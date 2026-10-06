<?php
// Incluir archivo de configuración de la base de datos
include 'config.php';

// Definir un array para almacenar la respuesta
$response = array();

// Verificar si se recibió el ID del producto a eliminar
if (isset($_GET['id'])) {
    // Limpiar el ID del producto recibido
    $producto_id = mysqli_real_escape_string($con, $_GET['id']);

    // Consulta SQL para actualizar el campo VISIBLE a 0 (no visible)
    $sql = "UPDATE producto SET VISIBLE = 0 WHERE _id = '$producto_id'";

    // Ejecutar la consulta SQL
    if (mysqli_query($con, $sql)) {
        // Producto eliminado correctamente
        $response['success'] = true;
        $response['message'] = "Producto eliminado correctamente.";
    } else {
        // Error al eliminar el producto
        $response['success'] = false;
        $response['message'] = "Error al eliminar el producto: " . mysqli_error($con);
    }
} else {
    // No se recibió el ID del producto
    $response['success'] = false;
    $response['message'] = "No se recibió el ID del producto.";
}

// Enviar la respuesta como JSON
echo json_encode($response);
?>

