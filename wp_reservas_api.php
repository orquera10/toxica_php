<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/reglas_reservas.php';
require_once __DIR__ . '/bloqueos_horarios.php';
require_once __DIR__ . '/mercadopago_utils.php';
require_once __DIR__ . '/cliente_mailer.php';

header('Content-Type: application/json; charset=utf-8');

function api_responder($status, $data)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_obtener_header($nombre)
{
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $nombre));
    return $_SERVER[$key] ?? '';
}

function api_autorizar()
{
    global $api_key;

    $header_key = api_obtener_header('X-API-Key');
    $authorization = api_obtener_header('Authorization');
    $bearer = '';

    if (stripos($authorization, 'Bearer ') === 0) {
        $bearer = trim(substr($authorization, 7));
    }

    $key = $header_key !== '' ? $header_key : $bearer;
    if (empty($api_key) || $key === '' || !hash_equals((string) $api_key, (string) $key)) {
        api_responder(401, [
            'success' => false,
            'message' => 'API key invalida.',
        ]);
    }
}

function api_input()
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '', true);

    if (is_array($json)) {
        return array_merge($_POST, $json);
    }

    return $_POST;
}

function api_fecha_turno_cliente($fecha, $hora_inicio)
{
    if (in_array($hora_inicio, ['00:00', '00:30', '01:00', '01:30'], true)) {
        return date('d-m-Y', strtotime($fecha . ' -1 day')) . ' (madrugada del ' . $fecha . ')';
    }

    return $fecha;
}

function api_mask_email($email)
{
    $email = trim((string) $email);
    $partes = explode('@', $email, 2);

    if (count($partes) !== 2) {
        return $email;
    }

    $usuario = $partes[0];
    $dominio = $partes[1];
    $visible = substr($usuario, 0, 2);

    return $visible . str_repeat('*', max(3, strlen($usuario) - 2)) . '@' . $dominio;
}

function api_variantes_telefono($telefono)
{
    $telefono = trim((string) $telefono);
    $digitos = preg_replace('/[^0-9]/', '', $telefono);
    $digitos = ltrim($digitos, '0');
    $variantes = [];

    foreach ([$telefono, $digitos, wp_bot_normalizar_telefono($telefono)] as $valor) {
        $valor = trim((string) $valor);
        if ($valor !== '') {
            $variantes[] = $valor;
        }
    }

    if (strpos($digitos, '549') === 0) {
        $variantes[] = substr($digitos, 3);
        $variantes[] = '0' . substr($digitos, 3);
    } elseif (strpos($digitos, '54') === 0) {
        $variantes[] = substr($digitos, 2);
        $variantes[] = '0' . substr($digitos, 2);
    } elseif ($digitos !== '') {
        $variantes[] = '549' . $digitos;
        $variantes[] = '54' . $digitos;
    }

    return array_values(array_unique(array_filter($variantes)));
}

function api_telefono_local($telefono)
{
    $digitos = preg_replace('/[^0-9]/', '', (string) $telefono);
    $digitos = ltrim($digitos, '0');

    if (strpos($digitos, '549') === 0) {
        return substr($digitos, 3);
    }

    if (strpos($digitos, '54') === 0) {
        return substr($digitos, 2);
    }

    return $digitos;
}

function api_buscar_cliente($telefono, $email = '')
{
    global $con;

    $telefono = trim((string) $telefono);
    $telefonos = api_variantes_telefono($telefono);
    $email = strtolower(trim((string) $email));

    if (count($telefonos) > 0) {
        $placeholders = implode(',', array_fill(0, count($telefonos), '?'));
        $tipos = str_repeat('s', count($telefonos));
        $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO FROM clientes WHERE VISIBLE = 1 AND TELEFONO IN ({$placeholders}) LIMIT 1");
        $stmt->bind_param($tipos, ...$telefonos);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cliente) {
            return $cliente;
        }
    }

    if ($email !== '') {
        $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO FROM clientes WHERE VISIBLE = 1 AND MAIL = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $cliente = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($cliente) {
            return $cliente;
        }
    }

    return null;
}

function api_buscar_cliente_acceso_por_telefono($telefono)
{
    global $con;

    $telefonos = api_variantes_telefono($telefono);
    if (count($telefonos) === 0) {
        return null;
    }

    $placeholders = implode(',', array_fill(0, count($telefonos), '?'));
    $tipos = str_repeat('s', count($telefonos));
    $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO, CLAVE, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE VISIBLE = 1 AND TELEFONO IN ({$placeholders}) LIMIT 1");
    $stmt->bind_param($tipos, ...$telefonos);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $cliente ?: null;
}

function api_buscar_cliente_acceso_por_email($email)
{
    global $con;

    $email = strtolower(trim((string) $email));
    if ($email === '') {
        return null;
    }

    $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO, CLAVE, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE VISIBLE = 1 AND MAIL = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $cliente ?: null;
}

function api_email_usado_por_otro_cliente($email, $cliente_id = 0)
{
    global $con;

    $email = strtolower(trim((string) $email));
    if ($email === '') {
        return false;
    }

    $stmt = $con->prepare("SELECT _id FROM clientes WHERE MAIL = ? AND VISIBLE = 1 AND _id <> ? LIMIT 1");
    $stmt->bind_param("si", $email, $cliente_id);
    $stmt->execute();
    $existe = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $existe;
}

