<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config.php';

$usuarioCookie = explode('|', $_COOKIE['USUARIO'] ?? '', 2);
$tipoCookie = explode('|', $_COOKIE['TIPO'] ?? '', 2);
$usuarioValido = count($usuarioCookie) === 2
    && hash_equals(hash_hmac('sha256', $usuarioCookie[0], 'clave_secreta'), $usuarioCookie[1]);
$tipoValido = count($tipoCookie) === 2
    && hash_equals(hash_hmac('sha256', $tipoCookie[0], 'clave_secreta'), $tipoCookie[1])
    && in_array($tipoCookie[0], ['admin', 'user'], true);

if (!$usuarioValido || !$tipoValido) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'La sesión no es válida.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
    exit;
}

$idProducto = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
$visible = filter_input(INPUT_POST, 'visible', FILTER_VALIDATE_INT);

if (!$idProducto || !in_array($visible, [0, 1], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Los datos enviados no son válidos.']);
    exit;
}

$consulta = mysqli_prepare($con, 'UPDATE producto SET EN_CATALOGO = ? WHERE _id = ? AND VISIBLE = 1');
if (!$consulta) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la actualización.']);
    exit;
}

mysqli_stmt_bind_param($consulta, 'ii', $visible, $idProducto);
$actualizado = mysqli_stmt_execute($consulta);

if (!$actualizado) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo actualizar el producto.']);
    exit;
}

echo json_encode([
    'success' => true,
    'visible' => (bool) $visible,
    'message' => $visible === 1 ? 'El producto se mostrará en el catálogo.' : 'El producto se ocultó del catálogo.',
]);
