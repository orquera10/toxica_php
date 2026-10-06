<?php
require_once 'config.php';
require_once 'cliente_mailer.php';
require_once 'reglas_reservas.php';
require_once 'vendor/autoload.php';

function app_public_url()
{
    global $mercadopago_public_url;

    if (!empty($mercadopago_public_url)) {
        return rtrim($mercadopago_public_url, '/');
    }

    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
    $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

    return $scheme . '://' . $host . ($path === '/' ? '' : $path);
}

function mercadopago_request($method, $endpoint, $payload = null)
{
    global $mercadopago_access_token;

    if (empty($mercadopago_access_token)) {
        return [false, 'Falta configurar el Access Token de Mercado Pago en config.php.', null];
    }

    $ch = curl_init('https://api.mercadopago.com' . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $mercadopago_access_token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [false, 'Error de conexion con Mercado Pago: ' . $error, null];
    }

    $data = json_decode($response, true);
    if ($status < 200 || $status >= 300) {
        return [false, $data['message'] ?? 'Mercado Pago rechazo la solicitud.', $data];
    }

    return [true, '', $data];
}

function wp_bot_normalizar_telefono($telefono)
{
    $numero = preg_replace('/[^0-9]/', '', (string) $telefono);
    $numero = ltrim($numero, '0');

    if ($numero === '') {
        return '';
    }

    if (strpos($numero, '549') === 0) {
        return $numero;
    }

    if (strpos($numero, '54') === 0) {
        $numero = substr($numero, 2);
    }

    return '549' . ltrim($numero, '0');
}

function asegurar_columnas_confirmacion_wp()
{
    global $con;

    $resultado = mysqli_query($con, "SHOW COLUMNS FROM ticket LIKE 'WP_CONFIRMACION_ENVIADA'");
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        return true;
    }

    if (!mysqli_query($con, "ALTER TABLE ticket
        ADD COLUMN WP_CONFIRMACION_ENVIADA TINYINT(1) NOT NULL DEFAULT 0,
        ADD COLUMN WP_CONFIRMACION_FECHA DATETIME NULL,
        ADD COLUMN WP_CONFIRMACION_ERROR TEXT NULL")) {
        error_log("No se pudieron crear columnas de confirmacion WP: " . mysqli_error($con));
        return false;
    }

    return true;
}

function reservar_confirmacion_wp($ticket_id)
{
    global $con;

    if (!asegurar_columnas_confirmacion_wp()) {
        return false;
    }

    $ticket_id = (int) $ticket_id;
    $stmt = $con->prepare("UPDATE ticket SET WP_CONFIRMACION_ENVIADA = 1, WP_CONFIRMACION_FECHA = NOW(), WP_CONFIRMACION_ERROR = NULL WHERE _id = ? AND WP_CONFIRMACION_ENVIADA = 0");
    if (!$stmt) {
        error_log("No se pudo preparar reserva de confirmacion WP: " . mysqli_error($con));
        return false;
    }
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $reservado = $stmt->affected_rows === 1;
    $stmt->close();

    return $reservado;
}

function registrar_error_confirmacion_wp($ticket_id, $mensaje)
{
    global $con;

    if (!asegurar_columnas_confirmacion_wp()) {
        error_log("Error confirmacion WP ticket {$ticket_id}: " . $mensaje);
        return;
    }

    $ticket_id = (int) $ticket_id;
    $mensaje = substr((string) $mensaje, 0, 1000);
    $stmt = $con->prepare("UPDATE ticket SET WP_CONFIRMACION_ERROR = ? WHERE _id = ?");
    if (!$stmt) {
        error_log("Error confirmacion WP ticket {$ticket_id}: " . $mensaje);
        return;
    }
    $stmt->bind_param("si", $mensaje, $ticket_id);
    $stmt->execute();
    $stmt->close();
}