function api_registrar_cliente()
{
    global $con;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_responder(405, [
            'success' => false,
            'message' => 'Usa POST para registrar usuarios.',
        ]);
    }

    $input = api_input();
    $nombre = trim((string) ($input['nombre'] ?? $input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? $input['mail'] ?? '')));
    $telefono = trim((string) ($input['telefono'] ?? $input['phone'] ?? ''));
    $telefono_local = api_telefono_local($telefono);
    $clave = (string) ($input['clave'] ?? $input['password'] ?? '');
    $repetir_clave = (string) ($input['repetir_clave'] ?? $input['password_confirmation'] ?? $input['confirm_password'] ?? $clave);

    if ($nombre === '' || $telefono === '' || $email === '' || strlen($clave) < 6) {
        api_responder(400, [
            'success' => false,
            'message' => 'Completa nombre, email, telefono y una clave de al menos 6 caracteres.',
        ]);
    }

    if ($clave !== $repetir_clave) {
        api_responder(400, [
            'success' => false,
            'message' => 'Las claves no coinciden.',
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_responder(400, [
            'success' => false,
            'message' => 'Ingresa un email valido.',
        ]);
    }

    $telefono_guardar = $telefono_local !== '' ? $telefono_local : $telefono;
    $cliente_existente = api_buscar_cliente_acceso_por_telefono($telefono);
    $cliente_por_mail = api_buscar_cliente_acceso_por_email($email);

    if ($cliente_por_mail && (!$cliente_existente || (int) $cliente_por_mail['_id'] !== (int) $cliente_existente['_id'])) {
        api_responder(409, [
            'success' => false,
            'message' => 'Ese email ya esta registrado con otro cliente.',
        ]);
    }

    if ($cliente_existente && !empty($cliente_existente['CLAVE']) && (int) $cliente_existente['EMAIL_VERIFICADO'] === 1) {
        api_responder(409, [
            'success' => false,
            'message' => 'Ese telefono ya tiene acceso creado. Ingresa con tu clave.',
        ]);
    }

    $clave_hash = password_hash($clave, PASSWORD_BCRYPT);
    $codigo = (string) random_int(100000, 999999);
    $codigo_hash = password_hash($codigo, PASSWORD_BCRYPT);
    $codigo_expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

    if ($cliente_existente) {
        $cliente_id = (int) $cliente_existente['_id'];
        $stmt = $con->prepare("UPDATE clientes SET NOMBRE = ?, MAIL = ?, TELEFONO = ?, CLAVE = ?, EMAIL_VERIFICADO = 0, CODIGO_VERIFICACION = ?, CODIGO_EXPIRA = ? WHERE _id = ?");
        $stmt->bind_param("ssssssi", $nombre, $email, $telefono_guardar, $clave_hash, $codigo_hash, $codigo_expira, $cliente_id);
        $ok = $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $con->prepare("INSERT INTO clientes (NOMBRE, MAIL, TELEFONO, CLAVE, EMAIL_VERIFICADO, CODIGO_VERIFICACION, CODIGO_EXPIRA) VALUES (?, ?, ?, ?, 0, ?, ?)");
        $stmt->bind_param("ssssss", $nombre, $email, $telefono_guardar, $clave_hash, $codigo_hash, $codigo_expira);
        $ok = $stmt->execute();
        $cliente_id = $stmt->insert_id;
        $stmt->close();
    }

    if (!$ok) {
        api_responder(500, [
            'success' => false,
            'message' => 'No se pudo crear el acceso. Intenta nuevamente.',
        ]);
    }

    [$mail_ok, $mail_mensaje] = enviar_codigo_verificacion_cliente($email, $nombre, $codigo);
    if (!$mail_ok) {
        api_responder(502, [
            'success' => false,
            'message' => $mail_mensaje,
        ]);
    }

    api_responder(201, [
        'success' => true,
        'message' => 'Usuario registrado. Te enviamos un codigo para verificar el email.',
        'requires_verification' => true,
        'cliente' => [
            'id' => (int) $cliente_id,
            'nombre' => $nombre,
            'email' => $email,
            'email_masked' => api_mask_email($email),
            'telefono' => $telefono_guardar,
        ],
        'verification' => [
            'expires_at' => $codigo_expira,
        ],
    ]);
}

function api_verificar_registro_cliente()
{
    global $con;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_responder(405, [
            'success' => false,
            'message' => 'Usa POST para verificar usuarios.',
        ]);
    }

    $input = api_input();
    $cliente_id = (int) ($input['cliente_id'] ?? $input['id'] ?? 0);
    $codigo = trim((string) ($input['codigo'] ?? $input['code'] ?? ''));

    if ($cliente_id <= 0 || $codigo === '') {
        api_responder(400, [
            'success' => false,
            'message' => 'Indica cliente_id y codigo.',
        ]);
    }

    $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO, CODIGO_VERIFICACION, CODIGO_EXPIRA FROM clientes WHERE _id = ? AND VISIBLE = 1 LIMIT 1");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cliente || empty($cliente['CODIGO_VERIFICACION']) || strtotime($cliente['CODIGO_EXPIRA']) < time()) {
        api_responder(400, [
            'success' => false,
            'message' => 'Codigo vencido o invalido. Solicita uno nuevo.',
        ]);
    }

    if (!password_verify($codigo, $cliente['CODIGO_VERIFICACION'])) {
        api_responder(400, [
            'success' => false,
            'message' => 'Codigo invalido.',
        ]);
    }

    $stmt = $con->prepare("UPDATE clientes SET EMAIL_VERIFICADO = 1, CODIGO_VERIFICACION = NULL, CODIGO_EXPIRA = NULL WHERE _id = ?");
    $stmt->bind_param("i", $cliente_id);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        api_responder(500, [
            'success' => false,
            'message' => 'No se pudo verificar el usuario.',
        ]);
    }

    api_responder(200, [
        'success' => true,
        'message' => 'Usuario verificado.',
        'cliente' => [
            'id' => (int) $cliente['_id'],
            'nombre' => $cliente['NOMBRE'],
            'email' => $cliente['MAIL'],
            'telefono' => $cliente['TELEFONO'],
        ],
    ]);
}

