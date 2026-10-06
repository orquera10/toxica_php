<?php
// Incluir el archivo de configuración de la base de datos
include 'config.php';

// Verificar si se recibió el ID de resumen_dias
if (isset($_POST['id_resumen'])) {
    // Obtener el ID de resumen_dias enviado desde el cliente
    $idResumen = $_POST['id_resumen'];

    // Consulta para actualizar el valor de FINALIZADO a 1
    $sqlActualizar = "UPDATE resumen_dias SET FINALIZADO = 1 WHERE _id = $idResumen";

    // Ejecutar la consulta
    if (mysqli_query($con, $sqlActualizar)) {
        // La actualización se realizó con éxito
        echo json_encode(array("success" => true));
    } else {
        // Error al ejecutar la consulta
        echo json_encode(array("success" => false, "message" => "Error al actualizar FINALIZADO"));
    }
} else {
    // No se recibió el ID de resumen_dias
    echo json_encode(array("success" => false, "message" => "ID de resumen_dias no recibido"));
}
?>
