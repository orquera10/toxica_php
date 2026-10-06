<?php
include 'config.php';

header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$nombre = $_POST['NOMBRE'] ?? null;
$usuario = $_POST['USUARIO'] ?? null;
$entrada = $_POST['HR_ENTRADA'] ?? null;
$salida = $_POST['HR_SALIDA'] ?? null;

if (!$id || !$nombre || !$usuario || !$entrada || !$salida) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios.']);
    exit;
}

// Validar que el usuario exista y sea tipo "user"
$check = mysqli_query($con, "SELECT * FROM usuarios WHERE _id = '$id' AND TIPO = 'user'");
if (mysqli_num_rows($check) === 0) {
    echo json_encode(['success' => false, 'message' => 'El usuario no existe o no se puede modificar.']);
    exit;
}

// Ejecutar el update
$query = "UPDATE usuarios SET 
    NOMBRE = ?,
    USUARIO = ?,
    HR_ENTRADA = ?,
    HR_SALIDA = ?
    WHERE _id = ?";

$stmt = mysqli_prepare($con, $query);
mysqli_stmt_bind_param($stmt, "ssssi", $nombre, $usuario, $entrada, $salida, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Empleado actualizado correctamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al actualizar el empleado.']);
}
?>