function liberar_confirmacion_wp($ticket_id, $mensaje)
{
    global $con;

    if (!asegurar_columnas_confirmacion_wp()) {
        error_log("Error confirmacion WP ticket {$ticket_id}: " . $mensaje);
        return;
    }

    $ticket_id = (int) $ticket_id;
    $mensaje = substr((string) $mensaje, 0, 1000);
    $stmt = $con->prepare("UPDATE ticket SET WP_CONFIRMACION_ENVIADA = 0, WP_CONFIRMACION_FECHA = NULL, WP_CONFIRMACION_ERROR = ? WHERE _id = ?");
    if (!$stmt) {
        error_log("Error confirmacion WP ticket {$ticket_id}: " . $mensaje);
        return;
    }
    $stmt->bind_param("si", $mensaje, $ticket_id);
    $stmt->execute();
    $stmt->close();
}

function enviar_mail_confirmacion_reserva($datos)
{
    $email = trim((string) ($datos['MAIL'] ?? ''));
    $nombre = trim((string) ($datos['cliente_nombre'] ?? 'Cliente'));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Email de cliente invalido.'];
    }

    $monto_mp = (float) ($datos['MP_SENIA'] ?? 0);
    $saldo = (float) ($datos['TOTAL'] ?? 0);
    $html = '
        <div style="font-family:Arial,sans-serif;background:#162426;color:#f9f5d2;padding:24px">
            <div style="max-width:560px;margin:auto;background:#315257;padding:24px;border-radius:8px">
                <h1 style="color:#fcc30c;margin-top:0">Reserva confirmada</h1>
                <p>Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ', tu reserva en La Toxica quedo confirmada.</p>
                <table style="width:100%;border-collapse:collapse;color:#f9f5d2">
                    <tr><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18)">Cancha</td><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18);text-align:right"><strong>' . htmlspecialchars((string) ($datos['cancha_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong></td></tr>
                    <tr><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18)">Fecha</td><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18);text-align:right"><strong>' . htmlspecialchars((string) ($datos['FECHA'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong></td></tr>
                    <tr><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18)">Horario</td><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18);text-align:right"><strong>' . htmlspecialchars((string) ($datos['HORA_INICIO'] ?? ''), ENT_QUOTES, 'UTF-8') . ' a ' . htmlspecialchars((string) ($datos['HORA_FIN'] ?? ''), ENT_QUOTES, 'UTF-8') . '</strong></td></tr>
                    <tr><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18)">Senia Mercado Pago</td><td style="padding:8px 0;border-bottom:1px solid rgba(255,255,255,.18);text-align:right"><strong>$' . number_format($monto_mp, 0, ',', '.') . '</strong></td></tr>
                    <tr><td style="padding:8px 0">Saldo pendiente</td><td style="padding:8px 0;text-align:right"><strong>$' . number_format($saldo, 0, ',', '.') . '</strong></td></tr>
                </table>
                <p style="margin-bottom:0">Te esperamos!</p>
            </div>
        </div>';

    return enviar_mail_smtp($email, $nombre, 'Reserva confirmada - La Toxica', $html);
}

function wp_bot_enviar_mensaje($telefono, $mensaje)
{
    global $api_key, $wp_bot_send_endpoint;

    $payload = [
        'to' => $telefono,
        'message' => $mensaje,
    ];

    $ch = curl_init($wp_bot_send_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'X-API-Key: ' . $api_key,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        return [false, $response === false
            ? 'Error de conexion con bot WP: ' . $error
            : 'Bot WP rechazo la solicitud. HTTP ' . $status . ' - ' . $response];
    }

    return [true, 'Mensaje enviado.'];
}

function wp_bot_iniciar_invitacion_cumpleanios($telefono, $datos)
{
    global $api_key, $wp_bot_birthday_endpoint;

    if (empty($wp_bot_birthday_endpoint)) {
        return [false, 'Falta configurar WP_BOT_BIRTHDAY_ENDPOINT.'];
    }

    $payload = [
        'to' => $telefono,
        'phone' => $telefono,
        'date' => (string) ($datos['FECHA'] ?? ''),
        'startTime' => (string) ($datos['HORA_INICIO'] ?? ''),
        'endTime' => (string) ($datos['HORA_FIN'] ?? ''),
    ];

    $ch = curl_init($wp_bot_birthday_endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
        'X-API-Key: ' . $api_key,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $status < 200 || $status >= 300) {
        return [false, $response === false
            ? 'Error de conexion con bot WP: ' . $error
            : 'Bot WP rechazo la invitacion. HTTP ' . $status . ' - ' . $response];
    }

    return [true, 'Flujo de invitacion iniciado.'];
}

