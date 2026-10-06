<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombreProducto = $_POST['nombreProducto'];
    $precioProducto = $_POST['precioProducto'];
    $tipoProducto = $_POST['tipoProducto'];
    $prioridadProducto = $_POST['prioridadProducto']; // ← NUEVO

    $directorioImagenes = "img/productos/";

    if (isset($_FILES['imagenProducto']) && $_FILES['imagenProducto']['error'] === UPLOAD_ERR_OK) {
        $imagenNombre = $_FILES['imagenProducto']['name'];
        $imagenTipo = $_FILES['imagenProducto']['type'];
        $imagenTamanio = $_FILES['imagenProducto']['size'];
        $imagenTempPath = $_FILES['imagenProducto']['tmp_name'];

        $imagenNombreUnico = uniqid() . '_' . $imagenNombre;
        $imagenRutaCompleta = $directorioImagenes . $imagenNombreUnico;

        if (move_uploaded_file($imagenTempPath, $imagenRutaCompleta)) {
            $imagenPath = $imagenRutaCompleta;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al mover la imagen al directorio de imágenes.']);
            exit;
        }
    } else {
        $imagenPath = $directorioImagenes . 'imagen_articulo_por_defecto.jpg';
    }

    // ← AHORA con IMPORTANCIA incluido
    $sql = "INSERT INTO producto (NOMBRE, PRECIO, URL_IMG, TIPO, IMPORTANCIA) 
            VALUES ('$nombreProducto', $precioProducto, '$imagenPath', '$tipoProducto', $prioridadProducto)";

    if (mysqli_query($con, $sql)) {
        $id_producto = mysqli_insert_id($con);
        $periodo = date('m-Y');

        $sql_obtener_periodo = "SELECT _id FROM periodo WHERE FECHA = '$periodo'";
        $resultado_obtener_periodo = mysqli_query($con, $sql_obtener_periodo);

        if (mysqli_num_rows($resultado_obtener_periodo) > 0) {
            $row = mysqli_fetch_assoc($resultado_obtener_periodo);
            $id_periodo = $row['_id'];
        } else {
            $sql_crear_periodo = "INSERT INTO periodo (FECHA) VALUES ('$periodo')";
            mysqli_query($con, $sql_crear_periodo);
            $id_periodo = mysqli_insert_id($con);
        }

        $sql_stock_mes = "INSERT INTO stock_mes (id_PERIODO, id_PRODUCTO) VALUES ('$id_periodo', '$id_producto')";
        if (mysqli_query($con, $sql_stock_mes)) {
            echo json_encode(['success' => true, 'message' => 'Producto agregado correctamente.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al agregar el producto a la tabla stock_mes.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al agregar el producto a la base de datos.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Error: No se recibió ningún formulario.']);
}
?>