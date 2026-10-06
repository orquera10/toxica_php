<?php
include 'config.php'; // Asegurate que esto tenga la conexión a la BD

header('Content-Type: application/json');

// Verificar si se recibió el ID por GET
if (isset($_GET['id'])) {
    $id = mysqli_real_escape_string($con, $_GET['id']);

    // Actualizar el campo ACTIVO a 0
    $sql = "UPDATE usuarios SET ACTIVO = 0 WHERE _id = '$id'";
    $resultado = mysqli_query($con, $sql);

    if ($resultado) {
        echo json_encode([
            'success' => true,
            'message' => 'Empleado marcado como inactivo correctamente.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al actualizar el empleado.'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'ID de empleado no proporcionado.'
    ]);
}
?>