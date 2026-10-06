<?php
require_once 'cliente_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_login.php");
    exit;
}

$cliente_id = (int) ($_POST['cliente_id'] ?? 0);
$codigo = trim($_POST['codigo'] ?? '');

if ($cliente_id <= 0 || $codigo === '') {
    header("Location: cliente_login.php?error=" . urlencode("Codigo invalido."));
    exit;
}

$stmt = $con->prepare("SELECT _id, NOMBRE, CODIGO_VERIFICACION, CODIGO_EXPIRA FROM clientes WHERE _id = ? AND VISIBLE = 1 LIMIT 1");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente || empty($cliente['CODIGO_VERIFICACION']) || strtotime($cliente['CODIGO_EXPIRA']) < time()) {
    header("Location: verificar_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("El codigo vencio. Registrate nuevamente para recibir otro codigo."));
    exit;
}

if (!password_verify($codigo, $cliente['CODIGO_VERIFICACION'])) {
    header("Location: verificar_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("El codigo no es correcto."));
    exit;
}

$stmt = $con->prepare("UPDATE clientes SET EMAIL_VERIFICADO = 1, CODIGO_VERIFICACION = NULL, CODIGO_EXPIRA = NULL WHERE _id = ?");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$stmt->close();

set_cliente_cookie(CLIENTE_COOKIE, $cliente['_id']);
set_cliente_cookie(CLIENTE_NOMBRE_COOKIE, $cliente['NOMBRE']);

header("Location: cliente_reservas.php?e=" . urlencode("Tu correo fue validado correctamente."));
exit;
?>
