<?php
require_once __DIR__ . '/config.php';

const CLIENTE_COOKIE = 'CLIENTE_ID';
const CLIENTE_NOMBRE_COOKIE = 'CLIENTE_NOMBRE';
const CLIENTE_COOKIE_SECRET = 'clave_secreta_cliente';

function firmar_cliente_cookie($valor)
{
    return hash_hmac('sha256', (string) $valor, CLIENTE_COOKIE_SECRET);
}

function set_cliente_cookie($nombre, $valor)
{
    $firma = firmar_cliente_cookie($valor);
    setcookie($nombre, $valor . '|' . $firma, time() + 32400, '/', '', false, true);
}

function leer_cliente_cookie($nombre)
{
    if (!isset($_COOKIE[$nombre])) {
        return null;
    }

    $partes = explode('|', urldecode($_COOKIE[$nombre]), 2);
    if (count($partes) !== 2) {
        return null;
    }

    [$valor, $firma] = $partes;
    if (!hash_equals(firmar_cliente_cookie($valor), $firma)) {
        return null;
    }

    return $valor;
}

function cliente_actual()
{
    global $con;

    $cliente_id = leer_cliente_cookie(CLIENTE_COOKIE);
    if (!$cliente_id) {
        return null;
    }

    $stmt = $con->prepare("SELECT _id, NOMBRE, MAIL, TELEFONO FROM clientes WHERE _id = ? AND VISIBLE = 1");
    $stmt->bind_param("i", $cliente_id);
    $stmt->execute();
    $resultado = $stmt->get_result();
    $cliente = $resultado->fetch_assoc();
    $stmt->close();

    return $cliente ?: null;
}

function requerir_cliente()
{
    $cliente = cliente_actual();
    if (!$cliente) {
        header("Location: index.php?error=" . urlencode("Inicia sesion para reservar tu turno."));
        exit;
    }

    return $cliente;
}

function cerrar_sesion_cliente()
{
    setcookie(CLIENTE_COOKIE, "", time() - 32400, '/', '', false, true);
    setcookie(CLIENTE_NOMBRE_COOKIE, "", time() - 32400, '/', '', false, true);
}
?>
