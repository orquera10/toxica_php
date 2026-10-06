<?php
require_once 'cliente_auth.php';
require_once 'cliente_mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: recuperar_clave_cliente.php");
    exit;
}

$login = trim($_POST['login'] ?? '');

if ($login === '') {
    header("Location: recuperar_clave_cliente.php?error=" . urlencode("Ingresa tu email o telefono."));
    exit;
}

$stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, CLAVE, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE VISIBLE = 1 AND (MAIL = ? OR TELEFONO = ?) LIMIT 1");
$stmt->bind_param("ss", $login, $login);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente || empty($cliente['CLAVE'])) {
    header("Location: recuperar_clave_cliente.php?error=" . urlencode("No encontramos un acceso de cliente con esos datos."));
    exit;
}

if ((int) $cliente['EMAIL_VERIFICADO'] !== 1) {
    header("Location: verificar_cliente.php?cliente=" . (int) $cliente['_id'] . "&error=" . urlencode("Valida tu correo antes de recuperar la clave."));
    exit;
}

$codigo = (string) random_int(100000, 999999);
$codigo_hash = password_hash($codigo, PASSWORD_BCRYPT);
$codigo_expira = date('Y-m-d H:i:s', strtotime('+30 minutes'));
$cliente_id = (int) $cliente['_id'];

$stmt = $con->prepare("UPDATE clientes SET RECUPERACION_CODIGO = ?, RECUPERACION_EXPIRA = ? WHERE _id = ?");
$stmt->bind_param("ssi", $codigo_hash, $codigo_expira, $cliente_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header("Location: recuperar_clave_cliente.php?error=" . urlencode("No se pudo generar el codigo."));
    exit;
}

[$mail_ok, $mail_mensaje] = enviar_codigo_recuperacion_cliente($cliente['MAIL'], $cliente['NOMBRE'], $codigo);
if (!$mail_ok) {
    header("Location: recuperar_clave_cliente.php?error=" . urlencode($mail_mensaje));
    exit;
}

header("Location: restablecer_clave_cliente.php?cliente=" . $cliente_id . "&success=" . urlencode("Te enviamos un codigo para recuperar tu clave."));
exit;
?>
