<?php

include 'config.php';

// Obtener la fecha que llega por POST
$fecha_entrada_raw = isset($_POST['fecha']) ? $_POST['fecha'] : null;
// Obtener el detalle que llega por POST
$detalle = isset($_POST['detalle']) ? trim($_POST['detalle']) : '';

// Formatear la fecha de entrada si llegó
if ($fecha_entrada_raw) {
    $timestamp = strtotime($fecha_entrada_raw);
    if ($timestamp === false) {
        $fechayHora = date('d-m-Y H:i');
    } else {
        $fechayHora = date('d-m-Y H:i', $timestamp);
    }
} else {
    $fechayHora = date('d-m-Y H:i');
}

// Obtener los datos del formulario
$id_producto = $_POST['id_producto'];
$cantidad = $_POST['cantidad'];

// Si detalle está vacío, completar según cantidad positiva o negativa
if ($detalle === '') {
    if ($cantidad < 0) {
        $detalle = 'Se quitó stock a un producto';
    } else {
        $detalle = 'Se agregó stock a un producto';
    }
}

// Verificar si la cantidad es negativa
if ($cantidad < 0) {
    $cantidad = abs($cantidad);
    $sql_insertar_stock = "INSERT INTO stock (FECHA, id_PRODUCTO, EGRESO, DETALLE) VALUES ('$fechayHora', '$id_producto', '$cantidad', '".$con->real_escape_string($detalle)."')";
    $sql_insertar_stock_producto = "UPDATE producto SET STOCK = STOCK - $cantidad WHERE _id = $id_producto";
} else {
    $sql_insertar_stock = "INSERT INTO stock (FECHA, id_PRODUCTO, INGRESO, DETALLE) VALUES ('$fechayHora', '$id_producto', '$cantidad', '".$con->real_escape_string($detalle)."')";
    $sql_insertar_stock_producto = "UPDATE producto SET STOCK = STOCK + $cantidad WHERE _id = $id_producto";
}

if ($con->query($sql_insertar_stock) === TRUE) {
    if ($con->query($sql_insertar_stock_producto) === TRUE) {
        echo "El stock se agregó correctamente";
    } else {
        echo "Error al agregar el stock: " . $con->error;
    }
} else {
    echo "Error al agregar el stock: " . $con->error;
}

$con->close();

?>