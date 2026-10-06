<?php
// Incluir el archivo de configuración de la base de datos
include 'config.php';

// Verificar si se envió un formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibir los datos del formulario
    $nombreProducto = $_POST['nombreProducto'];
    $precioProducto = $_POST['precioProducto'];
    $idProducto = $_POST['idProducto'];
    $tipo = $_POST['tipoProducto'];
    $prioridad = isset($_POST['prioridadProducto']) ? intval($_POST['prioridadProducto']) : null;

    // Verificar si se ha cargado una nueva imagen
    if (isset($_FILES['imagenProducto']) && $_FILES['imagenProducto']['error'] === UPLOAD_ERR_OK) {
        // Obtener información de la imagen
        $imagenNombre = $_FILES['imagenProducto']['name'];
        $imagenTipo = $_FILES['imagenProducto']['type'];
        $imagenTamanio = $_FILES['imagenProducto']['size'];
        $imagenTempPath = $_FILES['imagenProducto']['tmp_name'];

        // Directorio donde se almacenarán las imágenes cargadas
        $directorioImagenes = "img/productos/";

        // Generar un nombre único para la imagen
        $imagenNombreUnico = uniqid() . '_' . $imagenNombre;

        // Mover la imagen al directorio de imágenes
        $imagenRutaCompleta = $directorioImagenes . $imagenNombreUnico;
        if (move_uploaded_file($imagenTempPath, $imagenRutaCompleta)) {
            // Actualizar la base de datos incluyendo la prioridad
            $sql = "UPDATE producto SET 
                        URL_IMG = '$imagenRutaCompleta', 
                        NOMBRE = '$nombreProducto', 
                        PRECIO = $precioProducto, 
                        TIPO = '$tipo', 
                        IMPORTANCIA = $prioridad 
                    WHERE _id = $idProducto";
            mysqli_query($con, $sql);

            $response = array(
                'success' => true,
                'message' => 'Producto actualizado correctamente.'
            );
            echo json_encode($response);
            exit;
        } else {
            $response = array(
                'success' => false,
                'message' => 'Error al guardar la imagen.'
            );
            echo json_encode($response);
            exit;
        }
    } else {
        // No se cargó imagen: actualizar solo los demás campos
        $sql = "UPDATE producto SET 
                    NOMBRE = '$nombreProducto', 
                    PRECIO = $precioProducto, 
                    TIPO = '$tipo', 
                    IMPORTANCIA = $prioridad 
                WHERE _id = $idProducto";
        mysqli_query($con, $sql);

        $response = array(
            'success' => true,
            'message' => 'Producto actualizado correctamente.'
        );
        echo json_encode($response);
        exit;
    }
} else {
    $response = array(
        'success' => false,
        'message' => 'Error: No se recibió ningún formulario.'
    );
    echo json_encode($response);
    exit;
}
?>