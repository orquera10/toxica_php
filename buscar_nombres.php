<?php
// Incluir el archivo de configuración de la base de datos
include 'config.php';

// Verificar si se recibió el valor del nombre a buscar
if(isset($_POST['nombre'])) {
    // Obtener el valor del nombre a buscar y limpiarlo para evitar inyección SQL
    $nombre = mysqli_real_escape_string($con, $_POST['nombre']);

    // Consulta SQL para buscar nombres que coincidan con el valor recibido
    $sql = "SELECT NOMBRE FROM clientes WHERE NOMBRE LIKE '%$nombre%' AND VISIBLE=1";

    // Ejecutar la consulta
    $result = mysqli_query($con, $sql);

    // Verificar si se encontraron nombres coincidentes
    if(mysqli_num_rows($result) > 0) {
        // Construir una lista de nombres coincidentes como elementos <a>
        $nombresCoincidentes = '<div class="list-group listaNombresVentas">';
        while($row = mysqli_fetch_assoc($result)) {
            $nombreCliente = $row['NOMBRE'];
            // Agregar cada nombre como un enlace <a> dentro del div #nombresCoincidentes
            $nombresCoincidentes .= '<a href="#" class="list-group-item list-group-item-action nombre-coincidente">' . $nombreCliente . '</a>';
        }
        $nombresCoincidentes .= '</div>';

        // Devolver los nombres coincidentes como respuesta HTML
        echo $nombresCoincidentes;
    } else {
        // Si no se encontraron nombres coincidentes, devolver un mensaje de error
        echo '<div class="alert alert-warning" role="alert">No se encontraron nombres coincidentes</div>';
    }
} else {
    // Si no se recibió el valor del nombre, devolver un mensaje de error
    echo '<div class="alert alert-danger" role="alert">No se recibió el valor del nombre a buscar</div>';
}

// Cerrar la conexión a la base de datos
mysqli_close($con);
?>

