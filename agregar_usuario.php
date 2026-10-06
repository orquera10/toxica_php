<?php
include 'config.php';

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $hr_entrada = $_POST['hr_entrada'] ?? '';
    $hr_salida = $_POST['hr_salida'] ?? '';
    $clave = $_POST['password'] ?? '';

    if ($nombre && $usuario && $tipo && $clave) {
        // Verificar si el nombre de usuario ya existe
        $stmt_check = $con->prepare("SELECT _id FROM usuarios WHERE USUARIO = ?");
        $stmt_check->bind_param("s", $usuario);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $response['message'] = 'El nombre de usuario ya existe.';
        } else {
            $claveHash = password_hash($clave, PASSWORD_BCRYPT);
            $stmt = $con->prepare("INSERT INTO usuarios (NOMBRE, USUARIO, TIPO, HR_ENTRADA, HR_SALIDA, CLAVE, ACTIVO) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssss", $nombre, $usuario, $tipo, $hr_entrada, $hr_salida, $claveHash);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Usuario agregado exitosamente.';
            } else {
                $response['message'] = 'Error al insertar: ' . $stmt->error;
            }

            $stmt->close();
        }

        $stmt_check->close();
    } else {
        $response['message'] = 'Faltan campos obligatorios.';
    }
} else {
    $response['message'] = 'Método inválido.';
}

echo json_encode($response);
?>
