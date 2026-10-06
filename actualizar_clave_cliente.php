<?php
require_once 'cliente_auth.php';

$cliente = requerir_cliente();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_perfil.php");
    exit;
}

$clave_actual = $_POST['clave_actual'] ?? '';
$clave_nueva = $_POST['clave_nueva'] ?? '';
$repetir_clave_nueva = $_POST['repetir_clave_nueva'] ?? '';

if ($clave_actual === '' || strlen($clave_nueva) < 6) {
    header("Location: cliente_perfil.php?error=" . urlencode("Completa la clave actual y una nueva clave de al menos 6 caracteres."));
    exit;
}

if ($clave_nueva !== $repetir_clave_nueva) {
    header("Location: cliente_perfil.php?error=" . urlencode("Las claves no coinciden."));
    exit;
}

$cliente_id = (int) $cliente['_id'];
$stmt = $con->prepare("SELECT CLAVE FROM clientes WHERE _id = ? AND VISIBLE = 1 LIMIT 1");
$stmt->bind_param("i", $cliente_id);
$stmt->execute();
$resultado = $stmt->get_result();
$cliente_db = $resultado->fetch_assoc();
$stmt->close();

if (!$cliente_db || empty($cliente_db['CLAVE']) || !password_verify($clave_actual, $cliente_db['CLAVE'])) {
    header("Location: cliente_perfil.php?error=" . urlencode("La clave actual no es correcta."));
    exit;
}

$clave_hash = password_hash($clave_nueva, PASSWORD_BCRYPT);
$stmt = $con->prepare("UPDATE clientes SET CLAVE = ? WHERE _id = ?");
$stmt->bind_param("si", $clave_hash, $cliente_id);
$ok = $stmt->execute();
$stmt->close();

if (!$ok) {
    header("Location: cliente_perfil.php?error=" . urlencode("No se pudo actualizar la clave."));
    exit;
}

header("Location: cliente_perfil.php?success=" . urlencode("La clave se actualizo correctamente."));
exit;
?>
