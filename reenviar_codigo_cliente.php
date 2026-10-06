<?php
require_once 'cliente_auth.php';
require_once 'cliente_mailer.php';

$cliente_id = (int) ($_GET['cliente'] ?? $_POST['cliente_id'] ?? 0);

if ($cliente_id <= 0) {
    header("Location: cliente_login.php?error=" . urlencode("No se pudo reenviar el codigo."));
    exit;
}

$stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE _id = ? AND VISIBLE = 1 LIMIT 1");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente) {
    header("Location: cliente_login.php?error=" . urlencode("Cliente no encontrado."));
    exit;
}

if ((int) $cliente['EMAIL_VERIFICADO'] === 1) {
    header("Location: cliente_login.php?error=" . urlencode("Ese correo ya esta validado. Ingresa con tu clave."));
    exit;
}

$codigo = (string) random_int(100000, 999999);
$codigo_hash = password_hash($codigo, PASSWORD_BCRYPT);
$codigo_expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));

$stmt = $con->prepare("UPDATE clientes SET CODIGO_VERIFICACION = ?, CODIGO_EXPIRA = ? WHERE _id = ?");
$stmt->bind_param("ssi", $codigo_hash, $codigo_expira, $cliente_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header("Location: verificar_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("No se pudo generar un nuevo codigo."));
    exit;
}

[$mail_ok, $mail_mensaje] = enviar_codigo_verificacion_cliente($cliente['MAIL'], $cliente['NOMBRE'], $codigo);
if (!$mail_ok) {
    header("Location: verificar_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode($mail_mensaje));
    exit;
}

header("Location: verificar_cliente.php?cliente=" . $cliente_id . "&success=" . urlencode("Te enviamos un nuevo codigo de validacion."));
exit;
?>