function enviar_alertas_reserva_confirmada_hoy($datos)
{
    $fecha = DateTimeImmutable::createFromFormat('!d-m-Y', (string) ($datos['FECHA'] ?? ''));
    if (!$fecha || $fecha->format('d-m-Y') !== date('d-m-Y')) {
        return [];
    }

    $configuracion = configuracion_plazos_reserva();
    $entrada = trim((string) ($configuracion['telefonos_alerta_reserva_hoy'] ?? ''));
    if ($entrada === '') {
        return [];
    }

    $monto_mp = (float) ($datos['MP_SENIA'] ?? 0);
    $saldo = (float) ($datos['TOTAL'] ?? 0);
    $mensaje = "NUEVA RESERVA CONFIRMADA PARA HOY\n"
        . "Cliente: " . trim((string) ($datos['cliente_nombre'] ?? '')) . "\n"
        . "Telefono: " . trim((string) ($datos['TELEFONO'] ?? '')) . "\n"
        . "Cancha: " . trim((string) ($datos['cancha_nombre'] ?? '')) . "\n"
        . "Fecha: " . trim((string) ($datos['FECHA'] ?? '')) . "\n"
        . "Horario: " . trim((string) ($datos['HORA_INICIO'] ?? '')) . " a " . trim((string) ($datos['HORA_FIN'] ?? '')) . "\n"
        . "Senia: $" . number_format($monto_mp, 0, ',', '.') . "\n"
        . "Saldo pendiente: $" . number_format($saldo, 0, ',', '.');

    $errores = [];
    $destinos_enviados = [];
    foreach (preg_split('/[\r\n,;]+/', $entrada, -1, PREG_SPLIT_NO_EMPTY) as $destino) {
        $telefono = wp_bot_normalizar_telefono($destino);
        if ($telefono === '' || in_array($telefono, $destinos_enviados, true)) {
            continue;
        }

        $destinos_enviados[] = $telefono;
        [$ok, $resultado] = wp_bot_enviar_mensaje($telefono, $mensaje);
        if (!$ok) {
            $errores[] = $telefono . ': ' . $resultado;
        }
    }

    return $errores;
}

