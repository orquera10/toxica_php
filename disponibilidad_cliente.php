<?php
require_once 'cliente_auth.php';
require_once 'reglas_reservas.php';
require_once 'bloqueos_horarios.php';

requerir_cliente();
cancelar_reservas_pendientes_vencidas($con);
header('Content-Type: application/json; charset=utf-8');

$fecha_input = $_GET['fecha'] ?? '';
$id_cancha = (int) ($_GET['cancha'] ?? 0);
$duracion = duracion_reserva($id_cancha, (int) ($_GET['duracion'] ?? 1));

if (!$fecha_input || $id_cancha <= 0 || $duracion < 1 || $duracion > 4) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos.',
        'slots' => [],
    ]);
    exit;
}

$fecha_ts = strtotime($fecha_input);
if ($fecha_ts === false) {
    echo json_encode([
        'success' => false,
        'message' => 'Fecha invalida.',
        'slots' => [],
    ]);
    exit;
}

if (!reserva_dentro_del_plazo($fecha_input, $id_cancha)) {
    echo json_encode([
        'success' => false,
        'message' => mensaje_plazo_reserva($id_cancha),
        'slots' => [],
    ]);
    exit;
}

$stmt_cancha = $con->prepare("SELECT _id FROM canchas WHERE _id = ? AND _id != 9");
$stmt_cancha->bind_param("i", $id_cancha);
$stmt_cancha->execute();
$cancha_existe = $stmt_cancha->get_result()->num_rows > 0;
$stmt_cancha->close();

if (!$cancha_existe) {
    echo json_encode([
        'success' => false,
        'message' => 'Cancha no disponible.',
        'slots' => [],
    ]);
    exit;
}

$fecha = date('d-m-Y', $fecha_ts);
$slots = [];
$franjas_horario = limites_franjas_horario_cancha($con, $id_cancha, $fecha_input);
if (count($franjas_horario) === 0) { echo json_encode(['success' => true, 'slots' => []]); exit; }
$ahora = time();
$inicios_minutos = [];

if ($id_cancha === CANCHA_CUMPLE_ID) {
    foreach (horarios_inicio_cumpleanios_cliente($fecha_input) as $hora_cumple) {
        $partes_hora_cumple = explode(':', $hora_cumple);
        $inicios_minutos[] = ((int) $partes_hora_cumple[0] * 60) + (int) $partes_hora_cumple[1];
    }
} else {
    $inicios_minutos = inicios_en_horas_punto($franjas_horario, $duracion);
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

foreach ($inicios_minutos as $inicio_minutos) {
    $inicio_ts = strtotime($fecha_input . ' 00:00') + ($inicio_minutos * 60);
    $hora_inicio = date('H:i', $inicio_ts);
    $fin_minutos = $inicio_minutos + ($duracion * 60);
    $fin_ts = strtotime('+' . $duracion . ' hours', $inicio_ts);
    $hora_fin = date('H:i', $fin_ts);

    if (!reserva_en_horario_habil_cancha($con, $id_cancha, $fecha_input, $hora_inicio, $duracion)) {
        continue;
    }

    if ($inicio_ts === false || $inicio_ts <= $ahora) {
        continue;
    }

    $datetime_inicio = date('d-m-Y H:i', $inicio_ts);
    $datetime_fin = date('d-m-Y H:i', $fin_ts);

    $stmt = $con->prepare($sql_solapados);
    bind_parametros_solapamiento($stmt, $datetime_fin, $datetime_inicio, $id_cancha);
    $stmt->execute();
    $ocupado = $stmt->get_result()->num_rows > 0;
    $stmt->close();

    if (horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)) {
        continue;
    }

    if (!$ocupado) {
        $es_madrugada = date('Y-m-d', $inicio_ts) !== $fecha_input;
        $slots[] = [
            'inicio' => $hora_inicio,
            'fin' => $hora_fin,
            'fecha' => date('Y-m-d', $inicio_ts),
            'label' => $hora_inicio . ' a ' . $hora_fin . ($es_madrugada ? ' (madrugada)' : ''),
        ];
    }
}

echo json_encode([
    'success' => true,
    'slots' => $slots,
]);
?>

