<?php
include('config.php');

// Verificar si se recibió el id del evento y el tipo de total
if (isset($_POST['idEvento'], $_POST['tipoTotal'])) {
    $idEvento = $_POST['idEvento'];
    $tipoTotal = $_POST['tipoTotal'];

    // Definir el nombre del campo correspondiente al tipo de total
    $campoTotal = '';
    switch ($tipoTotal) {
        case 'total_detalle':
            $campoTotal = 'TOTAL_DETALLE';
            break;
        case 'total_cancha':
            $campoTotal = 'TOTAL_CANCHA';
            break;
        case 'total':
            $campoTotal = 'TOTAL';
            break;
        default:
            echo "Tipo de total inválido";
            exit;
    }

    // Consulta SQL para obtener el total específico del ticket para el evento especificado
    $sql_total = "SELECT $campoTotal FROM ticket WHERE id_TURNO = $idEvento";

    // Ejecutar la consulta SQL
    $result_total = mysqli_query($con, $sql_total);

    // Verificar si se obtuvo el resultado
    if ($result_total) {
        // Obtener el total específico
        $row = mysqli_fetch_assoc($result_total);
        $total = $row[$campoTotal];

        // Devolver el total como respuesta
        echo $total;
    } else {
        // Si hay un error en la consulta, devolver un mensaje de error
        echo "Error al obtener el total $tipoTotal del ticket";
    }
} else {
    // Si no se recibió el id del evento o el tipo de total, devolver un mensaje de error
    echo "No se recibió el id del evento o el tipo de total";
}
?>
