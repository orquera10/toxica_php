<?php
// Verificar si se recibieron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Incluir archivo de configuración de la base de datos
    include 'config.php';

    // Obtener los datos del formulario
    $idCliente = $_POST['idClienteModificar'];
    $nombreCliente = $_POST['nombreCliente'];
    $emailCliente = $_POST['emailCliente'];
    $telefonoCliente = $_POST['telefonoCliente'];

    // Preparar la consulta SQL para actualizar el cliente
    $sql = "UPDATE clientes SET NOMBRE=?, MAIL=?, TELEFONO=? WHERE _id=?";

    // Preparar la declaración
    $stmt = mysqli_prepare($con, $sql);

    // Vincular los parámetros con las variables
    mysqli_stmt_bind_param($stmt, "sssi", $nombreCliente, $emailCliente, $telefonoCliente, $idCliente);

    // Ejecutar la consulta
    if (mysqli_stmt_execute($stmt)) {
        $response = array(
            "success" => true,
            "message" => "Cliente modificado correctamente."
        );
    } else {
        $response = array(
            "success" => false,
            "message" => "Error al modificar el cliente: " . mysqli_error($con)
        );
    }

    // Cerrar la declaración y la conexión
    mysqli_stmt_close($stmt);
    mysqli_close($con);

    // Devolver la respuesta como JSON
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Si no se recibieron los datos por método POST, devolver un mensaje de error
    $response = array(
        "success" => false,
        "message" => "Error: método no permitido."
    );
    // Devolver la respuesta como JSON
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
