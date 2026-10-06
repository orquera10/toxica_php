<?php
include('config.php');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_user = $_POST['id_USER'] ?? '';
    $fecha_original = $_POST['FECHA'] ?? '';
    $monto = $_POST['MONTO'] ?? '';

    // Validación básica
    if ($id_user && $fecha_original && $monto !== '') {
        // Convertir fecha de Y-m-d a d-m-Y
        $fecha_convertida = date('d-m-Y', strtotime($fecha_original));

        // Insertar en la base de datos
        $stmt = $con->prepare("INSERT INTO pago_empleado (id_USER, FECHA, MONTO) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $id_user, $fecha_convertida, $monto);

        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = 'Pago registrado correctamente.';
        } else {
            $response['message'] = 'Error al insertar el pago: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        $response['message'] = 'Faltan datos obligatorios.';
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
?>
