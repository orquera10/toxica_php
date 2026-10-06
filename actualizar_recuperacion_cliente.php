<?php
require_once 'cliente_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: recuperar_clave_cliente.php");
    exit;
}

$cliente_id = (int) ($_POST['cliente_id'] ?? 0);
$codigo = trim($_POST['codigo'] ?? '');
$clave_nueva = $_POST['clave_nueva'] ?? '';
$repetir_clave_nueva = $_POST['repetir_clave_nueva'] ?? '';

if ($cliente_id <= 0 || $codigo === '' || strlen($clave_nueva) < 6) {
    header("Location: restablecer_clave_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("Completa codigo y una clave de al menos 6 caracteres."));
    exit;
}

if ($clave_nueva !== $repetir_clave_nueva) {
    header("Location: restablecer_clave_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("Las claves no coinciden."));
    exit;
}

$stmt = $con->prepare("SELECT _id, NOMBRE, RECUPERACION_CODIGO, RECUPERACION_EXPIRA FROM clientes WHERE _id = ? AND VISIBLE = 1 LIMIT 1");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$cliente = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cliente || empty($cliente['RECUPERACION_CODIGO']) || strtotime($cliente['RECUPERACION_EXPIRA']) < time()) {
    header("Location: restablecer_clave_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("El codigo vencio. Solicita uno nuevo."));
    exit;
}

if (!password_verify($codigo, $cliente['RECUPERACION_CODIGO'])) {
    header("Location: restablecer_clave_cliente.php?cliente=" . $cliente_id . "&error=" . urlencode("El codigo no es correcto."));
    exit;
}

$clave_hash = password_hash($clave_nueva, PASSWORD_BCRYPT);
$stmt = $con->prepare("UPDATE clientes SET CLAVE = ?, RECUPERACION_CODIGO = NULL, RECUPERACION_EXPIRA = NULL WHERE _id = ?");
$stmt->bind_param("si", $clave_hash, $cliente_id);
$stmt->execute();
$stmt->close();

set_cliente_cookie(CLIENTE_COOKIE, $cliente['_id']);
set_cliente_cookie(CLIENTE_NOMBRE_COOKIE, $cliente['NOMBRE']);

header("Location: cliente_reservas.php?e=" . urlencode("Tu clave fue actualizada correctamente."));
exit;
?>
