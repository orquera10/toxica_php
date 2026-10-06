<?php
include ('config.php');

// Verificar si se recibió el idProducto y el idEvento
if (isset($_POST['idProducto'], $_POST['idEvento'])) {
    $idProducto = $_POST['idProducto'];
    $idEvento = $_POST['idEvento'];

    // Consulta SQL para obtener el id_TICKET correspondiente al idEvento
    $sql_ticket = "SELECT _id, TOTAL_CANCHA, EXTRA, SENIA FROM ticket WHERE id_TURNO = $idEvento";

    // Ejecutar la consulta SQL
    $result_ticket = mysqli_query($con, $sql_ticket);

    // Verificar si se encontró el id_TICKET
    if ($result_ticket && mysqli_num_rows($result_ticket) > 0) {
        $row = mysqli_fetch_assoc($result_ticket);
        $idTicket = $row['_id'];
        $total_cancha = $row['TOTAL_CANCHA'];
        $extra = $row['EXTRA'];
        $senia = $row['SENIA'];


        // Consulta SQL para obtener la cantidad del producto en el detalle del ticket
        $sql_cantidad_producto = "SELECT CANTIDAD FROM detalle_ticket WHERE id_TICKET = $idTicket AND id_PRODUCTO = $idProducto";

        // Ejecutar la consulta SQL
        $result_cantidad_producto = mysqli_query($con, $sql_cantidad_producto);

        // Verificar si se encontró la cantidad del producto en el detalle del ticket
        if ($result_cantidad_producto && mysqli_num_rows($result_cantidad_producto) > 0) {
            $row_cantidad_producto = mysqli_fetch_assoc($result_cantidad_producto);
            $cantidad_producto = $row_cantidad_producto['CANTIDAD'];

            // Consulta SQL para eliminar el producto de la tabla detalle_ticket
            $sql_delete_producto = "DELETE FROM detalle_ticket WHERE id_TICKET = $idTicket AND id_PRODUCTO = $idProducto";

            // Ejecutar la consulta SQL
            if (mysqli_query($con, $sql_delete_producto)) {
                // Consulta SQL para obtener el stock actual del producto
                $sql_stock_actual = "SELECT STOCK FROM producto WHERE _id = $idProducto";

                // Ejecutar la consulta SQL
                $result_stock_actual = mysqli_query($con, $sql_stock_actual);

                // Verificar si se encontró el stock actual del producto
                if ($result_stock_actual && mysqli_num_rows($result_stock_actual) > 0) {
                    $row_stock_actual = mysqli_fetch_assoc($result_stock_actual);
                    $stock_actual = $row_stock_actual['STOCK'];

                    // Calcular el nuevo stock del producto
                    $nuevo_stock = $stock_actual + $cantidad_producto;

                    // Consulta SQL para actualizar el stock del producto
                    $sql_update_stock_product = "UPDATE producto SET STOCK = $nuevo_stock WHERE _id = $idProducto";

                    // Ejecutar la consulta SQL
                    if (mysqli_query($con, $sql_update_stock_product)) {
                        $fechaActual = date('d-m-Y');
                        $fechaYHora = date('d-m-Y H:i');
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

                        $sql_update_stock = "INSERT INTO stock (id_PRODUCTO, FECHA, INGRESO, DETALLE) VALUES ('$idProducto', '$fechaYHora', '$cantidad_producto', 'Producto quitado en un turno' )";
                        if ($con->query($sql_update_stock) === FALSE) {
                            echo json_encode(array("success" => false, "message" => "Error al agregar el stock: " . $con->error));
                        } else {
                            // Actualizar el total de los productos en el ticket
                            $sql_total_productos = "SELECT SUM(detalle_ticket.PRECIO * detalle_ticket.CANTIDAD) AS total 
                        FROM detalle_ticket 
                        INNER JOIN producto ON detalle_ticket.id_PRODUCTO = producto._id 
                        WHERE detalle_ticket.id_TICKET = $idTicket";
                            $result_total_productos = mysqli_query($con, $sql_total_productos);
                            $fila_total_productos = mysqli_fetch_assoc($result_total_productos);
                            $total_productos = $fila_total_productos['total'];

                            // Actualizar el total del ticket
                            $sql_update_total = "UPDATE ticket SET TOTAL_DETALLE = '$total_productos' WHERE _id = $idTicket";
                            mysqli_query($con, $sql_update_total);

                            // Actualizar el total general del ticket
                            $total_general = $total_productos + $total_cancha + $extra - $senia;
                            $sql_update_total_general = "UPDATE ticket SET TOTAL = '$total_general' WHERE _id = $idTicket";
                            mysqli_query($con, $sql_update_total_general);

                            echo "Producto eliminado correctamente y stock actualizado";
                        }

                    } else {
                        echo "Error al actualizar el stock del producto: " . mysqli_error($con);
                    }
                } else {
                    echo "No se pudo obtener el stock actual del producto";
                }
            } else {
                echo "Error al eliminar el producto: " . mysqli_error($con);
            }
        } else {
            echo "No se encontró la cantidad del producto en el detalle del ticket";
        }
    } else {
        echo "No se encontró el ticket correspondiente al evento seleccionado";
    }
} else {
    echo "No se recibió el id del producto o el id del evento";
}
?>