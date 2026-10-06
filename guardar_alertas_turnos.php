<?php

require_once __DIR__ . '/config.php';
ob_start();
require_once __DIR__ . '/headerUsuario.php';
ob_end_clean();
require_once __DIR__ . '/reglas_reservas.php';

header('Content-Type: application/json; charset=utf-8');

function responder_alertas_turnos($status, $success, $message, $data = [])
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_alertas_turnos(405, false, 'Método no permitido.');
}

$entrada = trim((string) ($_POST['telefonos_alerta_reserva_hoy'] ?? ''));
$telefonos = [];

if ($entrada !== '') {
    $candidatos = preg_split('/[\r\n,;]+/', $entrada, -1, PREG_SPLIT_NO_EMPTY);

    foreach ($candidatos as $candidato) {
        $numero = preg_replace('/[^0-9]/', '', trim($candidato));
        if (strlen($numero) < 8 || strlen($numero) > 15) {
            responder_alertas_turnos(422, false, 'Revisá los teléfonos. Ingresá uno por línea y con código de área.');
        }
        if (!in_array($numero, $telefonos, true)) {
            $telefonos[] = $numero;
        }
    }
}

if (count($telefonos) > 20) {
    responder_alertas_turnos(422, false, 'Podés configurar hasta 20 teléfonos de alerta.');
}

if (!asegurar_configuracion_plazos_reserva($con)) {
    responder_alertas_turnos(500, false, 'No se pudo preparar la configuración de turnos.');
}

$telefonos_guardados = implode("\n", $telefonos);
$stmt = $con->prepare('UPDATE configuracion_reservas SET telefonos_alerta_reserva_hoy = ? WHERE id = 1');
if (!$stmt) {
    responder_alertas_turnos(500, false, 'No se pudo preparar la actualización.');
}

$stmt->bind_param('s', $telefonos_guardados);
$guardado = $stmt->execute();
$stmt->close();

if (!$guardado) {
    responder_alertas_turnos(500, false, 'No se pudieron guardar los teléfonos.');
}

responder_alertas_turnos(200, true, 'Teléfonos de alerta actualizados correctamente.', [
    'telefonos' => $telefonos,
]);
