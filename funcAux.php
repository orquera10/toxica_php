<?php
// Incluir la configuración de la conexión a la base de datos
include 'config.php';

// Verificar que la solicitud sea de tipo POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Consulta SQL para actualizar las fechas
    $sql = "UPDATE stock s
            JOIN resumen_dias rd ON s.id_DIA = rd._id
            SET s.FECHA = rd.FECHA";

    // Ejecutar la consulta
    $stmt = $con->prepare($sql);
    $stmt->execute();

    echo "Fechas actualizadas correctamente.";
}

// Cerrar la conexión a la base de datos
$con->close();
?>