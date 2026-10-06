<?php

// Conexión a la base de datos
require_once 'config.php';

// Obtener los datos del formulario
$idProducto = $_POST['producto']; // Se recibe el ID del producto
$cantidad = $_POST['cantidad'];
$detalle = $_POST['detalle'];
$fechaDevolucionString = $_POST['fecha']; // Se recibe la fecha como string

// Formatear la fecha a d-m-Y
$fechaDevolucion = date('d-m-Y H:i', strtotime($fechaDevolucionString)); // Se utiliza la función date() y strtotime() para formatear la fecha

// Buscar el ID del día en la tabla resumen_dias
// $sql = "SELECT _id FROM resumen_dias WHERE FECHA = '$fechaDevolucion'";
// $resultado = $con->query($sql);

// if ($resultado->num_rows > 0) {
//     $fila = $resultado->fetch_assoc();
//     $idDia = $fila['_id']; // Obtener el ID del día encontrado
// } else {
//     // No se encontró el día, se inserta uno nuevo
//     $sql = "INSERT INTO resumen_dias (FECHA) VALUES ('$fechaDevolucion')";

//     if ($con->query($sql) === TRUE) {
//         $idDia = $con->insert_id; // Obtener el ID del día insertado
//     } else {
//         // Enviar mensaje de error al insertar el día
//         echo json_encode(array("estado" => "error", "mensaje" => "Error al insertar el día: " . $con->error));
//         exit; // Se detiene la ejecución del script si no se puede insertar el día
//     }
// }

// Registrar la devolución en la tabla stock
$sql = "INSERT INTO stock (id_PRODUCTO, FECHA, EGRESO, DETALLE) VALUES ($idProducto, '$fechaDevolucion', $cantidad, '$detalle')";

if ($con->query($sql) === TRUE) {
    // Actualizar el stock en la tabla productos
    $sql = "UPDATE producto SET STOCK = STOCK - $cantidad WHERE _id = $idProducto";

    if ($con->query($sql) === TRUE) {
        // Enviar mensaje de éxito
        echo json_encode(array("success" => true, "message" => "Devolución registrada exitosamente"));
    } else {
        // Enviar mensaje de error al actualizar el stock
        echo json_encode(array("success" => false, "message" => "Error al actualizar el stock: " . $con->error));
    }
} else {
    // Enviar mensaje de error al registrar la devolución
    echo json_encode(array("success" => false, "message" => "Error al registrar la devolución: " . $con->error));
}

// Cerrar la conexión a la base de datos
$con->close();

