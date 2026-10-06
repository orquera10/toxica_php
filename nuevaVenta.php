<?php
// Verificar si la solicitud es de tipo POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    include('config.php');

    $idCliente = isset($_POST['cliente_id_evento_venta']) && !empty($_POST['cliente_id_evento_venta']) ? $_POST['cliente_id_evento_venta'] : 1;
    $pagoTransferencia = $_POST['pagoTransfVenta'];
    $pagoEfectivo = $_POST['pagoEfectivoVenta'];
    $total = $_POST['totalVenta'];

    // 💡 Obtener la fecha enviada desde el formulario o usar la actual
    $fecha = isset($_POST['fechaVenta']) && !empty($_POST['fechaVenta'])
        ? date("d-m-Y H:i:s", strtotime($_POST['fechaVenta']))
        : date("d-m-Y H:i:s");

    $sumaPagos = $pagoTransferencia + $pagoEfectivo;

    if ($total != 0 && $total == $sumaPagos) {
        // Insertar el ticket
        $sql_insert_ticket = "INSERT INTO ticket (id_CLIENTE, id_TURNO, FECHA, TOTAL_CANCHA, TOTAL_DETALLE, TOTAL, PAGO_TRANSFERENCIA, PAGO_EFECTIVO) 
                              VALUES ('$idCliente', 1, '$fecha', 0, $total, $total, $pagoTransferencia, $pagoEfectivo)";

        if ($con->query($sql_insert_ticket) === TRUE) {
            $id_ticket = $con->insert_id;
            $detalleProductos = json_decode($_POST['detalleProductos'], true);

            foreach ($detalleProductos as $detalle) {
                $id_producto = $detalle['id'];
                $cantidad = $detalle['cantidad'];

                $sql_precio_producto = "SELECT PRECIO FROM producto WHERE _id = '$id_producto'";
                $result_precio_producto = $con->query($sql_precio_producto);
                $row_precio_producto = $result_precio_producto->fetch_assoc();
                $precio_producto = $row_precio_producto['PRECIO'];

                $sql_insert_detalle = "INSERT INTO detalle_ticket (id_TICKET, ID_PRODUCTO, PRECIO, CANTIDAD) 
                                       VALUES ('$id_ticket', '$id_producto', '$precio_producto', '$cantidad')";

                if ($con->query($sql_insert_detalle)) {
                    $sql_update_stock_product = "UPDATE producto SET STOCK = STOCK - $cantidad WHERE _id = '$id_producto'";
                    if ($con->query($sql_update_stock_product)) {
                        $fechaYHora = $fecha; // misma fecha que la venta
                        $stock = $cantidad;

                        $sql_update_stock = "INSERT INTO stock (id_PRODUCTO, FECHA, EGRESO, DETALLE) 
                                             VALUES ('$id_producto', '$fechaYHora', '$stock', 'Producto vendido')";
                        if ($con->query($sql_update_stock) === FALSE) {
                            echo json_encode(array("success" => false, "message" => "Error al agregar el stock: " . $con->error));
                        }
                    } else {
                        echo json_encode(array("success" => false, "message" => "Error al actualizar el stock del producto: " . $con->error));
                        exit;
                    }
                } else {
                    echo json_encode(array("success" => false, "message" => "Error al insertar el detalle del producto: " . $con->error));
                    exit;
                }
            }

            echo json_encode(array("success" => true));
        } else {
            echo json_encode(array("success" => false, "message" => "Error al insertar el ticket: " . $con->error));
        }

        $con->close();
    } else {
        echo json_encode(array("success" => false, "message" => "El total no coincide con la suma del pago por transferencia y el pago en efectivo"));
    }
} else {
    echo json_encode(array("success" => false, "message" => "La solicitud debe ser de tipo POST"));
}
?>
