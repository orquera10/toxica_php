<?php
// Conectar a la base de datos (configura tus propias credenciales)
require ("config.php");

// Verificar si se reciben los datos del formulario
if (isset($_POST['idEvento'], $_POST['idProducto'], $_POST['cantidad'])) {
    // Recoger los datos del formulario
    $idEvento = $_POST['idEvento'];
    $idProducto = $_POST['idProducto'];
    $cantidad = $_POST['cantidad'];

    // Consulta SQL para obtener el id_TICKET correspondiente al id_TURNO
    $sql_ticket = "SELECT _id FROM ticket WHERE id_TURNO = '$idEvento'";
    $result_ticket = mysqli_query($con, $sql_ticket);

    // Verificar si se encontró el id_TICKET
    if (mysqli_num_rows($result_ticket) > 0) {
        $row = mysqli_fetch_assoc($result_ticket);
        $idTicket = $row['_id'];

        // Consulta SQL para obtener el precio del producto
        $sql_precio_producto = "SELECT PRECIO FROM producto WHERE _id = '$idProducto'";
        $result_precio_producto = mysqli_query($con, $sql_precio_producto);

        // Verificar si se encontró el precio del producto
        if (mysqli_num_rows($result_precio_producto) > 0) {
            $row_precio_producto = mysqli_fetch_assoc($result_precio_producto);
            $precio_producto = $row_precio_producto['PRECIO'];

            // Consulta SQL para verificar si el producto ya existe en el ticket
            $sql_exist = "SELECT * FROM detalle_ticket WHERE id_TICKET = '$idTicket' AND id_PRODUCTO = '$idProducto'";
            $result_exist = mysqli_query($con, $sql_exist);

            // Si el producto ya existe en el ticket, aumentar la cantidad y actualizar el total
            if (mysqli_num_rows($result_exist) > 0) {
                $row_exist = mysqli_fetch_assoc($result_exist);
                $cantidad_existente = $row_exist['CANTIDAD'];

                // Calcular la nueva cantidad y el nuevo subtotal
                $nueva_cantidad = $cantidad_existente + $cantidad;

                // Actualizar la cantidad del producto en el ticket
                $sql_update = "UPDATE detalle_ticket SET CANTIDAD = '$nueva_cantidad' WHERE id_TICKET = '$idTicket' AND id_PRODUCTO = '$idProducto'";
                $result_update = mysqli_query($con, $sql_update);

                if ($result_update) {
                    // Actualizar el stock del producto
                    actualizarStockProducto($idProducto, -$cantidad, $con);

                    // Actualizar el total del ticket
                    actualizarTotalTicket($idTicket, $con);

                    $response = array("success" => true, "message" => "Cantidad del producto actualizada con éxito.");
                    echo json_encode($response);
                } else {
                    $response = array("success" => false, "message" => "Error al actualizar la cantidad del producto: " . mysqli_error($con));
                    echo json_encode($response);
                }
            } else {
                // El producto no existe en el ticket, insertarlo y actualizar el total
                $sql_insert = "INSERT INTO detalle_ticket (id_TICKET, id_PRODUCTO, PRECIO, CANTIDAD) VALUES ('$idTicket', '$idProducto', '$precio_producto', '$cantidad')";
                $result_insert = mysqli_query($con, $sql_insert);

                if ($result_insert) {
                    // Actualizar el stock del producto
                    actualizarStockProducto($idProducto, -$cantidad, $con);

                    // Actualizar el total del ticket
                    actualizarTotalTicket($idTicket, $con);

                    $response = array("success" => true, "message" => "Producto agregado al ticket con éxito.");
                    echo json_encode($response);
                } else {
                    $response = array("success" => false, "message" => "Error al agregar el producto al ticket: " . mysqli_error($con));
                    echo json_encode($response);
                }
            }
        } else {
            // Error: No se encontró el precio del producto
            $response = array("success" => false, "message" => "No se encontró el precio del producto.");
            echo json_encode($response);
        }
    } else {
        // Error: No se encontró el id_TICKET correspondiente al id_TURNO
        $response = array("success" => false, "message" => "No se encontró el ticket correspondiente al evento seleccionado.");
        echo json_encode($response);
    }

    // Cerrar la conexión
    mysqli_close($con);
} else {
    // Si no se reciben los datos del formulario, devolver un mensaje de error
    $response = array("success" => false, "message" => "No se recibieron los datos del formulario.");
    echo json_encode($response);
}