function api_crear_cliente_simple()
{
    global $con;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_responder(405, [
            'success' => false,
            'message' => 'Usa POST para crear clientes.',
        ]);
    }

    $input = api_input();
    $nombre = trim((string) ($input['nombre'] ?? $input['name'] ?? ''));
    $email = strtolower(trim((string) ($input['email'] ?? $input['mail'] ?? '')));
    $telefono = trim((string) ($input['telefono'] ?? $input['phone'] ?? ''));
    $telefono_local = api_telefono_local($telefono);

    if ($nombre === '' || $telefono === '' || $email === '') {
        api_responder(400, [
            'success' => false,
            'message' => 'Completa nombre, email y telefono.',
        ]);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        api_responder(400, [
            'success' => false,
            'message' => 'Ingresa un email valido.',
        ]);
    }

    $cliente = api_buscar_cliente($telefono, '');
    if ($cliente) {
        api_responder(200, [
            'success' => true,
            'created' => false,
            'exists' => true,
            'message' => 'El telefono ya esta registrado.',
            'cliente' => [
                'id' => (int) $cliente['_id'],
                'nombre' => $cliente['NOMBRE'],
                'email' => $cliente['MAIL'],
                'email_masked' => api_mask_email($cliente['MAIL']),
                'telefono' => $cliente['TELEFONO'],
            ],
        ]);
    }

    $telefono_guardar = $telefono_local !== '' ? $telefono_local : $telefono;
    $cliente_por_email = api_buscar_cliente('', $email);
    $cliente_por_nombre = null;

    if (!$cliente_por_email) {
        $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO FROM clientes WHERE VISIBLE = 1 AND LOWER(TRIM(NOMBRE)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $cliente_por_nombre = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    $cliente_actualizar = $cliente_por_email ?: $cliente_por_nombre;
    if ($cliente_actualizar) {
        $cliente_id = (int) $cliente_actualizar['_id'];
        $stmt = $con->prepare("UPDATE clientes SET NOMBRE = ?, MAIL = ?, TELEFONO = ? WHERE _id = ?");
        $stmt->bind_param("sssi", $nombre, $email, $telefono_guardar, $cliente_id);
        $ok = $stmt->execute();
        $stmt->close();

        if (!$ok) {
            api_responder(500, [
                'success' => false,
                'message' => 'No se pudo actualizar el cliente.',
            ]);
        }

        api_responder(200, [
            'success' => true,
            'created' => false,
            'updated' => true,
            'exists' => true,
            'message' => $cliente_por_email ? 'Cliente actualizado por email existente.' : 'Cliente actualizado por nombre existente.',
            'cliente' => [
                'id' => $cliente_id,
                'nombre' => $nombre,
                'email' => $email,
                'email_masked' => api_mask_email($email),
                'telefono' => $telefono_guardar,
            ],
        ]);
    }

    $stmt = $con->prepare("INSERT INTO clientes (NOMBRE, MAIL, TELEFONO) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $telefono_guardar);
    $ok = $stmt->execute();
    $cliente_id = $stmt->insert_id;
    $stmt->close();

    if (!$ok) {
        api_responder(500, [
            'success' => false,
            'message' => 'No se pudo crear el cliente.',
        ]);
    }

    api_responder(201, [
        'success' => true,
        'created' => true,
        'exists' => true,
        'message' => 'Cliente creado.',
        'cliente' => [
            'id' => (int) $cliente_id,
            'nombre' => $nombre,
            'email' => $email,
            'email_masked' => api_mask_email($email),
            'telefono' => $telefono_guardar,
        ],
    ]);
}

function api_obtener_o_crear_cliente($datos)
{
    global $con;

    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $email = strtolower(trim((string) ($datos['email'] ?? '')));
    $telefono = trim((string) ($datos['telefono'] ?? ''));
    $telefono_local = api_telefono_local($telefono);

    if ($telefono === '' && $telefono_local === '') {
        return [false, 'Ingresa el telefono del cliente.', null];
    }

    $cliente = api_buscar_cliente($telefono, $email);
    if ($cliente) {
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Ingresa un email valido.', null];
        }

        $cliente_id = (int) $cliente['_id'];
        $nombre_final = (string) $cliente['NOMBRE'];
        $email_final = strtolower(trim((string) $cliente['MAIL']));
        $telefono_final = (string) $cliente['TELEFONO'];

        if ($email !== '' && $email_final !== '' && !hash_equals($email_final, $email)) {
            return [false, 'El telefono ya pertenece a un cliente registrado con otro email. Usa el email registrado o consulta con el local.', null];
        }

        if ($email_final === '' || !filter_var($email_final, FILTER_VALIDATE_EMAIL)) {
            return [false, 'El cliente necesita un email valido para generar el pago.', null];
        }

        return [true, '', [
            '_id' => $cliente_id,
            'NOMBRE' => $nombre_final,
            'MAIL' => $email_final,
            'TELEFONO' => $telefono_final,
        ]];
    }

    if ($nombre === '' || $email === '') {
        return [false, 'Para crear un cliente nuevo ingresa nombre, email y telefono.', null];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Ingresa un email valido.', null];
    }

    if (api_email_usado_por_otro_cliente($email)) {
        return [false, 'Ese email ya esta registrado con otro cliente.', null];
    }

    $telefono_guardar = $telefono_local !== '' ? $telefono_local : $telefono;
    $stmt = $con->prepare("INSERT INTO clientes (NOMBRE, MAIL, TELEFONO) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $email, $telefono_guardar);
    $ok = $stmt->execute();
    $cliente_id = $stmt->insert_id;
    $stmt->close();

    if (!$ok) {
        return [false, 'No se pudo crear el cliente.', null];
    }

    return [true, '', [
        '_id' => $cliente_id,
        'NOMBRE' => $nombre,
        'MAIL' => $email,
        'TELEFONO' => $telefono_guardar,
    ]];
}

function api_sql_solapados()
{
    return "SELECT t._id
        FROM turnos t
        INNER JOIN canchas c ON t.id_CANCHA = c._id
        WHERE VENTA = 0
        AND (
            STR_TO_DATE(CONCAT(t.FECHA, ' ', t.HORA_INICIO), '%d-%m-%Y %H:%i') < STR_TO_DATE(?, '%d-%m-%Y %H:%i')
            AND
            STR_TO_DATE(
                CONCAT(
                    CASE
                        WHEN t.HORA_FIN IN ('00:00', '00:30', '01:00', '01:30', '02:00') AND t.HORA_INICIO NOT IN ('00:00', '00:30', '01:00', '01:30')
                            THEN DATE_FORMAT(DATE_ADD(STR_TO_DATE(t.FECHA, '%d-%m-%Y'), INTERVAL 1 DAY), '%d-%m-%Y')
                        WHEN t.HORA_FIN = '24:00'
                            THEN DATE_FORMAT(DATE_ADD(STR_TO_DATE(t.FECHA, '%d-%m-%Y'), INTERVAL 1 DAY), '%d-%m-%Y')
                        ELSE t.FECHA
                    END,
                    ' ',
                    CASE WHEN t.HORA_FIN = '24:00' THEN '00:00' ELSE t.HORA_FIN END
                ),
                '%d-%m-%Y %H:%i'
            ) > STR_TO_DATE(?, '%d-%m-%Y %H:%i')
        )
        AND " . condicion_solapamiento_canchas_sql();
}

function api_cancha($id_cancha)
{
    global $con;

    $stmt = $con->prepare("SELECT _id, NOMBRE, PRECIO FROM canchas WHERE _id = ? AND _id NOT IN (9, 10) LIMIT 1");
    $stmt->bind_param("i", $id_cancha);
    $stmt->execute();
    $cancha = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $cancha ?: null;
}

function api_horario_ocupado($datetime_inicio, $datetime_fin, $id_cancha)
{
    global $con;

    $stmt = $con->prepare(api_sql_solapados());
    bind_parametros_solapamiento($stmt, $datetime_fin, $datetime_inicio, $id_cancha);
    $stmt->execute();
    $ocupado = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    return $ocupado;
}

function api_slots_disponibles($fecha_input, $id_cancha, $duracion)
{
    global $con;

    $fecha_ts = strtotime($fecha_input);
    if ($fecha_ts === false) {
        return [false, 'Fecha invalida.', []];
    }

    if (!reserva_dentro_del_plazo($fecha_input, $id_cancha)) {
        return [false, mensaje_plazo_reserva($id_cancha), []];
    }

    $cancha = api_cancha($id_cancha);
    if (!$cancha) {
        return [false, 'Cancha no disponible.', []];
    }

    $reglas_precio = precios_horarios_cancha($con, $id_cancha);
    $total_base = (float) $cancha['PRECIO'] * ($id_cancha === CANCHA_CUMPLE_ID ? 1 : $duracion);
    $slots = [];
    $franjas_horario = limites_franjas_horario_cancha($con, $id_cancha, $fecha_input);
    if (count($franjas_horario) === 0) return [true, '', []];
    $ahora = time();
    $inicios_minutos = [];

    if ($id_cancha === CANCHA_CUMPLE_ID) {
        foreach (horarios_inicio_cumpleanios_cliente($fecha_input) as $hora_cumple) {
            $partes = explode(':', $hora_cumple);
            $inicios_minutos[] = ((int) $partes[0] * 60) + (int) $partes[1];
        }
    } else {
        $inicios_minutos = inicios_en_horas_punto($franjas_horario, $duracion);
    }

    foreach ($inicios_minutos as $inicio_minutos) {
        $inicio_ts = strtotime(date('Y-m-d', $fecha_ts) . ' 00:00') + ($inicio_minutos * 60);
        $fin_ts = strtotime('+' . $duracion . ' hours', $inicio_ts);

        if ($inicio_ts === false || $fin_ts === false || $inicio_ts <= $ahora) {
            continue;
        }

        $datetime_inicio = date('d-m-Y H:i', $inicio_ts);
        $datetime_fin = date('d-m-Y H:i', $fin_ts);

        if (horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)) {
            continue;
        }

        if (api_horario_ocupado($datetime_inicio, $datetime_fin, $id_cancha)) {
            continue;
        }

        $hora_inicio = date('H:i', $inicio_ts);
        $hora_fin = date('H:i', $fin_ts);
        if (!reserva_en_horario_habil_cancha($con, $id_cancha, $fecha_input, $hora_inicio, $duracion)) continue;
        $es_madrugada = date('Y-m-d', $inicio_ts) !== date('Y-m-d', $fecha_ts);

        $total = calcular_precio_turno($reglas_precio, $cancha['PRECIO'], date('Y-m-d', $inicio_ts), $hora_inicio, $duracion, $id_cancha === CANCHA_CUMPLE_ID);
        $slots[] = [
            'total' => $total,
            'total_base' => $total_base,
            'minimo_senia' => minimo_senia_reserva($con, $total, $id_cancha, date('Y-m-d', $inicio_ts), $hora_inicio),
            'fecha' => date('Y-m-d', $inicio_ts),
            'inicio' => $hora_inicio,
            'fin' => $hora_fin,
            'label' => $hora_inicio . ' a ' . $hora_fin . ($es_madrugada ? ' (madrugada)' : ''),
        ];
    }

    return [true, '', $slots];
}

function api_listar_canchas()
{
    global $con;

    $resultado = mysqli_query($con, "SELECT _id, NOMBRE, PRECIO FROM canchas WHERE _id NOT IN (9, 10) ORDER BY NOMBRE");
    $canchas = [];

    while ($cancha = mysqli_fetch_assoc($resultado)) {
        $id = (int) $cancha['_id'];
        $canchas[] = [
            'id' => $id,
            'nombre' => nombre_cancha_cliente($id, $cancha['NOMBRE']),
            'precio' => (float) $cancha['PRECIO'],
            'precio_unidad' => $id === CANCHA_CUMPLE_ID ? '3 hs' : 'hora',
            'tiene_precios_horarios' => count(precios_horarios_cancha($con, $id)) > 0,
            'duracion_fija' => $id === CANCHA_CUMPLE_ID ? 3 : null,
        ];
    }

    api_responder(200, [
        'success' => true,
        'canchas' => $canchas,
    ]);
}

function api_terminos()
{
    $tipo = $_GET['tipo'] ?? 'turno';
    $cumple = $tipo === 'cumple' || (int) ($_GET['cancha'] ?? 0) === CANCHA_CUMPLE_ID;

    $terminos = $cumple
        ? terminos_reserva_cumpleanios()
        : terminos_reserva_turnos();

    api_responder(200, [
        'success' => true,
        'tipo' => $cumple ? 'cumple' : 'turno',
        'terminos' => $terminos,
    ]);
}

function api_disponibilidad()
{
    $fecha = $_GET['fecha'] ?? '';
    $id_cancha = (int) ($_GET['cancha'] ?? 0);
    $duracion = duracion_reserva($id_cancha, (int) ($_GET['duracion'] ?? 1));

    if ($fecha === '' || $id_cancha <= 0 || $duracion < 1 || $duracion > 4) {
        api_responder(400, [
            'success' => false,
            'message' => 'Datos incompletos.',
            'slots' => [],
        ]);
    }

    [$ok, $mensaje, $slots] = api_slots_disponibles($fecha, $id_cancha, $duracion);
    api_responder($ok ? 200 : 400, [
        'success' => $ok,
        'message' => $mensaje,
        'slots' => $slots,
    ]);
}

function api_consultar_cliente()
{
    global $con;

    $telefono = $_GET['telefono'] ?? '';
    $email = $_GET['email'] ?? '';
    $nombre = trim((string) ($_GET['nombre'] ?? ''));
    $cliente = api_buscar_cliente($telefono, $email);

    if (!$cliente && $nombre !== '') {
        $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO FROM clientes WHERE VISIBLE = 1 AND LOWER(TRIM(NOMBRE)) = LOWER(TRIM(?)) LIMIT 2");
        $stmt->bind_param("s", $nombre);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($resultado->num_rows === 1) {
            $cliente = $resultado->fetch_assoc();
        } elseif ($resultado->num_rows > 1) {
            $stmt->close();
            api_responder(200, [
                'success' => true,
                'exists' => false,
                'ambiguous' => true,
                'message' => 'Hay mas de un cliente con ese nombre. Verifica por email o telefono.',
                'cliente' => null,
            ]);
        }

        $stmt->close();
    }

    if (!$cliente) {
        api_responder(200, [
            'success' => true,
            'exists' => false,
            'message' => 'No se encontro un cliente registrado con ese telefono.',
            'cliente' => null,
        ]);
    }

    api_responder(200, [
        'success' => true,
        'exists' => true,
        'message' => 'Cliente encontrado.',
        'cliente' => [
            'id' => (int) $cliente['_id'],
            'nombre' => $cliente['NOMBRE'],
            'email' => $cliente['MAIL'],
            'email_masked' => api_mask_email($cliente['MAIL']),
            'telefono' => $cliente['TELEFONO'],
        ],
    ]);
}

function api_consultar_turnos()
{
    global $con;

    $telefono = $_GET['telefono'] ?? '';
    $email = $_GET['email'] ?? '';
    $cliente = api_buscar_cliente($telefono, $email);

    if (!$cliente) {
        api_responder(404, [
            'success' => false,
            'message' => 'No se encontro el cliente.',
            'turnos' => [],
        ]);
    }

    $cliente_id = (int) $cliente['_id'];
    $solo_futuros = ($_GET['futuros'] ?? '1') !== '0';
    $limite = max(1, min(20, (int) ($_GET['limite'] ?? 10)));

    $where_futuros = $solo_futuros
        ? "AND STR_TO_DATE(CONCAT(t.FECHA, ' ', t.HORA_INICIO), '%d-%m-%Y %H:%i') >= NOW()"
        : "";
    $orden_turnos = $solo_futuros ? 'ASC' : 'DESC';

    $stmt = $con->prepare("SELECT
            tk._id AS ticket_id,
            tk.TOTAL,
            tk.TOTAL_CANCHA,
            tk.SENIA,
            tk.MP_SENIA,
            tk.ESTADO_RESERVA,
            tk.MP_PREFERENCE_ID,
            t._id AS turno_id,
            t.FECHA,
            t.HORA_INICIO,
            t.HORA_FIN,
            c._id AS cancha_id,
            c.NOMBRE AS cancha
        FROM ticket tk
        INNER JOIN turnos t ON t._id = tk.id_TURNO
        INNER JOIN canchas c ON c._id = t.id_CANCHA
        WHERE tk.id_CLIENTE = ? AND t.VENTA = 0 {$where_futuros}
        ORDER BY STR_TO_DATE(CONCAT(t.FECHA, ' ', t.HORA_INICIO), '%d-%m-%Y %H:%i') {$orden_turnos}, t._id {$orden_turnos}
        LIMIT ?");
    $stmt->bind_param("ii", $cliente_id, $limite);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $turnos = [];

    while ($turno = $resultado->fetch_assoc()) {
        $turnos[] = [
            'ticket_id' => (int) $turno['ticket_id'],
            'turno_id' => (int) $turno['turno_id'],
            'estado' => $turno['ESTADO_RESERVA'],
            'fecha' => $turno['FECHA'],
            'fecha_label' => api_fecha_turno_cliente($turno['FECHA'], $turno['HORA_INICIO']),
            'hora_inicio' => $turno['HORA_INICIO'],
            'hora_fin' => $turno['HORA_FIN'],
            'cancha_id' => (int) $turno['cancha_id'],
            'cancha' => nombre_cancha_cliente($turno['cancha_id'], $turno['cancha']),
            'senia' => (float) ($turno['SENIA'] > 0 ? $turno['SENIA'] : $turno['MP_SENIA']),
            'total_cancha' => (float) $turno['TOTAL_CANCHA'],
            'saldo_pendiente' => (float) $turno['TOTAL'],
        ];
    }

    $stmt->close();

    api_responder(200, [
        'success' => true,
        'cliente' => [
            'id' => (int) $cliente['_id'],
            'nombre' => $cliente['NOMBRE'],
            'email' => $cliente['MAIL'],
            'telefono' => $cliente['TELEFONO'],
        ],
        'turnos' => $turnos,
    ]);
}

function api_crear_reserva()
{
    global $con;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_responder(405, [
            'success' => false,
            'message' => 'Usa POST para crear reservas.',
        ]);
    }

    $input = api_input();
    $acepta_terminos = filter_var($input['acepta_terminos'] ?? false, FILTER_VALIDATE_BOOLEAN);

    if (!$acepta_terminos) {
        api_responder(400, [
            'success' => false,
            'message' => 'El cliente debe aceptar los terminos y condiciones de la reserva.',
        ]);
    }

    $cliente_input = is_array($input['cliente'] ?? null) ? $input['cliente'] : $input;
    [$cliente_ok, $cliente_mensaje, $cliente] = api_obtener_o_crear_cliente($cliente_input);

    if (!$cliente_ok) {
        api_responder(400, [
            'success' => false,
            'message' => $cliente_mensaje,
        ]);
    }

    $fecha_input = (string) ($input['fecha'] ?? $input['fecha_reserva'] ?? '');
    $hora_inicio = (string) ($input['hora_inicio'] ?? '');
    $id_cancha = (int) ($input['cancha'] ?? $input['cancha_id'] ?? 0);
    $duracion = duracion_reserva($id_cancha, (int) ($input['duracion'] ?? 1));

    if ($fecha_input === '' || $hora_inicio === '' || $id_cancha <= 0 || $duracion < 1 || $duracion > 4) {
        api_responder(400, [
            'success' => false,
            'message' => 'Completa fecha, hora_inicio, cancha y duracion.',
        ]);
    }

    if (!reserva_dentro_del_plazo($fecha_input, $id_cancha, $hora_inicio)) {
        api_responder(400, [
            'success' => false,
            'message' => mensaje_plazo_reserva($id_cancha),
        ]);
    }

    if (!horario_en_hora_punto($hora_inicio)) {
        api_responder(400, [
            'success' => false,
            'message' => 'Los turnos deben comenzar en horas en punto.',
        ]);
    }

    $reserva_horario_valida = $id_cancha === CANCHA_CUMPLE_ID
        ? reserva_cumpleanios_cliente_permitida($fecha_input, $hora_inicio, $duracion) && reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, $duracion, $fecha_input)
        : reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, $duracion, $fecha_input);

    if (!$reserva_horario_valida) {
        api_responder(400, [
            'success' => false,
            'message' => $id_cancha === CANCHA_CUMPLE_ID
                ? mensaje_horario_cumpleanios_cliente()
                : 'El horario seleccionado no esta permitido para esa cancha.',
        ]);
    }

    $inicio_ts = strtotime($fecha_input . ' ' . $hora_inicio);
    if ($inicio_ts === false || $inicio_ts < time()) {
        api_responder(400, [
            'success' => false,
            'message' => 'Elige una fecha y horario futuro.',
        ]);
    }

    $cancha = api_cancha($id_cancha);
    if (!$cancha) {
        api_responder(400, [
            'success' => false,
            'message' => 'Cancha no disponible.',
        ]);
    }

    $fin_ts = strtotime('+' . $duracion . ' hours', $inicio_ts);
    $fecha = date('d-m-Y', $inicio_ts);
    $fecha_reserva = date('Y-m-d', $inicio_ts);
    $hora_inicio = date('H:i', $inicio_ts);
    $hora_fin = date('H:i', $fin_ts);
    $fecha_fin = date('d-m-Y', $fin_ts);
    $datetime_inicio = $fecha . ' ' . $hora_inicio;
    $datetime_fin = $fecha_fin . ' ' . $hora_fin;

    if (api_horario_ocupado($datetime_inicio, $datetime_fin, $id_cancha)) {
        api_responder(409, [
            'success' => false,
            'message' => 'Ese horario ya esta ocupado.',
        ]);
    }

    if (horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)) {
        api_responder(409, [
            'success' => false,
            'message' => 'Ese horario esta bloqueado.',
        ]);
    }

    $color_evento = match ($id_cancha) {
        7 => "#FCC30C",
        5 => "#4ED8E1",
        6 => "#8FE14E",
        8 => "#EA7BF3",
        10 => "#FFFFFF",
        11 => "#98A2FA",
        default => "#4E9BE1",
    };

    $total_cancha = precio_total_reserva($con, $id_cancha, $cancha['PRECIO'], $fecha_reserva, $hora_inicio, $duracion);
    $minimo_senia = minimo_senia_reserva($con, $total_cancha, $id_cancha, $fecha_reserva, $hora_inicio);
    $monto_senia = isset($input['monto_senia']) ? round((float) $input['monto_senia'], 2) : $minimo_senia;

    if ($monto_senia < $minimo_senia || $monto_senia > $total_cancha) {
        api_responder(400, [
            'success' => false,
            'message' => 'La senia debe estar entre el minimo requerido y el total del turno.',
            'minimo_senia' => $minimo_senia,
            'total_cancha' => (float) $total_cancha,
        ]);
    }

    $stmt = $con->prepare("INSERT INTO turnos (HORA_INICIO, HORA_FIN, COLOR, FECHA, id_CANCHA, FINALIZADO, VENTA, id_USUARIO) VALUES (?, ?, ?, ?, ?, 0, 0, NULL)");
    $stmt->bind_param("ssssi", $hora_inicio, $hora_fin, $color_evento, $fecha, $id_cancha);
    $ok_turno = $stmt->execute();
    $id_turno = $stmt->insert_id;
    $stmt->close();

    if (!$ok_turno) {
        api_responder(500, [
            'success' => false,
            'message' => 'No se pudo guardar el turno.',
        ]);
    }

    $cliente_id = (int) $cliente['_id'];
    $extra = 0;
    $total_detalle = 0;
    $pagado = 0;
    $estado_reserva = 'pendiente_pago';
    $pendiente_expira = date('Y-m-d H:i:s', strtotime('+' . RESERVA_PENDIENTE_MINUTOS . ' minutes'));

    $stmt = $con->prepare("INSERT INTO ticket (id_TURNO, id_CLIENTE, FECHA, TOTAL_CANCHA, EXTRA, TOTAL_DETALLE, TOTAL, ESTADO_RESERVA, MP_SENIA, PAGO_TRANSFERENCIA, PAGO_EFECTIVO, PENDIENTE_EXPIRA) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisddddsddds", $id_turno, $cliente_id, $fecha, $total_cancha, $extra, $total_detalle, $total_cancha, $estado_reserva, $monto_senia, $pagado, $pagado, $pendiente_expira);
    $ok_ticket = $stmt->execute();
    $id_ticket = $stmt->insert_id;
    $stmt->close();

    if (!$ok_ticket) {
        mysqli_query($con, "DELETE FROM turnos WHERE _id = " . (int) $id_turno);
        api_responder(500, [
            'success' => false,
            'message' => 'No se pudo generar el ticket de la reserva.',
        ]);
    }

    $nombre_cancha = nombre_cancha_cliente($id_cancha, $cancha['NOMBRE']);
    $descripcion = "Senia reserva " . $nombre_cancha . " " . $fecha . " " . $hora_inicio . " a " . $hora_fin;
    [$mp_ok, $mp_mensaje, $preferencia] = crear_preferencia_senia($id_ticket, $cliente, $descripcion, $monto_senia, $pendiente_expira);

    if (!$mp_ok || empty($preferencia['init_point'])) {
        cancelar_reserva_pendiente($id_ticket);
        api_responder(502, [
            'success' => false,
            'message' => $mp_mensaje ?: 'No se pudo iniciar el pago de la senia.',
        ]);
    }

    $preference_id = $preferencia['id'] ?? '';
    $stmt = $con->prepare("UPDATE ticket SET MP_PREFERENCE_ID = ? WHERE _id = ?");
    $stmt->bind_param("si", $preference_id, $id_ticket);
    $stmt->execute();
    $stmt->close();

    api_responder(201, [
        'success' => true,
        'message' => 'Reserva creada. Queda pendiente hasta que se pague la senia.',
        'cliente' => [
            'id' => $cliente_id,
            'nombre' => $cliente['NOMBRE'],
            'email' => $cliente['MAIL'],
            'telefono' => $cliente['TELEFONO'],
        ],
        'reserva' => [
            'ticket_id' => $id_ticket,
            'turno_id' => $id_turno,
            'estado' => $estado_reserva,
            'fecha' => $fecha,
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
            'cancha_id' => $id_cancha,
            'cancha' => $nombre_cancha,
            'duracion' => $duracion,
            'total_cancha' => (float) $total_cancha,
            'senia' => (float) $monto_senia,
        ],
        'mercadopago' => [
            'preference_id' => $preference_id,
            'init_point' => $preferencia['init_point'],
        ],
    ]);
}

