<?php
// Incluir archivo de configuración de la base de datos
include 'config.php';

// Verificar si se recibió el ID del cliente por GET
if (isset($_GET['id'])) {
    // Obtener el ID del cliente desde la solicitud
    $idCliente = $_GET['id'];

    // Actualizar el atributo VISIBLE del cliente en la base de datos
    $sql = "UPDATE clientes SET VISIBLE = 0 WHERE _id = $idCliente"; // Cambiar el 0 por 1 si deseas ocultarlo
    if (mysqli_query($con, $sql)) {
        // Si la consulta se realizó correctamente, enviar una respuesta JSON de éxito
        $response = array(
            'success' => true,
            'message' => 'Cliente eliminado correctamente.'
        );
        echo json_encode($response);
        exit;
    } else {
        // Si hubo un error en la consulta, enviar una respuesta JSON de error
        $response = array(
            'success' => false,
            'message' => 'Error al eliminar el cliente.'
        );
        echo json_encode($response);
        exit;
    }
} else {
    // Si no se recibió el ID del cliente, enviar una respuesta JSON de error
    $response = array(
        'success' => false,
        'message' => 'Error: No se recibió el ID del cliente.'
    );
    echo json_encode($response);
    exit;
}
?>
