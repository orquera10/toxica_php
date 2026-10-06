<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
ob_start();
require 'headerUsuario.php';
ob_end_clean();
require 'config.php';
require_once 'precios_canchas.php';
header('Content-Type: application/json; charset=utf-8');

function responder_precios($codigo, $mensaje, $success = false)
{
    http_response_code($codigo);
    echo json_encode(['success' => $success, 'message' => $mensaje], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder_precios(405, 'Método no permitido.');
if (empty($_SESSION['csrf_precios']) || !hash_equals($_SESSION['csrf_precios'], (string) ($_POST['csrf'] ?? ''))) {
    responder_precios(403, 'Recargá la página e intentá nuevamente.');
}
$id = filter_var($_POST['cancha_id'] ?? '', FILTER_VALIDATE_INT);
$reglas = json_decode($_POST['reglas'] ?? '', true);
if (!$id || !is_array($reglas) || count($reglas) > 100) responder_precios(422, 'Datos de precios inválidos.');
$validas = [];
foreach ($reglas as $regla) {
    if (!is_array($regla)) responder_precios(422, 'Franja inválida.');
    $dia = filter_var($regla['dia_semana'] ?? '', FILTER_VALIDATE_INT);
    $inicio = filter_var($regla['inicio'] ?? '', FILTER_VALIDATE_INT);
    $fin = filter_var($regla['fin'] ?? '', FILTER_VALIDATE_INT);
    $precio = filter_var($regla['precio'] ?? '', FILTER_VALIDATE_FLOAT);
    if (!$dia || $dia < 1 || $dia > 7 || $inicio === false || $fin === false || $inicio < 420 || $fin > 1560 || $inicio >= $fin || $inicio % 30 || $fin % 30 || $precio === false || !is_finite($precio) || $precio <= 0 || $precio > 9999999999.99 || abs($precio - round($precio, 2)) > 0.000001) {
        responder_precios(422, 'Revisá día, horario (07:00 a 02:00, cada 30 minutos) y precio mayor a cero.');
    }
    foreach ($validas as $otra) {
        if ($otra['dia'] === $dia && $inicio < $otra['fin'] && $fin > $otra['inicio']) responder_precios(422, 'Las franjas de un mismo día no pueden superponerse.');
    }
    $validas[] = compact('dia', 'inicio', 'fin', 'precio');
}
try {
    asegurar_tabla_precios_canchas($con);
    $con->begin_transaction();
    $stmt = $con->prepare('SELECT _id FROM canchas WHERE _id = ? AND _id <> 9 FOR UPDATE');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    if (!$stmt->get_result()->num_rows) {
        $con->rollback();
        responder_precios(404, 'Cancha no encontrada.');
    }
    $stmt = $con->prepare('DELETE FROM cancha_precios_horarios WHERE id_cancha = ?');
    $stmt->bind_param('i', $id);
    if (!$stmt->execute()) throw new RuntimeException();
    $stmt = $con->prepare('INSERT INTO cancha_precios_horarios (id_cancha, dia_semana, inicio, fin, precio) VALUES (?, ?, ?, ?, ?)');
    foreach ($validas as $r) {
        $stmt->bind_param('iiiid', $id, $r['dia'], $r['inicio'], $r['fin'], $r['precio']);
        if (!$stmt->execute()) throw new RuntimeException();
    }
    $con->commit();
    responder_precios(200, 'Precios por horario guardados.', true);
} catch (Throwable $e) {
    $con->rollback();
    responder_precios(500, 'No se pudieron guardar los precios.');
}
