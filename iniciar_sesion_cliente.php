<?php
require_once 'cliente_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_login.php");
    exit;
}

$login = trim($_POST['login'] ?? '');
$clave = $_POST['clave'] ?? '';

if ($login === '' || $clave === '') {
    header("Location: cliente_login.php?error=" . urlencode("Completa email/telefono y clave."));
    exit;
}

$stmt = $con->prepare("SELECT _id, NOMBRE, CLAVE, EMAIL_VERIFICADO + 0 AS EMAIL_VERIFICADO FROM clientes WHERE VISIBLE = 1 AND (MAIL = ? OR TELEFONO = ?) LIMIT 1");
$stmt->bind_param("ss", $login, $login);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente = $resultado->fetch_assoc();
$stmt->close();

if (!$cliente || empty($cliente['CLAVE']) || !password_verify($clave, $cliente['CLAVE'])) {
    header("Location: cliente_login.php?error=" . urlencode("Verifica tus datos de acceso."));
    exit;
}

if ((int) $cliente['EMAIL_VERIFICADO'] !== 1) {
    header("Location: verificar_cliente.php?cliente=" . (int) $cliente['_id'] . "&error=" . urlencode("Valida tu correo para ingresar."));
    exit;
}

set_cliente_cookie(CLIENTE_COOKIE, $cliente['_id']);
set_cliente_cookie(CLIENTE_NOMBRE_COOKIE, $cliente['NOMBRE']);

header("Location: cliente_reservas.php");
exit;
?>
