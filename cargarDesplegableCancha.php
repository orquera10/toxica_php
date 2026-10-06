<?php
include ("config.php");

// Consulta SQL para seleccionar los nombres de las canchas de fútbol excluyendo la cancha con ID 9
$sql = "SELECT _id, NOMBRE FROM canchas WHERE _id != 9";

// Ejecutar la consulta
$canchas = mysqli_query($con, $sql);

// Obtener la fecha del campo oculto
$fecha_inicio = isset($_POST['fecha']) ? $_POST['fecha'] : '';

$html = '';

if (mysqli_num_rows($canchas) > 0) {
    // Generar el código HTML de los options del desplegable
    while ($fila = mysqli_fetch_assoc($canchas)) {
        $html .= '<option value="' . $fila['_id'] . '">' . $fila['NOMBRE'] . '</option>';
    }
} else {
    $html = "No se encontraron canchas de fútbol en la base de datos.";
}

// Devolver el HTML generado
echo $html;

?>
