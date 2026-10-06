<?php
include('config.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin/');
    exit;
}

$usuario = $_POST['Usuario'] ?? '';
$clave = $_POST['Clave'] ?? '';

// Consulta para obtener los datos del usuario
$sql = "SELECT * FROM usuarios WHERE USUARIO='$usuario'";
$consulta = mysqli_query($con, $sql);
$existe = mysqli_num_rows($consulta);

if ($existe == 1) {
    $usuario_info = mysqli_fetch_assoc($consulta);
    $tipo_usuario = $usuario_info['TIPO'];
    $id_usuario = $usuario_info['_id'];
    $claveHash = $usuario_info['CLAVE']; // Obtener la contraseña hasheada de la base de datos

    // Verificar si la clave en la base de datos es un hash válido
    if (password_verify($clave, $claveHash)) {
        // Crear firmas para las cookies
        $firma_usuario = hash_hmac('sha256', $usuario, 'clave_secreta');
        $firma_tipo = hash_hmac('sha256', $tipo_usuario, 'clave_secreta');
        $firma_id = hash_hmac('sha256', $id_usuario, 'clave_secreta');

        // Establecer cookies con firma
        setcookie("USUARIO", $usuario . '|' . $firma_usuario, time() + 32400, '/', '', true, true);
        setcookie("TIPO", $tipo_usuario . '|' . $firma_tipo, time() + 32400, '/', '', true, true);
        setcookie("ID_USUARIO", $id_usuario . '|' . $firma_id, time() + 32400, '/');

        // Redireccionar a la página principal
        header("Location: page_turnos.php");
        exit();
    } else {
        // En caso de que password_verify falle
        header('Location: admin/?error=' . urlencode('Verifique que el usuario y la clave sean correctos.'));
        exit;
    }
} else {
    // Usuario no encontrado
    header('Location: admin/?error=' . urlencode('Verifique que el usuario y la clave sean correctos.'));
    exit;
}
?>