function enviar_confirmacion_wp_bot($ticket_id)
{
    global $con, $api_key, $wp_bot_send_endpoint;

    if (empty($api_key)) {
        liberar_confirmacion_wp($ticket_id, 'Falta configurar API_KEY.');
        return [false, 'Falta configurar API_KEY.'];
    }

    if (empty($wp_bot_send_endpoint)) {
        liberar_confirmacion_wp($ticket_id, 'Falta configurar WP_BOT_SEND_ENDPOINT.');
        return [false, 'Falta configurar WP_BOT_SEND_ENDPOINT.'];
    }

    if (!reservar_confirmacion_wp($ticket_id)) {
        return [true, 'La confirmacion ya fue enviada o reservada.'];
    }

    $stmt = $con->prepare("SELECT
            tk.MP_SENIA,
            tk.TOTAL,
            cl.NOMBRE AS cliente_nombre,
            cl.MAIL,
            cl.TELEFONO,
            t.FECHA,
            t.HORA_INICIO,
            t.HORA_FIN,
            t.id_CANCHA AS cancha_id,
            ca.NOMBRE AS cancha_nombre
        FROM ticket tk
        INNER JOIN clientes cl ON cl._id = tk.id_CLIENTE
        INNER JOIN turnos t ON t._id = tk.id_TURNO
        INNER JOIN canchas ca ON ca._id = t.id_CANCHA
        WHERE tk._id = ?
        LIMIT 1");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $datos = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$datos) {
        liberar_confirmacion_wp($ticket_id, 'No se encontraron datos de la reserva.');
        return [false, 'No se encontraron datos de la reserva.'];
    }

    $datos['cancha_nombre'] = nombre_cancha_cliente(
        $datos['cancha_id'] ?? 0,
        $datos['cancha_nombre'] ?? ''
    );

    $errores_alertas = enviar_alertas_reserva_confirmada_hoy($datos);
    if ($errores_alertas) {
        $mensaje_alertas = 'No se pudieron enviar algunas alertas internas: ' . implode(' | ', $errores_alertas);
        registrar_error_confirmacion_wp($ticket_id, $mensaje_alertas);
        error_log("Error alertas WP ticket {$ticket_id}: " . $mensaje_alertas);
    }

    $telefono = wp_bot_normalizar_telefono($datos['TELEFONO'] ?? '');
    if ($telefono === '') {
        liberar_confirmacion_wp($ticket_id, 'Telefono de cliente invalido.');
        return [false, 'Telefono de cliente invalido.'];
    }

    [$mail_ok, $mail_mensaje] = enviar_mail_confirmacion_reserva($datos);
    if (!$mail_ok) {
        registrar_error_confirmacion_wp($ticket_id, 'No se pudo enviar el correo de confirmacion: ' . $mail_mensaje);
    }

    $monto_mp = (float) ($datos['MP_SENIA'] ?? 0);
    $saldo = (float) ($datos['TOTAL'] ?? 0);
    $message = "Tu reserva en La Toxica quedo confirmada.\n"
        . "Cancha: " . $datos['cancha_nombre'] . "\n"
        . "Fecha: " . $datos['FECHA'] . "\n"
        . "Horario: " . $datos['HORA_INICIO'] . " a " . $datos['HORA_FIN'] . "\n"
        . "Senia Mercado Pago: $" . number_format($monto_mp, 0, ',', '.') . "\n"
        . "Saldo pendiente: $" . number_format($saldo, 0, ',', '.') . "\n"
        . "Te esperamos!\n"
        . "Tambien se envio una copia al correo registrado.";

    [$enviado, $mensaje_error] = wp_bot_enviar_mensaje($telefono, $message);
    if (!$enviado) {
        liberar_confirmacion_wp($ticket_id, $mensaje_error);
        return [false, $mensaje_error];
    }

    if ((int) ($datos['cancha_id'] ?? 0) === (int) CANCHA_CUMPLE_ID) {
        [$invitacion_ok, $invitacion_mensaje] = wp_bot_iniciar_invitacion_cumpleanios($telefono, $datos);
        if (!$invitacion_ok) {
            registrar_error_confirmacion_wp($ticket_id, $invitacion_mensaje);
            error_log("Error invitacion WP ticket {$ticket_id}: " . $invitacion_mensaje);
        }
    }

    return [true, 'Confirmacion enviada.'];
}

function crear_preferencia_senia($ticket_id, $cliente, $descripcion, $monto_senia, $pendiente_expira = null)
{
    global $mercadopago_statement_descriptor, $mercadopago_medios_pago_habilitados;

    $base_url = app_public_url();
    $expira_ts = $pendiente_expira ? strtotime($pendiente_expira) : strtotime('+' . RESERVA_PENDIENTE_MINUTOS . ' minutes');
    if ($expira_ts === false || $expira_ts <= time()) {
        $expira_ts = strtotime('+' . RESERVA_PENDIENTE_MINUTOS . ' minutes');
    }

    $payload = [
        'items' => [
            [
                'title' => $descripcion,
                'quantity' => 1,
                'currency_id' => 'ARS',
                'unit_price' => (float) $monto_senia,
            ],
        ],
        'payer' => [
            'name' => $cliente['NOMBRE'],
            'email' => $cliente['MAIL'],
        ],
        'external_reference' => (string) $ticket_id,
        'statement_descriptor' => $mercadopago_statement_descriptor,
        'back_urls' => [
            'success' => $base_url . '/mercadopago_retorno_cliente.php?ticket=' . $ticket_id . '&resultado=success',
            'failure' => $base_url . '/mercadopago_retorno_cliente.php?ticket=' . $ticket_id . '&resultado=failure',
            'pending' => $base_url . '/mercadopago_retorno_cliente.php?ticket=' . $ticket_id . '&resultado=pending',
        ],
        'notification_url' => $base_url . '/mercadopago_webhook.php',
        'auto_return' => 'approved',
        'expires' => true,
        'expiration_date_from' => date('c'),
        'expiration_date_to' => date('c', $expira_ts),
    ];

    $tipos_disponibles = [
        'account_money',
        'credit_card',
        'debit_card',
        'prepaid_card',
        'ticket',
        'bank_transfer',
        'atm',
        'digital_currency',
    ];
    $tipos_habilitados = array_values(array_intersect(
        $tipos_disponibles,
        is_array($mercadopago_medios_pago_habilitados ?? null)
            ? $mercadopago_medios_pago_habilitados
            : []
    ));
    if (!in_array('account_money', $tipos_habilitados, true)) {
        array_unshift($tipos_habilitados, 'account_money');
    }

    // Checkout Pro no permite excluir el saldo disponible en cuenta.
    $tipos_excluidos = array_values(array_diff(
        $tipos_disponibles,
        $tipos_habilitados,
        ['account_money']
    ));
    if ($tipos_excluidos) {
        $payload['payment_methods'] = [
            'excluded_payment_types' => array_map(
                static function ($tipo) {
                    return ['id' => $tipo];
                },
                $tipos_excluidos
            ),
        ];
    }

    // Este proposito es necesario para un checkout exclusivo con saldo MP,
    // pero impediria mostrar los demas medios cuando se combinan opciones.
    if ($tipos_habilitados === ['account_money']) {
        $payload['purpose'] = 'wallet_purchase';
    }

    return mercadopago_request('POST', '/checkout/preferences', $payload);
}

