<?php
// Incluir archivo de configuración de la base de datos
include 'config.php';

// Obtener los datos enviados por la solicitud AJAX
$idProducto = $_POST['id_producto'];
$egreso = $_POST['egreso'];
$idResumen = $_POST['id_resumen'];

// Consulta SQL para insertar los datos en la tabla de stock
$sql = "INSERT INTO stock (id_PRODUCTO, id_DIA, EGRESO) VALUES ('$idProducto', '$idResumen', '$egreso')";

// Ejecutar la consulta
if (mysqli_query($con, $sql)) {
    $response = array(
        'status' => 'success',
        'message' => 'Los datos se han insertado correctamente en la tabla de stock.'
    );
} else {
    $response = array(
        'status' => 'error',
        'message' => 'Error al insertar datos en la tabla de stock: ' . mysqli_error($con)
    );
}

// Cerrar la conexión a la base de datos
mysqli_close($con);

// Enviar respuesta JSON con SweetAlert
echo json_encode($response);
?>
