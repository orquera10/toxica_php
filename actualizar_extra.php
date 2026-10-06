<?php
require("config.php");

if (isset($_POST['dinero_extra'], $_POST['idEvento'], $_POST['fecha_extra'], $_POST['detalle_extra'])) {
    $dinero_extra = intval($_POST['dinero_extra']);
    $idEvento = intval($_POST['idEvento']);
    $fecha_extra = date('d-m-Y H:i:s'); // Guardás con formato compatible con DATETIME

    $detalle_extra = mysqli_real_escape_string($con, $_POST['detalle_extra']);

    // Obtener el total actual
    $sql_total = "SELECT TOTAL FROM ticket WHERE id_TURNO = $idEvento";
    $resultado = mysqli_query($con, $sql_total);

    if ($resultado && $row = mysqli_fetch_assoc($resultado)) {
        $total_actual = $row['TOTAL'];
        mysqli_free_result($resultado);

        $nuevo_total = $total_actual + $dinero_extra;

        // Iniciar transacción
        mysqli_begin_transaction($con);

        try {
            // 1. Insertar en tabla extras
            $sql_insert_extra = "INSERT INTO extras (id_TURNO, MONTO, FECHA, DETALLE)
                                 VALUES ($idEvento, $dinero_extra, '$fecha_extra', '$detalle_extra')";
            mysqli_query($con, $sql_insert_extra);

            // 2. Actualizar tabla ticket
            $sql_update_ticket = "UPDATE ticket SET EXTRA = EXTRA + $dinero_extra, TOTAL = $nuevo_total WHERE id_TURNO = $idEvento";
            mysqli_query($con, $sql_update_ticket);

            // Confirmar cambios
            mysqli_commit($con);

            echo "Extra registrado y ticket actualizado correctamente.";
        } catch (Exception $e) {
            // Revertir si algo falla
            mysqli_rollback($con);
            echo "Error al registrar el extra: " . $e->getMessage();
        }

    } else {
        echo "Error al obtener el total actual del ticket: " . mysqli_error($con);
    }

    mysqli_close($con);
} else {
    echo "Error: Datos faltantes.";
}
?>

