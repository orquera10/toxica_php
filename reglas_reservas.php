<?php
require_once __DIR__ . '/horarios_canchas.php';
require_once __DIR__ . '/precios_canchas.php';
const CANCHA_1_ID = 5;
const CANCHA_2_ID = 6;
const CANCHA_GRANDE_ID = 7;
const CANCHA_CUMPLE_ID = 8;
const CANCHA_PROMO_ID = 11;
const APERTURA_MINUTOS = 13 * 60;
const CIERRE_MINUTOS = 26 * 60;
const CIERRE_PROMO_MINUTOS = 17 * 60;
const APERTURA_CUMPLE_MINUTOS = 13 * 60;
const RESERVA_PENDIENTE_MINUTOS = 10;
const MAX_DIAS_ANTICIPACION_TURNO = 30;
const MAX_DIAS_ANTICIPACION_CUMPLE = 60;

function asegurar_configuracion_plazos_reserva($con)
{
    $sql = "CREATE TABLE IF NOT EXISTS configuracion_reservas (
        id TINYINT UNSIGNED NOT NULL,
        dias_anticipacion_turnos SMALLINT UNSIGNED NOT NULL DEFAULT 30,
        dias_anticipacion_cumpleanos SMALLINT UNSIGNED NOT NULL DEFAULT 60,
        telefonos_alerta_reserva_hoy TEXT NULL,
        actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if (!mysqli_query($con, $sql)) {
        error_log('No se pudo crear configuracion_reservas: ' . mysqli_error($con));
        return false;
    }

    $columna_telefonos = mysqli_query($con, "SHOW COLUMNS FROM configuracion_reservas LIKE 'telefonos_alerta_reserva_hoy'");
    if (!$columna_telefonos) {
        error_log('No se pudo verificar telefonos_alerta_reserva_hoy: ' . mysqli_error($con));
        return false;
    }

    if (mysqli_num_rows($columna_telefonos) === 0) {
        if (!mysqli_query($con, "ALTER TABLE configuracion_reservas ADD COLUMN telefonos_alerta_reserva_hoy TEXT NULL AFTER dias_anticipacion_cumpleanos")) {
            error_log('No se pudo agregar telefonos_alerta_reserva_hoy: ' . mysqli_error($con));
            return false;
        }
    }

    $sql = "INSERT IGNORE INTO configuracion_reservas
        (id, dias_anticipacion_turnos, dias_anticipacion_cumpleanos)
        VALUES (1, " . MAX_DIAS_ANTICIPACION_TURNO . ", " . MAX_DIAS_ANTICIPACION_CUMPLE . ")";

    if (!mysqli_query($con, $sql)) {
        error_log('No se pudo inicializar configuracion_reservas: ' . mysqli_error($con));
        return false;
    }

    return true;
}

function configuracion_plazos_reserva($conexion = null)
{
    static $configuracion = null;

    if ($configuracion !== null) {
        return $configuracion;
    }

    $configuracion = [
        'turnos' => MAX_DIAS_ANTICIPACION_TURNO,
        'cumpleanos' => MAX_DIAS_ANTICIPACION_CUMPLE,
        'telefonos_alerta_reserva_hoy' => '',
    ];

    if ($conexion === null) {
        global $con;
        $conexion = $con ?? null;
    }

    if (!$conexion || !asegurar_configuracion_plazos_reserva($conexion)) {
        return $configuracion;
    }

    $resultado = mysqli_query($conexion, "SELECT dias_anticipacion_turnos, dias_anticipacion_cumpleanos, telefonos_alerta_reserva_hoy FROM configuracion_reservas WHERE id = 1 LIMIT 1");
    $fila = $resultado ? mysqli_fetch_assoc($resultado) : null;

    if ($fila) {
        $configuracion['turnos'] = max(1, (int) $fila['dias_anticipacion_turnos']);
        $configuracion['cumpleanos'] = max(1, (int) $fila['dias_anticipacion_cumpleanos']);
        $configuracion['telefonos_alerta_reserva_hoy'] = trim((string) ($fila['telefonos_alerta_reserva_hoy'] ?? ''));
    }

    return $configuracion;
}

function nombre_cancha_cliente($id_cancha, $nombre_original = '')
{
    return match ((int) $id_cancha) {
        CANCHA_1_ID => 'Fútbol 5',
        CANCHA_2_ID => 'Fútbol 6',
        CANCHA_GRANDE_ID => 'Fútbol 7/8',
        default => (string) $nombre_original,
    };
}

function cancha_es_grande($id_cancha)
{
    return in_array((int) $id_cancha, [7, 8, 10, 11], true);
}

function cancha_es_chica($id_cancha)
{
    return in_array((int) $id_cancha, [5, 6], true);
}