function api_cancelar_reserva()
{
    global $con;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        api_responder(405, [
            'success' => false,
            'message' => 'Usa POST para cancelar reservas.',
        ]);
    }

    $input = api_input();
    $ticket_id = (int) ($input['ticket_id'] ?? 0);
    $telefono = $input['telefono'] ?? ($input['cliente']['telefono'] ?? '');
    $email = $input['email'] ?? ($input['cliente']['email'] ?? '');

    if ($ticket_id <= 0) {
        api_responder(400, [
            'success' => false,
            'message' => 'Indica el ticket_id de la reserva.',
        ]);
    }

    $cliente = api_buscar_cliente($telefono, $email);
    if (!$cliente) {
        api_responder(404, [
            'success' => false,
            'message' => 'No se encontro el cliente.',
        ]);
    }

    $cliente_id = (int) $cliente['_id'];
    $stmt = $con->prepare("SELECT
            tk._id AS ticket_id,
            tk.id_TURNO,
            tk.ESTADO_RESERVA,
            tk.SENIA,
            tk.MP_SENIA,
            t.FECHA,
            t.HORA_INICIO,
            t.HORA_FIN,
            c._id AS cancha_id,
            c.NOMBRE AS cancha
        FROM ticket tk
        INNER JOIN turnos t ON t._id = tk.id_TURNO
        INNER JOIN canchas c ON c._id = t.id_CANCHA
        WHERE tk._id = ? AND tk.id_CLIENTE = ? AND t.VENTA = 0
        LIMIT 1");
    $stmt->bind_param("ii", $ticket_id, $cliente_id);
    $stmt->execute();
    $reserva = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$reserva) {
        api_responder(404, [
            'success' => false,
            'message' => 'No se encontro una reserva de ese cliente con ese ticket_id.',
        ]);
    }

    if ($reserva['ESTADO_RESERVA'] !== 'pendiente_pago') {
        api_responder(409, [
            'success' => false,
            'message' => 'Solo se pueden cancelar reservas pendientes de pago desde el bot.',
            'estado' => $reserva['ESTADO_RESERVA'],
        ]);
    }

    if ((float) $reserva['SENIA'] > 0) {
        api_responder(409, [
            'success' => false,
            'message' => 'La reserva ya tiene senia registrada y no puede cancelarse desde el bot.',
        ]);
    }

    $turno_id = (int) $reserva['id_TURNO'];
    $con->begin_transaction();

    $stmt = $con->prepare("UPDATE ticket SET ESTADO_RESERVA = 'cancelada' WHERE _id = ? AND id_CLIENTE = ? AND ESTADO_RESERVA = 'pendiente_pago'");
    $stmt->bind_param("ii", $ticket_id, $cliente_id);
    $stmt->execute();
    $ticket_actualizado = $stmt->affected_rows === 1;
    $stmt->close();

    if (!$ticket_actualizado) {
        $con->rollback();
        api_responder(409, [
            'success' => false,
            'message' => 'La reserva ya no esta pendiente de pago.',
        ]);
    }

    $stmt = $con->prepare("DELETE FROM turnos WHERE _id = ?");
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();
    $stmt->close();

    $con->commit();

    api_responder(200, [
        'success' => true,
        'message' => 'Reserva pendiente cancelada.',
        'reserva' => [
            'ticket_id' => $ticket_id,
            'turno_id' => $turno_id,
            'estado' => 'cancelada',
            'fecha' => $reserva['FECHA'],
            'hora_inicio' => $reserva['HORA_INICIO'],
            'hora_fin' => $reserva['HORA_FIN'],
            'cancha' => nombre_cancha_cliente($reserva['cancha_id'], $reserva['cancha']),
        ],
    ]);
}

