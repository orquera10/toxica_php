<?php
// Incluir el archivo de configuración de la base de datos
require("config.php");

// Verificar si se han enviado los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validar y obtener los datos del formulario
    $nombre = isset($_POST["nombre"]) ? $_POST["nombre"] : "";
    $email = isset($_POST["email"]) ? $_POST["email"] : "";
    $telefono = isset($_POST["telefono"]) ? $_POST["telefono"] : "";

    // Verificar si los campos obligatorios están vacíos
    if (empty($nombre) || empty($telefono)) {
        // Si faltan campos obligatorios, redirigir de vuelta a la página actual con un mensaje de error
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Por favor, complete todos los campos obligatorios.");
        exit; // Terminar la ejecución del script
    }

    // Generar un correo electrónico predeterminado si el campo email está vacío
    if (empty($email)) {
        $nombre_sin_espacios = str_replace(' ', '', $nombre); // Eliminar espacios del nombre
        $email = strtolower($nombre_sin_espacios . '@mail.com'); // Generar email predeterminado y convertir a minúsculas
    } else {
        $email = strtolower($email); // Convertir el email proporcionado a minúsculas
    }

    // Verificar si el teléfono ya existe en la base de datos
    $sql_verificar_telefono = "SELECT * FROM clientes WHERE TELEFONO = '$telefono' AND VISIBLE = 1";
    $resultado_verificar_telefono = mysqli_query($con, $sql_verificar_telefono);

    if (mysqli_num_rows($resultado_verificar_telefono) > 0) {
        // Si el teléfono ya existe en la base de datos, redirigir de vuelta a la página actual con un mensaje de error
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=El teléfono ya está registrado.");
        exit; // Terminar la ejecución del script
    }

    // Preparar la consulta SQL para insertar el nuevo cliente
    $sql = "INSERT INTO clientes (NOMBRE, MAIL, TELEFONO) VALUES ('$nombre', '$email', '$telefono')";

    // Ejecutar la consulta SQL utilizando la conexión existente en config.php
    if (mysqli_query($con, $sql)) {
        // Si la inserción es exitosa, redirigir de vuelta a la página actual con un mensaje de éxito
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?eaa");
    } else {
        // Si hay un error al ejecutar la consulta SQL, redirigir de vuelta a la página actual con un mensaje de error
        header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=Error al agregar cliente: " . mysqli_error($con));
    }
} else {
    // Si no se ha enviado el formulario mediante POST, mostrar un mensaje de error
    echo "Error: El formulario no ha sido enviado correctamente.";
}
?>
