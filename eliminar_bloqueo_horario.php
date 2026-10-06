<?php
require_once 'config.php';
require_once 'bloqueos_horarios.php';

header('Content-Type: application/json');
asegurar_tabla_bloqueos_horarios($con);

$response = array('success' => false, 'message' => 'No se pudo eliminar el bloqueo.');

if (!usuario_interno_autenticado()) {
    $response['message'] = 'No tenes permisos para eliminar bloqueos.';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $response['message'] = 'No se recibio un ID valido.';
    echo json_encode($response);
    exit;
}

$id = (int) $_GET['id'];
$stmt = mysqli_prepare($con, "DELETE FROM bloqueos_horarios WHERE _id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
    $response['success'] = true;
    $response['message'] = 'Bloqueo eliminado correctamente.';
} else {
    $response['message'] = 'No se encontro el bloqueo.';
}

mysqli_stmt_close($stmt);
echo json_encode($response);
?>
