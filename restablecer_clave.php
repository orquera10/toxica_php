<?php
include 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'] ?? '';
    $nueva_clave = $_POST['nueva_clave'] ?? '';

    if ($id_usuario && $nueva_clave) {
        $claveHash = password_hash($nueva_clave, PASSWORD_BCRYPT);

        $stmt = $con->prepare("UPDATE usuarios SET CLAVE = ? WHERE _id = ?");
        $stmt->bind_param("si", $claveHash, $id_usuario);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Contraseña actualizada correctamente.';
        } else {
            $response['message'] = 'Error al actualizar la contraseña: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Datos incompletos.';
    }
} else {
    $response['message'] = 'Método inválido.';
}

echo json_encode($response);

