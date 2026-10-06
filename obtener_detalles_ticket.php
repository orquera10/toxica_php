<?php
// obtener_detalles_ticket.php

// Incluir el archivo de configuración de la base de datos
include 'config.php';

// Verificar si se recibió un ID de ticket
if (isset($_GET['id'])) {
    $idTicket = $_GET['id'];

    // Consulta SQL para obtener los detalles del ticket
    $sqlTicket = "SELECT ticket._id AS id_ticket, clientes.NOMBRE AS nombre_cliente
                    FROM ticket
                    INNER JOIN clientes ON ticket.id_CLIENTE = clientes._id
                    WHERE ticket._id = '$idTicket'";
    $resultadoTicket = mysqli_query($con, $sqlTicket);

    // Verificar si se encontraron resultados para el ticket
    if ($filaTicket = mysqli_fetch_assoc($resultadoTicket)) {
        // Formatear los detalles del ticket en HTML
        $html = "<strong>ID Ticket:</strong> " . $filaTicket['id_ticket'] . "<br>";
        $html .= "<strong>Nombre Cliente Turno:</strong> " . $filaTicket['nombre_cliente'] . "<br>";
        $html .= "<p class='mt-3'>Detalle Pago:</p>";
        // Agrega más campos según sea necesario
        // ...

        // Consulta SQL para obtener los detalles de pago del ticket
        $sqlPago = "SELECT * FROM detalle_pago WHERE id_TICKET = '$idTicket'";
        $resultadoPago = mysqli_query($con, $sqlPago);

        // Verificar si se encontraron resultados para los detalles de pago
        if (mysqli_num_rows($resultadoPago) > 0) {
            // Formatear los detalles de pago en HTML y agregarlos al contenido del modal
            $html .= "<div class='rounded tablaTurnosAll mb-4 shadow py-2 px-4'>";
            $html .= "<table class='table'>";
            $html .= "<thead>";
            $html .= "<tr>";
            $html .= "<th>ID Pago</th>";
            $html .= "<th>Nombre</th>";
            $html .= "<th>Transferencia</th>";
            $html .= "<th>Efectivo</th>";
            $html .= "</tr>";
            $html .= "</thead>";
            $html .= "<tbody>";

            while ($filaPago = mysqli_fetch_assoc($resultadoPago)) {
                $html .= "<tr>";
                $html .= "<td>" . $filaPago['_id'] . "</td>";
                $html .= "<td>" . $filaPago['NOMBRE'] . "</td>";
                $html .= "<td>" . $filaPago['TRANSFERENCIA'] . "</td>";
                $html .= "<td>" . $filaPago['EFECTIVO'] . "</td>";
                $html .= "</tr>";
            }

            $html .= "</tbody>";
            $html .= "</table>";
            $html .= "</div>";
        } else {
            $html .= "No se encontraron detalles de pago para este ticket.";
        }

        echo $html;
    } else {
        echo "No se encontraron detalles para este ticket.";
    }
} else {
    echo "ID de ticket no proporcionado.";
}
?>
