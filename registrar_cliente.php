<?php
require_once 'cliente_auth.php';
require_once 'cliente_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_login.php");
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email = strtolower(trim($_POST['email'] ?? ''));
$telefono = trim($_POST['telefono'] ?? '');
$clave = $_POST['clave'] ?? '';
$repetir_clave = $_POST['repetir_clave'] ?? '';

if ($nombre === '' || $telefono === '' || $email === '' || strlen($clave) < 6) {
    header("Location: cliente_login.php?error=" . urlencode("Completa nombre, email, telefono y una clave de al menos 6 caracteres."));
    exit;
}

if ($clave !== $repetir_clave) {
    header("Location: cliente_login.php?error=" . urlencode("Las claves no coinciden."));
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: cliente_login.php?error=" . urlencode("Ingresa un email valido."));
    exit;
}

$clave_hash = password_hash($clave, PASSWORD_BCRYPT);
$codigo = (string) random_int(100000, 999999);
$codigo_hash = password_hash($codigo, PASSWORD_BCRYPT);
$codigo_expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $con->prepare("SELECT _id, CLAVE, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE TELEFONO = ? AND VISIBLE = 1 LIMIT 1");
$stmt->bind_param("s", $telefono);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente_existente = $resultado->fetch_assoc();
$stmt->close();

$stmt = $con->prepare("SELECT _id, CLAVE, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE MAIL = ? AND VISIBLE = 1 LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$cliente_por_mail = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($cliente_por_mail && (!$cliente_existente || (int) $cliente_por_mail['_id'] !== (int) $cliente_existente['_id'])) {
    header("Location: cliente_login.php?error=" . urlencode("Ese email ya esta registrado con otro cliente."));
    exit;
}

if ($cliente_existente && !empty($cliente_existente['CLAVE']) && (int) $cliente_existente['EMAIL_VERIFICADO'] === 1) {
    header("Location: cliente_login.php?error=" . urlencode("Ese telefono ya tiene acceso creado. Ingresa con tu clave."));
    exit;
}

if ($cliente_existente) {
    $cliente_id = (int) $cliente_existente['_id'];
    $stmt = $con->prepare("UPDATE clientes SET NOMBRE = ?, MAIL = ?, TELEFONO = ?, CLAVE = ?, EMAIL_VERIFICADO = 0, CODIGO_VERIFICACION = ?, CODIGO_EXPIRA = ? WHERE _id = ?");
    $stmt->bind_param("ssssssi", $nombre, $email, $telefono, $clave_hash, $codigo_hash, $codigo_expira, $cliente_id);
    $ok = $stmt->execute();
    $stmt->close();
} else {
    $stmt = $con->prepare("INSERT INTO clientes (NOMBRE, MAIL, TELEFONO, CLAVE, EMAIL_VERIFICADO, CODIGO_VERIFICACION, CODIGO_EXPIRA) VALUES (?, ?, ?, ?, 0, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $email, $telefono, $clave_hash, $codigo_hash, $codigo_expira);
    $ok = $stmt->execute();
    $cliente_id = $stmt->insert_id;
    $stmt->close();
}

if (!$ok) {
    header("Location: cliente_login.php?error=" . urlencode("No se pudo crear el acceso. Intenta nuevamente."));
    exit;
}

[$mail_ok, $mail_mensaje] = enviar_codigo_verificacion_cliente($email, $nombre, $codigo);
if (!$mail_ok) {
    header("Location: cliente_login.php?error=" . urlencode($mail_mensaje));
    exit;
}

header("Location: verificar_cliente.php?cliente=" . $cliente_id);
exit;
?>