// Función para actualizar el stock del producto
function actualizarStockProducto($idProducto, $cantidad, $con)
{
    // Consulta SQL para obtener el stock actual del producto
    $sql_stock = "SELECT STOCK FROM producto WHERE _id = '$idProducto'";
    $result_stock = mysqli_query($con, $sql_stock);
    $fila_stock = mysqli_fetch_assoc($result_stock);
    $stock_actual = $fila_stock['STOCK'];

    // Calcular el nuevo stock
    $nuevo_stock = $stock_actual + $cantidad;

    // Actualizar el stock del producto en la base de datos
    $sql_update_stock_product = "UPDATE producto SET STOCK = '$nuevo_stock' WHERE _id = '$idProducto'";


    if ($con->query($sql_update_stock_product) === TRUE) {
        $fechaActual = date('d-m-Y');
        $fechaYHora = date('d-m-Y H:i');
        $stock = -$cantidad;
        // // Consulta para verificar si ya existe una entrada para la fecha seleccionada
        // $sql_verificar = "SELECT _id FROM `resumen_dias` WHERE `FECHA` = '$fechaActual'";
        // $resultado_verificar = mysqli_query($con, $sql_verificar);

        // // Verificar si la consulta devuelve filas
        // if (mysqli_num_rows($resultado_verificar) == 0) {
        //     // No hay entrada para esta fecha, proceder con la inserción
        //     $sql_insertar = "INSERT INTO `resumen_dias` (`FECHA`) VALUES ('$fechaActual')";
        //     mysqli_query($con, $sql_insertar);
        //     // Obtener el ID generado
        //     $idDia = mysqli_insert_id($con);
        // } else {
        //     // Ya existe una entrada en la tabla para la fecha seleccionada, obtener su ID
        //     $fila_resumen = mysqli_fetch_assoc($resultado_verificar);
        //     $idDia = $fila_resumen['_id'];
        // }

        $sql_update_stock = "INSERT INTO stock (id_PRODUCTO, FECHA, EGRESO, DETALLE) VALUES ('$idProducto', '$fechaYHora', '$stock', 'Producto agregado a un turno' )";
        if ($con->query($sql_update_stock) === FALSE) {
            echo json_encode(array("success" => false, "message" => "Error al agregar el stock: " . $con->error));
        }
    } else {
        echo json_encode(array("success" => false, "message" => "Error al agregar el stock: " . $con->error));
    }

}

// Función para actualizar el total del ticket
function actualizarTotalTicket($idTicket, $con)
{
    // Consulta SQL para obtener el total de los productos en el ticket
    $sql_total_productos = "SELECT SUM(producto.PRECIO * detalle_ticket.CANTIDAD) AS total 
                            FROM detalle_ticket 
                            INNER JOIN producto ON detalle_ticket.id_PRODUCTO = producto._id 
                            WHERE detalle_ticket.id_TICKET = '$idTicket'";
    $result_total_productos = mysqli_query($con, $sql_total_productos);
    $fila_total_productos = mysqli_fetch_assoc($result_total_productos);
    $total_productos = $fila_total_productos['total'];

    // Consulta SQL para obtener el total de la cancha en el ticket
    $sql_total_cancha = "SELECT TOTAL_CANCHA, EXTRA, SENIA FROM ticket WHERE _id = '$idTicket'";
    $result_total_cancha = mysqli_query($con, $sql_total_cancha);
    $fila_total_cancha = mysqli_fetch_assoc($result_total_cancha);
    $total_cancha = $fila_total_cancha['TOTAL_CANCHA'];
    $extra = $fila_total_cancha['EXTRA'];
    $senia = $fila_total_cancha['SENIA'];

    // Calcular el nuevo total del ticket
    $total_general = $total_cancha + $extra + $total_productos - $senia;

    // Actualizar el total del ticket en la base de datos
    $sql_update_total = "UPDATE ticket SET TOTAL_DETALLE = '$total_productos', TOTAL = '$total_general' WHERE _id = '$idTicket'";
    mysqli_query($con, $sql_update_total);
}
?>