function duracion_reserva($id_cancha, $duracion)
{
    if ((int) $id_cancha === CANCHA_CUMPLE_ID) {
        return 3;
    }

    return (int) $duracion;
}

function max_dias_anticipacion_reserva($id_cancha)
{
    $configuracion = configuracion_plazos_reserva();

    return (int) $id_cancha === CANCHA_CUMPLE_ID
        ? $configuracion['cumpleanos']
        : $configuracion['turnos'];
}

function reserva_dentro_del_plazo($fecha, $id_cancha, $hora_inicio = '')
{
    $fecha_operativa = fecha_operativa_reserva($fecha, $hora_inicio);
    $fecha_reserva = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_operativa);
    if (!$fecha_reserva || $fecha_reserva->format('Y-m-d') !== $fecha_operativa) {
        return false;
    }

    $hoy = new DateTimeImmutable('today');
    $fecha_maxima = $hoy->modify('+' . max_dias_anticipacion_reserva($id_cancha) . ' days');

    return $fecha_reserva >= $hoy && $fecha_reserva <= $fecha_maxima;
}

function mensaje_plazo_reserva($id_cancha)
{
    $dias = max_dias_anticipacion_reserva($id_cancha);

    return (int) $id_cancha === CANCHA_CUMPLE_ID
        ? "Los cumpleaños pueden reservarse con un máximo de {$dias} días de anticipación."
        : "Los turnos pueden reservarse con un máximo de {$dias} días de anticipación.";
}

function mensaje_horario_cumpleanios()
{
    return "Los cumpleaños se reservan por 3 horas, en horarios cada 30 minutos, desde las 13:00 y finalizando como maximo a las 02:00.";
}

function horarios_inicio_cumpleanios_cliente($fecha)
{
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return [];
    }

    $dia_semana = (int) date('N', $timestamp);

    if (in_array($dia_semana, [5, 6, 7], true)) {
        return ['13:00', '17:00', '21:00'];
    }

    if (in_array($dia_semana, [1, 2, 3, 4], true)) {
        return ['17:00', '21:00'];
    }

    return [];
}

function reserva_cumpleanios_cliente_permitida($fecha, $hora_inicio, $duracion)
{
    return (int) $duracion === 3
        && in_array($hora_inicio, horarios_inicio_cumpleanios_cliente($fecha), true);
}

function mensaje_horario_cumpleanios_cliente()
{
    return "Los cumpleanos desde clientes se reservan viernes, sabados y domingos de 13:00 a 16:00, 17:00 a 20:00 o 21:00 a 00:00; y lunes a jueves de 17:00 a 20:00 o 21:00 a 00:00.";
}

function terminos_reserva_cumpleanios()
{
    $dias_anticipacion = max_dias_anticipacion_reserva(CANCHA_CUMPLE_ID);

    return [
        'El horario de cumpleaños incluye el uso de cancha por 3 horas con el salón inferior, para hasta 30 personas entre adultos y niños. La persona adicional tiene costo adicional (consultar).',
        "Las reservas de cumpleaños pueden realizarse con un máximo de {$dias_anticipacion} días de anticipación.",
        'La seña es totalmente reembolsable, únicamente hasta 72 horas previas al evento.',
        'El salón estará disponible 15 minutos antes del horario establecido para el cumpleaños.',
        'El salón superior, o el uso de ambos salones, tiene costo adicional (consultar).',
        'No está permitido el ingreso de bebidas; solo se podrán consumir aquellas dispuestas a la venta en el salón.',
        'No está permitido el ingreso a la cocina, cuyo uso es exclusivo del personal.',
        'La vajilla y mantelería tienen costo adicional (consultar).',
        'No está permitido el ingreso de peloteros, camas elásticas o cualquier otro aparato o juego.',
        'No está permitido tirar papel picado ni usar piñatas dentro del espacio de cancha. Se cobrará adicional de limpieza en ese caso.',
        'En caso de exceder el horario, se cobrará el proporcional.',
    ];
}

function terminos_reserva_turnos()
{
    $dias_anticipacion = max_dias_anticipacion_reserva(CANCHA_1_ID);

    return [
        'La seña es no reembolsable y tampoco modificable.',
        "Las reservas de turnos pueden realizarse con un máximo de {$dias_anticipacion} días de anticipación.",
        'La tolerancia es de 10 minutos.',
        'La reserva de turno no habilita el uso de las instalaciones para cumpleaños, para lo cual debe contratarse un turno de cumpleaños por 3 horas.',
        'Está prohibido el ingreso de bebidas; solo se podrán consumir las expendidas en el lugar.',
        'Fútbol 5: 10 jugadores.',
        'Fútbol 6: 12 jugadores.',
        'Fútbol 7/8: 16 jugadores.',
        'Si exceden la cantidad de jugadores de la cancha reservada, se abona extra por jugador adicional.',
        'La seña de Fútbol 7/8 no puede pasar a Fútbol 5 o Fútbol 6 por falta de jugadores.',
        'Se proveen pecheras para un equipo y pelota de medio pique.',
        'No están permitidos los botines con tapones.',
        'No está permitido el uso de pelotas que no sean las de medio pique.',
        'La casa se reserva el derecho de admisión y permanencia.',
        'La casa se reserva el derecho de cancelación de la reserva por cualquier motivo de fuerza mayor, en cuyo caso reintegra la seña.',
    ];
}

