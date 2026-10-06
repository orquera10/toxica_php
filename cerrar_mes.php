<?php
// Incluir la configuración de la conexión a la base de datos
include 'config.php';

// Verificar que la solicitud sea de tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los datos enviados por la solicitud AJAX
    $stockInicial = $_POST['stokInicial'];
    $idProducto = $_POST['id_producto'];
    $idPeriodoSiguiente = $_POST['id_periodo_siguiente'];
    $idPeriodoActual = $_POST['id_periodo_actual'];

    // Iniciar una transacción
    $con->begin_transaction();

    try {
        // Cambiar el valor de FINALIZADO a 1 en la tabla periodo
        $sqlUpdatePeriodo = "UPDATE periodo SET FINALIZADO = 1 WHERE _id = ?";
        $stmtUpdatePeriodo = $con->prepare($sqlUpdatePeriodo);
        $stmtUpdatePeriodo->bind_param("i", $idPeriodoActual);
        $stmtUpdatePeriodo->execute();

        // Insertar los datos en la tabla stock_mes
        $sqlInsertStockMes = "INSERT INTO stock_mes (id_PERIODO, id_PRODUCTO, STOCK_INICIAL) VALUES (?, ?, ?)";
        $stmtInsertStockMes = $con->prepare($sqlInsertStockMes);
        $stmtInsertStockMes->bind_param("iis", $idPeriodoSiguiente, $idProducto, $stockInicial);
        $stmtInsertStockMes->execute();

        // Confirmar la transacción
        $con->commit();

        // Enviar una respuesta JSON indicando éxito
        echo json_encode(["status" => "success"]);
    } catch (Exception $e) {
        // En caso de error, revertir la transacción
        $con->rollback();

        // Enviar una respuesta JSON indicando error
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }

    // Cerrar las declaraciones
    if (isset($stmtUpdatePeriodo)) {
        $stmtUpdatePeriodo->close();
    }
    if (isset($stmtInsertStockMes)) {
        $stmtInsertStockMes->close();
    }
}

// Cerrar la conexión a la base de datos
$con->close();
?>