function consultar_pago_mercadopago($payment_id)
{
    return mercadopago_request('GET', '/v1/payments/' . urlencode($payment_id));
}

function buscar_pago_aprobado_mercadopago_por_referencia($external_reference)
{
    $external_reference = trim((string) $external_reference);
    if ($external_reference === '') {
        return [false, 'Referencia externa invalida.', null];
    }

    $query = http_build_query([
        'sort' => 'date_created',
        'criteria' => 'desc',
        'external_reference' => $external_reference,
        'status' => 'approved',
        'limit' => 10,
        'offset' => 0,
    ]);
    [$ok, $mensaje, $respuesta] = mercadopago_request('GET', '/v1/payments/search?' . $query);
    if (!$ok) {
        return [false, $mensaje, null];
    }

    foreach (($respuesta['results'] ?? []) as $pago) {
        if (
            (string) ($pago['external_reference'] ?? '') === $external_reference
            && ($pago['status'] ?? '') === 'approved'
            && !empty($pago['id'])
        ) {
            return [true, '', $pago];
        }
    }

    return [true, 'No se encontro un pago aprobado para la referencia.', null];
}

function reconciliar_reservas_pendientes_mercadopago($limite = 50)
{
    global $con;

    $limite = max(1, min(100, (int) $limite));
    $conteo = $con->query("SELECT COUNT(*) AS total FROM ticket WHERE ESTADO_RESERVA = 'pendiente_pago'");
    $total_pendientes = (int) ($conteo?->fetch_assoc()['total'] ?? 0);
    $stmt = $con->prepare("SELECT _id FROM ticket WHERE ESTADO_RESERVA = 'pendiente_pago' ORDER BY _id ASC LIMIT ?");
    if (!$stmt) {
        return ['revisadas' => 0, 'confirmadas' => 0, 'errores' => ['No se pudo consultar reservas pendientes: ' . mysqli_error($con)]];
    }
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    $pendientes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $resultado = ['revisadas' => 0, 'confirmadas' => 0, 'errores' => []];
    if ($total_pendientes > $limite) {
        $resultado['errores'][] = "Hay {$total_pendientes} reservas pendientes y solo se pueden revisar {$limite} por ejecucion.";
    }
    foreach ($pendientes as $pendiente) {
        $ticket_id = (int) $pendiente['_id'];
        $resultado['revisadas']++;

        [$ok, $mensaje, $pago] = buscar_pago_aprobado_mercadopago_por_referencia($ticket_id);
        if (!$ok) {
            $error = "Ticket {$ticket_id}: {$mensaje}";
            $resultado['errores'][] = $error;
            error_log('Error conciliacion Mercado Pago: ' . $error);
            continue;
        }
        if (!$pago) {
            continue;
        }

        $procesado = aplicar_pago_senia($ticket_id, (string) $pago['id'], (string) ($pago['status'] ?? ''), $pago);
        $stmt = $con->prepare('SELECT ESTADO_RESERVA FROM ticket WHERE _id = ? LIMIT 1');
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        $estado = $stmt->get_result()->fetch_assoc()['ESTADO_RESERVA'] ?? '';
        $stmt->close();

        if ($estado === 'confirmada' && $procesado) {
            $resultado['confirmadas']++;
        } else {
            $detalle = $estado !== 'confirmada'
                ? "la reserva quedo en estado {$estado}"
                : 'no se pudo enviar la confirmacion por WhatsApp';
            $error = "Ticket {$ticket_id}: Mercado Pago informa pago aprobado, pero {$detalle}.";
            $resultado['errores'][] = $error;
            error_log('Error conciliacion Mercado Pago: ' . $error);
        }
    }

    return $resultado;
}