api_autorizar();
cancelar_reservas_pendientes_vencidas($con);

$action = $_GET['action'] ?? $_GET['endpoint'] ?? '';

switch ($action) {
    case 'canchas':
        api_listar_canchas();
        break;
    case 'terminos':
        api_terminos();
        break;
    case 'cliente':
        api_consultar_cliente();
        break;
    case 'disponibilidad':
        api_disponibilidad();
        break;
    case 'turnos':
        api_consultar_turnos();
        break;
    case 'registrar':
    case 'registro':
    case 'registrar_usuario':
        api_registrar_cliente();
        break;
    case 'verificar_registro':
    case 'confirmar_registro':
        api_verificar_registro_cliente();
        break;
    case 'crear_cliente':
    case 'registrar_cliente':
        api_crear_cliente_simple();
        break;
    case 'reservar':
        api_crear_reserva();
        break;
    case 'cancelar':
        api_cancelar_reserva();
        break;
    default:
        api_responder(404, [
            'success' => false,
            'message' => 'Endpoint no encontrado.',
            'endpoints' => [
                'GET action=canchas',
                'GET action=terminos&tipo=turno|cumple',
                'GET action=cliente&telefono=...',
                'GET action=disponibilidad&fecha=YYYY-MM-DD&cancha=ID&duracion=1',
                'GET action=turnos&telefono=...',
                'POST action=registrar',
                'POST action=verificar_registro',
                'POST action=crear_cliente',
                'POST action=reservar',
                'POST action=cancelar',
            ],
        ]);
}
?>