function terminos_reserva($id_cancha)
{
    return (int) $id_cancha === CANCHA_CUMPLE_ID
        ? terminos_reserva_cumpleanios()
        : terminos_reserva_turnos();
}

function fecha_operativa_reserva($fecha, $hora_inicio = '')
{
    if (in_array($hora_inicio, ['00:00', '00:30', '01:00', '01:30'], true)) {
        return date('Y-m-d', strtotime($fecha . ' -1 day'));
    }

    return $fecha;
}

function porcentaje_minimo_senia($con, $id_cancha, $fecha, $hora_inicio = '')
{
    $fecha_operativa = fecha_operativa_reserva($fecha, $hora_inicio);
    $timestamp = strtotime($fecha_operativa);
    if ($timestamp === false) {
        return 0.50;
    }

    $dia_semana = (int) date('N', $timestamp);
    $horario = horario_cancha_para_fecha($con, $id_cancha, $fecha_operativa);
    if ($horario && isset($horario['porcentaje_senia'])) {
        $porcentaje = (float) $horario['porcentaje_senia'];
        if ($porcentaje >= 1 && $porcentaje <= 100) return $porcentaje / 100;
    }

    if ((int) $id_cancha === CANCHA_CUMPLE_ID) return 0.30;

    if (in_array($dia_semana, [2, 3, 4], true)) {
        return 0.50;
    }

    return 0.30;
}

function minimo_senia_reserva($con, $total_cancha, $id_cancha, $fecha, $hora_inicio = '')
{
    return round(((float) $total_cancha) * porcentaje_minimo_senia($con, $id_cancha, $fecha, $hora_inicio), 2);
}

function horario_en_intervalo_30($hora)
{
    $partes = explode(':', $hora);
    if (count($partes) < 2) {
        return false;
    }

    $minutos = (int) $partes[1];
    return $minutos === 0 || $minutos === 30;
}

function horario_en_hora_punto($hora)
{
    $partes = explode(':', $hora);
    if (count($partes) < 2) {
        return false;
    }

    return (int) $partes[1] === 0;
}

function inicios_en_horas_punto($franjas, $duracion)
{
    $inicios = [];
    $duracion_minutos = (int) $duracion * 60;

    foreach ($franjas as $franja) {
        $primer_inicio = (int) (ceil($franja['apertura'] / 60) * 60);
        for ($inicio = $primer_inicio; $inicio + $duracion_minutos <= $franja['cierre']; $inicio += 60) {
            $inicios[] = $inicio;
        }
    }

    $inicios = array_values(array_unique($inicios));
    sort($inicios);

    return $inicios;
}

function minutos_desde_apertura($hora)
{
    $partes = explode(':', $hora);
    if (count($partes) < 2) {
        return null;
    }

    $minutos = ((int) $partes[0] * 60) + (int) $partes[1];
    if ($minutos < APERTURA_MINUTOS) {
        $minutos += 24 * 60;
    }

    return $minutos;
}

function reserva_dentro_del_horario($hora_inicio, $duracion)
{
    $inicio_minutos = minutos_desde_apertura($hora_inicio);
    if ($inicio_minutos === null) {
        return false;
    }

    return $inicio_minutos >= APERTURA_MINUTOS && ($inicio_minutos + ((int) $duracion * 60)) <= CIERRE_MINUTOS;
}

function reserva_cumpleanios_dentro_del_horario($hora_inicio, $duracion)
{
    $partes = explode(':', $hora_inicio);
    if (count($partes) < 2) {
        return false;
    }

    $inicio_minutos = ((int) $partes[0] * 60) + (int) $partes[1];
    if ($inicio_minutos < APERTURA_CUMPLE_MINUTOS) {
        $inicio_minutos += 24 * 60;
    }

    return $inicio_minutos >= APERTURA_CUMPLE_MINUTOS && ($inicio_minutos + ((int) $duracion * 60)) <= CIERRE_MINUTOS;
}

function reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, $duracion, $fecha = '')
{
    global $con;
    if ($fecha !== '' && isset($con)) {
        $fecha_operativa = fecha_operativa_reserva($fecha, $hora_inicio);
        $habilitada = reserva_en_horario_habil_cancha($con, $id_cancha, $fecha_operativa, $hora_inicio, $duracion);
        if ((int) $id_cancha !== CANCHA_CUMPLE_ID) return $habilitada;
        if (!$habilitada) return false;
    }
    $inicio_minutos = minutos_desde_apertura($hora_inicio);
    if ($inicio_minutos === null) {
        return false;
    }

    if ((int) $id_cancha === CANCHA_CUMPLE_ID) {
        return (int) $duracion === 3 && reserva_cumpleanios_dentro_del_horario($hora_inicio, $duracion);
    }

    if ((int) $id_cancha === CANCHA_PROMO_ID) {
        return $inicio_minutos >= APERTURA_MINUTOS && ($inicio_minutos + ((int) $duracion * 60)) <= CIERRE_PROMO_MINUTOS;
    }

    return reserva_dentro_del_horario($hora_inicio, $duracion);
}

function condicion_solapamiento_canchas_sql()
{
    return "(
        t.id_CANCHA = ?
        OR (? IN (5, 6) AND c._id IN (7, 8, 10, 11))
        OR (? IN (7, 8, 10, 11) AND c._id IN (5, 6, 7, 8, 10, 11))
    )";
}

function bind_parametros_solapamiento($stmt, $datetime_fin, $datetime_inicio, $id_cancha)
{
    $stmt->bind_param(
        "ssiii",
        $datetime_fin,
        $datetime_inicio,
        $id_cancha,
        $id_cancha,
        $id_cancha
    );
}

function asegurar_columna_expiracion_reserva($con)
{
    $resultado = mysqli_query($con, "SHOW COLUMNS FROM ticket LIKE 'PENDIENTE_EXPIRA'");
    if ($resultado && mysqli_num_rows($resultado) > 0) {
        mysqli_query($con, "UPDATE ticket SET PENDIENTE_EXPIRA = DATE_ADD(NOW(), INTERVAL " . RESERVA_PENDIENTE_MINUTOS . " MINUTE) WHERE ESTADO_RESERVA = 'pendiente_pago' AND PENDIENTE_EXPIRA IS NULL");
        return true;
    }

    if (!mysqli_query($con, "ALTER TABLE ticket ADD COLUMN PENDIENTE_EXPIRA DATETIME NULL, ADD INDEX idx_ticket_pendiente_expira (ESTADO_RESERVA, PENDIENTE_EXPIRA)")) {
        error_log("No se pudo crear columna PENDIENTE_EXPIRA en ticket: " . mysqli_error($con));
        return false;
    }

    mysqli_query($con, "UPDATE ticket SET PENDIENTE_EXPIRA = DATE_ADD(NOW(), INTERVAL " . RESERVA_PENDIENTE_MINUTOS . " MINUTE) WHERE ESTADO_RESERVA = 'pendiente_pago' AND PENDIENTE_EXPIRA IS NULL");
    return true;
}

function cancelar_reservas_pendientes_vencidas($con)
{
    if (!asegurar_columna_expiracion_reserva($con)) {
        return 0;
    }

    $stmt = $con->prepare("SELECT _id, id_TURNO FROM ticket WHERE ESTADO_RESERVA = 'pendiente_pago' AND COALESCE(SENIA, 0) <= 0 AND PENDIENTE_EXPIRA IS NOT NULL AND PENDIENTE_EXPIRA <= NOW()");
    if (!$stmt) {
        error_log("No se pudo preparar limpieza de reservas vencidas: " . mysqli_error($con));
        return 0;
    }

    $stmt->execute();
    $resultado = $stmt->get_result();
    $vencidas = $resultado->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (count($vencidas) === 0) {
        return 0;
    }

    $con->begin_transaction();
    $canceladas = 0;

    foreach ($vencidas as $reserva) {
        $ticket_id = (int) $reserva['_id'];
        $turno_id = (int) $reserva['id_TURNO'];

        $stmt = $con->prepare("UPDATE ticket SET ESTADO_RESERVA = 'cancelada' WHERE _id = ? AND ESTADO_RESERVA = 'pendiente_pago' AND COALESCE(SENIA, 0) <= 0");
        $stmt->bind_param("i", $ticket_id);
        $stmt->execute();
        $actualizado = $stmt->affected_rows === 1;
        $stmt->close();

        if (!$actualizado) {
            continue;
        }

        $stmt = $con->prepare("DELETE FROM turnos WHERE _id = ?");
        $stmt->bind_param("i", $turno_id);
        $stmt->execute();
        $stmt->close();

        $canceladas++;
    }

    $con->commit();
    return $canceladas;
}?>