function reintentar_confirmaciones_wp_pendientes($limite = 50)
{
    global $con;

    $resultado = ['revisadas' => 0, 'enviadas' => 0, 'errores' => []];
    if (!asegurar_columnas_confirmacion_wp()) {
        $resultado['errores'][] = 'No se pudieron preparar las columnas de confirmacion por WhatsApp.';
        return $resultado;
    }

    $limite = max(1, min(100, (int) $limite));
    $stmt = $con->prepare("SELECT _id FROM ticket WHERE ESTADO_RESERVA = 'confirmada' AND WP_CONFIRMACION_ENVIADA = 0 AND WP_CONFIRMACION_ERROR IS NOT NULL AND WP_CONFIRMACION_ERROR <> '' ORDER BY _id ASC LIMIT ?");
    if (!$stmt) {
        $resultado['errores'][] = 'No se pudieron consultar las confirmaciones pendientes: ' . mysqli_error($con);
        return $resultado;
    }
    $stmt->bind_param('i', $limite);
    $stmt->execute();
    $tickets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($tickets as $ticket) {
        $ticket_id = (int) $ticket['_id'];
        $resultado['revisadas']++;
        [$ok, $mensaje] = enviar_confirmacion_wp_bot($ticket_id);
        if ($ok) {
            $resultado['enviadas']++;
            continue;
        }

        $error = "Ticket {$ticket_id}: {$mensaje}";
        $resultado['errores'][] = $error;
        error_log('Error reintentando confirmacion WP: ' . $error);
    }

    return $resultado;
}

function validar_pago_aprobado_para_ticket($ticket_id, $pago)
{
    global $con;

    if (!is_array($pago)) {
        return [false, 'Mercado Pago no devolvio los datos del pago.'];
    }

    $stmt = $con->prepare('SELECT MP_SENIA FROM ticket WHERE _id = ? LIMIT 1');
    if (!$stmt) {
        return [false, 'No se pudo consultar el importe de la reserva.'];
    }
    $ticket_id = (int) $ticket_id;
    $stmt->bind_param('i', $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        return [false, 'No existe la reserva indicada por el pago.'];
    }
    if ((string) ($pago['external_reference'] ?? '') !== (string) $ticket_id) {
        return [false, 'La referencia externa del pago no coincide con la reserva.'];
    }
    if (($pago['status'] ?? '') !== 'approved') {
        return [false, 'El pago todavia no esta aprobado.'];
    }
    if (($pago['currency_id'] ?? '') !== 'ARS') {
        return [false, 'La moneda del pago no coincide con la reserva.'];
    }

    $esperado = round((float) $ticket['MP_SENIA'], 2);
    $pagado = round((float) ($pago['transaction_amount'] ?? -1), 2);
    if (abs($esperado - $pagado) > 0.009) {
        return [false, "El importe acreditado ({$pagado}) no coincide con la senia esperada ({$esperado})."];
    }

    return [true, ''];
}

