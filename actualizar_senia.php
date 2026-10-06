<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $monto = floatval($_POST['montoGasto']);
    $fecha = $_POST['fechaGasto'];
    $tipoPago = $_POST['tipoPago'];
    $idEvento = intval($_POST['idEvento']);
    $idCliente = intval($_POST['idCliente']);

    // NUEVOS CAMPOS
    $dejaSenia = trim($_POST['dejaSenia']);
    $recibeSenia = trim($_POST['recibeSenia']);

    $fechaConvertida = date('d-m-Y H:i:s', strtotime($fecha));
    $efectivo = ($tipoPago === 'efectivo') ? $monto : 0;
    $transferencia = ($tipoPago === 'transferencia') ? $monto : 0;

    // Consulta actualizada con DEJA y RECIBE
    $insertSeniaQuery = "INSERT INTO senias (MONTO, FECHA, id_CLIENTE, id_TURNO, EFECTIVO, TRANSFERENCIA, DEJA, RECIBE)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $con->prepare($insertSeniaQuery)) {
        $stmt->bind_param("dsiiiiss", $monto, $fechaConvertida, $idCliente, $idEvento, $efectivo, $transferencia, $dejaSenia, $recibeSenia);

        if ($stmt->execute()) {
            $updateTicketQuery = "UPDATE ticket 
                                  SET SENIA = SENIA + ?, TOTAL = TOTAL - ? 
                                  WHERE id_TURNO = ?";

            if ($stmtUpdate = $con->prepare($updateTicketQuery)) {
                $stmtUpdate->bind_param("ddi", $monto, $monto, $idEvento);

                if ($stmtUpdate->execute()) {
                    echo json_encode(['status' => 'success']);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el ticket.']);
                }
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al insertar la seña.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error en la preparación de la consulta.']);
    }
}
?>