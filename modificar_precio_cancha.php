<?php
// Incluir archivo de configuración de la base de datos
include 'config.php';

// Verificar si se recibió un ID de cancha y un nuevo precio
if (isset($_POST['cancha_id']) && isset($_POST['nuevo_precio'])) {
    // Limpiar los datos recibidos para evitar inyección SQL
    $cancha_id = mysqli_real_escape_string($con, $_POST['cancha_id']);
    $nuevo_precio = mysqli_real_escape_string($con, $_POST['nuevo_precio']);

    // Consulta SQL para obtener el precio anterior de la cancha
    $sql_precio_anterior = "SELECT PRECIO FROM canchas WHERE _id = '$cancha_id'";
    $result_precio_anterior = mysqli_query($con, $sql_precio_anterior);
    $row_precio_anterior = mysqli_fetch_assoc($result_precio_anterior);
    $precio_anterior = $row_precio_anterior['PRECIO'];

    // Consulta SQL para actualizar el precio en la base de datos
    $sql = "UPDATE canchas SET PRECIO = '$nuevo_precio' WHERE _id = '$cancha_id'";

    // Ejecutar la consulta y verificar si fue exitosa
    if (mysqli_query($con, $sql)) {
        // echo "Precio actualizado correctamente.";

        // Calcular la diferencia de precios
        // $diferencia_precio = $nuevo_precio - $precio_anterior;

        // Obtener la fecha actual
        $fecha_actual = date('d-m-Y'); // Formato correcto para comparaciones de fecha en MySQL


        // Consulta SQL para actualizar los tickets relacionados
        $sql_tickets = "SELECT ticket._id AS id_ticket, ticket.EXTRA, ticket.SENIA, ticket.TOTAL_DETALLE, turnos.HORA_INICIO, turnos.HORA_FIN
                        FROM ticket
                        INNER JOIN turnos ON ticket.id_TURNO = turnos._id
                        WHERE turnos.id_CANCHA = '$cancha_id' AND STR_TO_DATE(turnos.FECHA, '%d-%m-%Y') >= STR_TO_DATE('$fecha_actual', '%d-%m-%Y')";
        $result_tickets = mysqli_query($con, $sql_tickets);


        // Actualizar los tickets
        while ($row_ticket = mysqli_fetch_assoc($result_tickets)) {
            $id_ticket = $row_ticket['id_ticket'];
            $extra = $row_ticket['EXTRA'];
            $senia = $row_ticket['SENIA'];
            $total_detalle = $row_ticket['TOTAL_DETALLE'];
            $hora_inicio = $row_ticket['HORA_INICIO'];
            $hora_fin = $row_ticket['HORA_FIN'];
            $duracion = strtotime($hora_fin) - strtotime($hora_inicio); // Diferencia en segundos
            $duracion_horas = $duracion / (60 * 60); // Convertir a horas

            // Verificar si el ID de la cancha es 8
            if ($cancha_id == 8) {
                // Si el ID de la cancha es 8, actualizar solo TOTAL_CANCHA con el nuevo precio
                $nuevo_total_cancha = $nuevo_precio;
            } else {
                // Si el ID de la cancha no es 8, actualizar TOTAL_CANCHA y TOTAL con el nuevo precio
                $nuevo_total_cancha = $duracion_horas * $nuevo_precio;
            }
            $total = $nuevo_total_cancha+$extra-$senia+$total_detalle;
            $sql_actualizar_ticket = "UPDATE ticket SET TOTAL_CANCHA = '$nuevo_total_cancha', TOTAL = '$total' WHERE _id = '$id_ticket'";
            mysqli_query($con, $sql_actualizar_ticket);
        }
        echo "Precio actualizado correctamente.";
    } else {
        echo "Error al actualizar el precio: " . mysqli_error($con);
    }
} else {
    echo "No se recibieron datos válidos.";
}
?>