function aplicar_pago_senia($ticket_id, $payment_id, $status, $pago = null)
{
    global $con;

    $ticket_id = (int) $ticket_id;
    $payment_id = (string) $payment_id;
    $status = (string) $status;

    if ($status !== 'approved') {
        $stmt = $con->prepare("UPDATE ticket SET MP_PAYMENT_ID = ?, MP_STATUS = ? WHERE _id = ?");
        $stmt->bind_param("ssi", $payment_id, $status, $ticket_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }

    if ($pago === null) {
        [$consulta_ok, $consulta_mensaje, $pago] = consultar_pago_mercadopago($payment_id);
        if (!$consulta_ok) {
            error_log("No se pudo validar el pago {$payment_id}: {$consulta_mensaje}");
            return false;
        }
    }
    [$pago_valido, $pago_error] = validar_pago_aprobado_para_ticket($ticket_id, $pago);
    if (!$pago_valido) {
        error_log("Pago {$payment_id} invalido para ticket {$ticket_id}: {$pago_error}");
        return false;
    }

    $con->begin_transaction();

    asegurar_columna_expiracion_reserva($con);
    $stmt = $con->prepare("SELECT id_CLIENTE, id_TURNO, MP_SENIA, MP_PAYMENT_ID, SENIA, TOTAL, ESTADO_RESERVA FROM ticket WHERE _id = ? FOR UPDATE");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        $con->rollback();
        return false;
    }

    if ($ticket['ESTADO_RESERVA'] !== 'pendiente_pago' && $ticket['ESTADO_RESERVA'] !== 'confirmada') {
        $con->rollback();
        return false;
    }

    $monto_senia = (float) $ticket['MP_SENIA'];
    $senia_actual = (float) $ticket['SENIA'];
    $id_cliente = (int) $ticket['id_CLIENTE'];
    $id_turno = (int) $ticket['id_TURNO'];

    $recibe = 'Mercado Pago';
    $stmt = $con->prepare("SELECT _id FROM senias WHERE id_TURNO = ? AND MONTO = ? AND TRANSFERENCIA = ? AND RECIBE = ? LIMIT 1");
    $stmt->bind_param("idds", $id_turno, $monto_senia, $monto_senia, $recibe);
    $stmt->execute();
    $ya_registrado = (bool) $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total = (float) $ticket['TOTAL'];
    if (!$ya_registrado && $senia_actual <= 0) {
        $total = max(0, $total - $monto_senia);
    }

    $stmt = $con->prepare("UPDATE ticket SET SENIA = MP_SENIA, TOTAL = ?, ESTADO_RESERVA = 'confirmada', MP_PAYMENT_ID = ?, MP_STATUS = ? WHERE _id = ?");
    $stmt->bind_param("dssi", $total, $payment_id, $status, $ticket_id);
    $stmt->execute();
    $stmt->close();

    if (!$ya_registrado && $monto_senia > 0) {
        $fecha = date('d-m-Y H:i:s');
        $efectivo = 0;
        $transferencia = $monto_senia;
        $deja = 'Cliente web';

        $stmt = $con->prepare("INSERT INTO senias (MONTO, FECHA, id_CLIENTE, id_TURNO, EFECTIVO, TRANSFERENCIA, DEJA, RECIBE) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("dsiiddss", $monto_senia, $fecha, $id_cliente, $id_turno, $efectivo, $transferencia, $deja, $recibe);
        $stmt->execute();
        $stmt->close();
    }

    $con->commit();

    [$wp_ok, $wp_mensaje] = enviar_confirmacion_wp_bot($ticket_id);
    if (!$wp_ok) {
        error_log("Error al enviar confirmacion WP: " . $wp_mensaje);
    }

    return $wp_ok;
}

function cancelar_reserva_pendiente($ticket_id)
{
    global $con;

    $ticket_id = (int) $ticket_id;
    $stmt = $con->prepare("SELECT id_TURNO FROM ticket WHERE _id = ? AND ESTADO_RESERVA = 'pendiente_pago' LIMIT 1");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $ticket = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ticket) {
        return;
    }

    $turno_id = (int) $ticket['id_TURNO'];
    $stmt = $con->prepare("UPDATE ticket SET ESTADO_RESERVA = 'cancelada' WHERE _id = ?");
    $stmt->bind_param("i", $ticket_id);
    $stmt->execute();
    $stmt->close();

    $stmt = $con->prepare("DELETE FROM turnos WHERE _id = ?");
    $stmt->bind_param("i", $turno_id);
    $stmt->execute();
    $stmt->close();
}
?>
