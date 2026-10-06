<?php

include 'config.php';
ob_start();
include 'headerUsuario.php';
ob_end_clean();
require_once 'horarios_canchas.php';
header('Content-Type: application/json; charset=utf-8');

function responder_horarios($status, $success, $message)
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function hora_horario_valida($hora)
{
    return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hora) === 1;
}

function minutos_hora_franja($hora, $franja)
{
    [$horas, $minutos] = array_map('intval', explode(':', $hora));
    $total = ($horas * 60) + $minutos;
    if ($franja === 'noche' && $total < 7 * 60) {
        $total += 24 * 60;
    }
    return $total;
}

function horario_dentro_de_franja($franja, $apertura, $cierre)
{
    $limites = [
        'manana' => [7 * 60, 12 * 60],
        'tarde' => [12 * 60, 20 * 60],
        'noche' => [20 * 60, 26 * 60],
    ];
    [$minimo, $maximo] = $limites[$franja];
    $inicio = minutos_hora_franja($apertura, $franja);
    $fin = minutos_hora_franja($cierre, $franja);

    return $inicio >= $minimo && $fin <= $maximo && $inicio < $fin;
}

$id_cancha = (int) ($_POST['cancha_id'] ?? 0);
$dias = $_POST['dias'] ?? [];

if ($id_cancha <= 0 || !is_array($dias) || count($dias) !== 7) {
    responder_horarios(422, false, 'Datos de horarios incompletos.');
}

if (!asegurar_tabla_horarios_canchas($con)) {
    responder_horarios(500, false, 'No se pudo preparar la configuración.');
}

$stmt_cancha = $con->prepare('SELECT _id FROM canchas WHERE _id = ? AND _id <> 9');
$stmt_cancha->bind_param('i', $id_cancha);
$stmt_cancha->execute();
if ($stmt_cancha->get_result()->num_rows !== 1) {
    responder_horarios(404, false, 'Cancha no encontrada.');
}
$stmt_cancha->close();

$sql = "INSERT INTO cancha_horarios (
        id_cancha, dia_semana, habilitado, hora_apertura, hora_cierre, porcentaje_senia,
        manana_habilitada, hora_apertura_manana, hora_cierre_manana,
        tarde_habilitada, hora_apertura_tarde, hora_cierre_tarde,
        noche_habilitada, hora_apertura_noche, hora_cierre_noche
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        habilitado = VALUES(habilitado),
        hora_apertura = VALUES(hora_apertura),
        hora_cierre = VALUES(hora_cierre),
        porcentaje_senia = VALUES(porcentaje_senia),
        manana_habilitada = VALUES(manana_habilitada),
        hora_apertura_manana = VALUES(hora_apertura_manana),
        hora_cierre_manana = VALUES(hora_cierre_manana),
        tarde_habilitada = VALUES(tarde_habilitada),
        hora_apertura_tarde = VALUES(hora_apertura_tarde),
        hora_cierre_tarde = VALUES(hora_cierre_tarde),
        noche_habilitada = VALUES(noche_habilitada),
        hora_apertura_noche = VALUES(hora_apertura_noche),
        hora_cierre_noche = VALUES(hora_cierre_noche)";
$stmt = $con->prepare($sql);
$con->begin_transaction();

for ($dia = 1; $dia <= 7; $dia++) {
    $datos_dia = $dias[$dia] ?? [];
    $habilitado = isset($datos_dia['habilitado']) ? 1 : 0;
    $porcentaje = filter_var($datos_dia['porcentaje_senia'] ?? null, FILTER_VALIDATE_FLOAT);
    $franjas = [];
    $cantidad_habilitadas = 0;

    foreach (FRANJAS_HORARIAS_CANCHA as $nombre) {
        $datos_franja = $datos_dia['franjas'][$nombre] ?? [];
        $franja_habilitada = isset($datos_franja['habilitada']) ? 1 : 0;
        $apertura = substr((string) ($datos_franja['apertura'] ?? ''), 0, 5);
        $cierre = substr((string) ($datos_franja['cierre'] ?? ''), 0, 5);

        if (
            !hora_horario_valida($apertura)
            || !hora_horario_valida($cierre)
            || (
                $habilitado
                && $franja_habilitada
                && !horario_dentro_de_franja($nombre, $apertura, $cierre)
            )
        ) {
            $con->rollback();
            $rangos = ['manana' => '07:00 a 12:00', 'tarde' => '12:00 a 20:00', 'noche' => '20:00 a 02:00'];
            responder_horarios(422, false, 'La franja ' . $nombre . ' del día ' . $dia . ' debe estar dentro de ' . $rangos[$nombre] . '.');
        }

        $franjas[$nombre] = [
            'habilitada' => $franja_habilitada,
            'apertura' => $apertura,
            'cierre' => $cierre,
        ];
        $cantidad_habilitadas += $franja_habilitada;
    }

    if (
        $porcentaje === false
        || $porcentaje < 1
        || $porcentaje > 100
        || ($habilitado && $cantidad_habilitadas === 0)
    ) {
        $con->rollback();
        responder_horarios(422, false, 'Revisá las franjas y el porcentaje de seña del día ' . $dia . '.');
    }

    $primera_franja = null;
    foreach (FRANJAS_HORARIAS_CANCHA as $nombre) {
        if ($franjas[$nombre]['habilitada']) {
            $primera_franja = $franjas[$nombre];
            break;
        }
    }
    $primera_franja = $primera_franja ?? $franjas['tarde'];
    $apertura_legacy = $primera_franja['apertura'];
    $cierre_legacy = $primera_franja['cierre'];

    $manana_habilitada = $franjas['manana']['habilitada'];
    $apertura_manana = $franjas['manana']['apertura'];
    $cierre_manana = $franjas['manana']['cierre'];
    $tarde_habilitada = $franjas['tarde']['habilitada'];
    $apertura_tarde = $franjas['tarde']['apertura'];
    $cierre_tarde = $franjas['tarde']['cierre'];
    $noche_habilitada = $franjas['noche']['habilitada'];
    $apertura_noche = $franjas['noche']['apertura'];
    $cierre_noche = $franjas['noche']['cierre'];

    $stmt->bind_param(
        'iiissdissississ',
        $id_cancha,
        $dia,
        $habilitado,
        $apertura_legacy,
        $cierre_legacy,
        $porcentaje,
        $manana_habilitada,
        $apertura_manana,
        $cierre_manana,
        $tarde_habilitada,
        $apertura_tarde,
        $cierre_tarde,
        $noche_habilitada,
        $apertura_noche,
        $cierre_noche
    );

    if (!$stmt->execute()) {
        $stmt->close();
        $con->rollback();
        responder_horarios(500, false, 'No se pudieron guardar los horarios.');
    }
}

$stmt->close();
$con->commit();
responder_horarios(200, true, 'Horarios actualizados correctamente.');
