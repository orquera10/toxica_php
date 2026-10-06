<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_usuario = $_POST['id_usuario'] ?? '';
    $monto_total = $_POST['monto_total'] ?? '';
    $fecha = $_POST['fecha'] ?? '';
    $detalle = $_POST['detalle'] ?? '';  // Nuevo campo

    if (empty($id_usuario) || empty($monto_total) || empty($fecha)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Faltan datos obligatorios (usuario, monto o fecha).'
        ]);
        exit;
    }

    $fecha_formateada = date('d-m-Y', strtotime($fecha));

    $sql_check = "SELECT _id FROM turnos_personal WHERE ID_USER = ? AND FECHA = ?";
    $stmt_check = mysqli_prepare($con, $sql_check);
    mysqli_stmt_bind_param($stmt_check, "is", $id_usuario, $fecha_formateada);
    mysqli_stmt_execute($stmt_check);
    $resultado = mysqli_stmt_get_result($stmt_check);

    if ($fila_existente = mysqli_fetch_assoc($resultado)) {
        $id_turno = $fila_existente['_id'];
        $sql_update = "UPDATE turnos_personal SET MONTO = ?, DETALLE = ? WHERE _id = ?";
        $stmt_update = mysqli_prepare($con, $sql_update);
        mysqli_stmt_bind_param($stmt_update, "dsi", $monto_total, $detalle, $id_turno);

        if (mysqli_stmt_execute($stmt_update)) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Turno actualizado correctamente.'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al actualizar el turno: ' . mysqli_stmt_error($stmt_update)
            ]);
        }

        mysqli_stmt_close($stmt_update);
    } else {
        $sql_insert = "INSERT INTO turnos_personal (ID_USER, MONTO, FECHA, DETALLE) VALUES (?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($con, $sql_insert);

        if ($stmt_insert) {
            mysqli_stmt_bind_param($stmt_insert, "idss", $id_usuario, $monto_total, $fecha_formateada, $detalle);
            if (mysqli_stmt_execute($stmt_insert)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Turno cerrado correctamente.'
                ]);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error al guardar el turno: ' . mysqli_stmt_error($stmt_insert)
                ]);
            }
            mysqli_stmt_close($stmt_insert);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Error al preparar la consulta.'
            ]);
        }
    }

    mysqli_stmt_close($stmt_check);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Solicitud no válida.'
    ]);
}

mysqli_close($con);
?>