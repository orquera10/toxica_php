<?php
require_once 'cliente_auth.php';
require_once 'reglas_reservas.php';
require_once 'mercadopago_utils.php';
require_once 'bloqueos_horarios.php';

$cliente = requerir_cliente();
cancelar_reservas_pendientes_vencidas($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_reservas.php");
    exit;
}

if (($_POST['acepta_terminos_reserva'] ?? '') !== '1') {
    header("Location: cliente_reservas.php?error=" . urlencode("Debes aceptar los terminos y condiciones para continuar."));
    exit;
}

$fecha_input = $_POST['fecha_reserva'] ?? '';
if ($fecha_input === '') {
    $fecha_input = $_POST['fecha'] ?? '';
    $minutos_inicio = minutos_desde_apertura($_POST['hora_inicio'] ?? '');
    if ($fecha_input !== '' && $minutos_inicio !== null && $minutos_inicio >= 24 * 60) {
        $fecha_input = date('Y-m-d', strtotime($fecha_input . ' +1 day'));
    }
}
$hora_inicio = $_POST['hora_inicio'] ?? '';
$id_cancha = (int) ($_POST['cancha'] ?? 0);
$duracion = duracion_reserva($id_cancha, (int) ($_POST['duracion'] ?? 1));

if (!$fecha_input || !$hora_inicio || $duracion < 1 || $duracion > 4 || $id_cancha <= 0) {
    header("Location: cliente_reservas.php?error=" . urlencode("Completa todos los datos de la reserva."));
    exit;
}

if (!reserva_dentro_del_plazo($fecha_input, $id_cancha, $hora_inicio)) {
    header("Location: cliente_reservas.php?error=" . urlencode(mensaje_plazo_reserva($id_cancha)));
    exit;
}

if (!horario_en_hora_punto($hora_inicio)) {
    header("Location: cliente_reservas.php?error=" . urlencode("Los turnos deben comenzar en horas en punto."));
    exit;
}

$reserva_horario_valida = $id_cancha === CANCHA_CUMPLE_ID
    ? reserva_cumpleanios_cliente_permitida($fecha_input, $hora_inicio, $duracion) && reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, $duracion, $fecha_input)
    : reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, $duracion, $fecha_input);

if (!$reserva_horario_valida) {
    $mensaje_horario = $id_cancha === CANCHA_CUMPLE_ID
        ? mensaje_horario_cumpleanios_cliente()
        : ($id_cancha === CANCHA_PROMO_ID
        ? "La cancha promo solo se puede reservar entre las 13:00 y las 17:00."
        : "Los turnos pueden reservarse desde las 13:00 y finalizar como maximo a las 02:00.");
    header("Location: cliente_reservas.php?error=" . urlencode($mensaje_horario));
    exit;
}

$inicio_ts = strtotime($fecha_input . ' ' . $hora_inicio);
if ($inicio_ts === false || $inicio_ts < time()) {
    header("Location: cliente_reservas.php?error=" . urlencode("Elige una fecha y horario futuro."));
    exit;
}

$fin_ts = strtotime('+' . $duracion . ' hours', $inicio_ts);
$fecha = date('d-m-Y', $inicio_ts);
$hora_inicio = date('H:i', $inicio_ts);
$hora_fin = date('H:i', $fin_ts);
$fecha_fin = date('d-m-Y', $fin_ts);
$datetime_inicio = $fecha . ' ' . $hora_inicio;
$datetime_fin = $fecha_fin . ' ' . $hora_fin;

$stmt = $con->prepare("SELECT PRECIO, NOMBRE FROM canchas WHERE _id = ? AND _id != 9");
$stmt->bind_param("i", $id_cancha);
$stmt->execute();
$resultado_cancha = $stmt->get_result();
$cancha = $resultado_cancha->fetch_assoc();
$stmt->close();

if (!$cancha) {
    header("Location: cliente_reservas.php?error=" . urlencode("La cancha seleccionada no esta disponible."));
    exit;
}

$sql_solapados = "SELECT t._id
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

$stmt = $con->prepare($sql_solapados);
bind_parametros_solapamiento($stmt, $datetime_fin, $datetime_inicio, $id_cancha);
$stmt->execute();
$solapados = $stmt->get_result()->num_rows;
$stmt->close();

if ($solapados > 0) {
    header("Location: cliente_reservas.php?error=" . urlencode("Ese horario ya esta ocupado. Prueba con otro turno."));
    exit;
}

if (horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)) {
    header("Location: cliente_reservas.php?error=" . urlencode("Ese horario esta bloqueado. Prueba con otro turno."));
    exit;
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

$total_cancha = precio_total_reserva($con, $id_cancha, $cancha['PRECIO'], $fecha_input, $hora_inicio, $duracion);
// El importe se calcula siempre en el servidor y no depende de los datos
// enviados por el navegador.
$monto_senia = minimo_senia_reserva($con, $total_cancha, $id_cancha, $fecha_input, $hora_inicio);

$stmt = $con->prepare("INSERT INTO turnos (HORA_INICIO, HORA_FIN, COLOR, FECHA, id_CANCHA, FINALIZADO, VENTA, id_USUARIO) VALUES (?, ?, ?, ?, ?, 0, 0, NULL)");
$stmt->bind_param("ssssi", $hora_inicio, $hora_fin, $color_evento, $fecha, $id_cancha);
$ok_turno = $stmt->execute();
$id_turno = $stmt->insert_id;
$stmt->close();

if (!$ok_turno) {
    header("Location: cliente_reservas.php?error=" . urlencode("No se pudo guardar el turno."));
    exit;
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
    header("Location: cliente_reservas.php?error=" . urlencode("No se pudo generar el ticket de la reserva."));
    exit;
}

$descripcion = "Seña reserva " . nombre_cancha_cliente($id_cancha, $cancha['NOMBRE']) . " " . $fecha . " " . $hora_inicio . " a " . $hora_fin;
[$mp_ok, $mp_mensaje, $preferencia] = crear_preferencia_senia($id_ticket, $cliente, $descripcion, $monto_senia, $pendiente_expira);

if (!$mp_ok || empty($preferencia['init_point'])) {
    cancelar_reserva_pendiente($id_ticket);
    header("Location: cliente_reservas.php?error=" . urlencode($mp_mensaje ?: "No se pudo iniciar el pago de la seña."));
    exit;
}

$preference_id = $preferencia['id'] ?? '';
$stmt = $con->prepare("UPDATE ticket SET MP_PREFERENCE_ID = ? WHERE _id = ?");
$stmt->bind_param("si", $preference_id, $id_ticket);
$stmt->execute();
$stmt->close();

header("Location: " . $preferencia['init_point']);
exit;
?>



