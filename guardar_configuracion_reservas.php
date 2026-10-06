<?php

require_once __DIR__ . '/config.php';
ob_start();
require_once __DIR__ . '/headerUsuario.php';
ob_end_clean();
require_once __DIR__ . '/reglas_reservas.php';

header('Content-Type: application/json; charset=utf-8');

function responder_configuracion_reservas($status, $success, $message, $data = [])
{
    http_response_code($status);
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder_configuracion_reservas(405, false, 'Método no permitido.');
}

$opciones = [
    'options' => [
        'min_range' => 1,
        'max_range' => 3650,
    ],
];
$dias_turnos = filter_var($_POST['dias_anticipacion_turnos'] ?? null, FILTER_VALIDATE_INT, $opciones);
$dias_cumpleanos = filter_var($_POST['dias_anticipacion_cumpleanos'] ?? null, FILTER_VALIDATE_INT, $opciones);

if ($dias_turnos === false || $dias_cumpleanos === false) {
    responder_configuracion_reservas(422, false, 'Los plazos deben ser números enteros entre 1 y 3650 días.');
}

if (!asegurar_configuracion_plazos_reserva($con)) {
    responder_configuracion_reservas(500, false, 'No se pudo preparar la configuración de reservas.');
}

$stmt = $con->prepare("INSERT INTO configuracion_reservas
    (id, dias_anticipacion_turnos, dias_anticipacion_cumpleanos)
    VALUES (1, ?, ?)
    ON DUPLICATE KEY UPDATE
        dias_anticipacion_turnos = VALUES(dias_anticipacion_turnos),
        dias_anticipacion_cumpleanos = VALUES(dias_anticipacion_cumpleanos)");

if (!$stmt) {
    responder_configuracion_reservas(500, false, 'No se pudo preparar la actualización.');
}

$stmt->bind_param('ii', $dias_turnos, $dias_cumpleanos);
$guardado = $stmt->execute();
$stmt->close();

if (!$guardado) {
    responder_configuracion_reservas(500, false, 'No se pudo guardar la configuración.');
}

responder_configuracion_reservas(200, true, 'Configuración de reservas actualizada correctamente.', [
    'dias_anticipacion_turnos' => $dias_turnos,
    'dias_anticipacion_cumpleanos' => $dias_cumpleanos,
]);
