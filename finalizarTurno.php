<?php
// Verificar si se recibieron los parámetros esperados
if (isset($_POST['idTurno']) && isset($_POST['pagoEfectivo']) && isset($_POST['pagoTransferencia'])) {
    require("config.php");

    $idTurno = $_POST['idTurno'];
    $pagoEfectivo = $_POST['pagoEfectivo'];
    $pagoTransferencia = $_POST['pagoTransferencia'];
    $fechaPago = $_POST['fechaPago'] ?? ''; // <-- nuevo

    $nombrePagos = $_POST['nombrePagos'] ?? [];
    $montoTransferencias = $_POST['montoTransferencias'] ?? [];
    $montoEfectivos = $_POST['montoEfectivos'] ?? [];

    // Obtener el TOTAL y _id de la tabla ticket para el idTurno proporcionado
    $sql_total = "SELECT TOTAL, _id FROM ticket WHERE id_TURNO = ?";
    $stmt_total = mysqli_prepare($con, $sql_total);
    mysqli_stmt_bind_param($stmt_total, "d", $idTurno);
    mysqli_stmt_execute($stmt_total);
    mysqli_stmt_bind_result($stmt_total, $total, $idTicket);
    mysqli_stmt_fetch($stmt_total);
    mysqli_stmt_close($stmt_total);

    // Validar la suma de los pagos
    if ($pagoEfectivo + $pagoTransferencia != $total) {
        echo json_encode(["success" => false, "message" => "La suma de los pagos no coincide con el TOTAL."]);
        exit();
    }

    // Usar la fecha proporcionada o la actual si está vacía
    if (!empty($fechaPago)) {
        // Convertir al formato d-m-Y H:i:s
        $fecha_actual = date("d-m-Y H:i:s", strtotime($fechaPago));
    } else {
        $fecha_actual = date("d-m-Y H:i:s");
    }

    // Iniciar transacción
    mysqli_autocommit($con, false);

    // Actualizar ticket y turno
    $sql_update = "UPDATE turnos AS t
        JOIN ticket AS ti ON t._id = ti.id_TURNO
        SET ti.PAGO_EFECTIVO = ?, 
            ti.PAGO_TRANSFERENCIA = ?,
            ti.FECHA = ?, 
            t.FINALIZADO = 1
        WHERE t._id = ?";
    $stmt_update = mysqli_prepare($con, $sql_update);
    mysqli_stmt_bind_param($stmt_update, "ddsd", $pagoEfectivo, $pagoTransferencia, $fecha_actual, $idTurno);
    $success = mysqli_stmt_execute($stmt_update);

    if (!$success) {
        mysqli_rollback($con);
        echo json_encode(["success" => false, "message" => "Error al actualizar los datos: " . mysqli_error($con)]);
        exit();
    }

    // Insertar detalles de pago si existen
    if (!empty($nombrePagos) && !empty($montoTransferencias) && !empty($montoEfectivos)) {
        $sql_insert_detalle_pago = "INSERT INTO detalle_pago (id_TICKET, NOMBRE, TRANSFERENCIA, EFECTIVO) VALUES (?, ?, ?, ?)";
        $stmt_insert_detalle_pago = mysqli_prepare($con, $sql_insert_detalle_pago);

        for ($i = 0; $i < count($nombrePagos); $i++) {
            mysqli_stmt_bind_param($stmt_insert_detalle_pago, "isdd", $idTicket, $nombrePagos[$i], $montoTransferencias[$i], $montoEfectivos[$i]);
            $success = mysqli_stmt_execute($stmt_insert_detalle_pago);
            if (!$success) {
                mysqli_rollback($con);
                echo json_encode(["success" => false, "message" => "Error al insertar detalles de pago: " . mysqli_error($con)]);
                exit();
            }
        }
    }

    mysqli_commit($con);

    mysqli_stmt_close($stmt_update);
    if (isset($stmt_insert_detalle_pago)) {
        mysqli_stmt_close($stmt_insert_detalle_pago);
    }
    mysqli_close($con);

    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Error: Todos los parámetros necesarios no fueron proporcionados."]);
}
?>


