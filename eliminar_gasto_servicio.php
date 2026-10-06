<?php
include 'config.php';

header('Content-Type: application/json');

$response = array(
    'success' => false,
    'message' => 'No se pudo eliminar el gasto de servicio.'
);

$tipoUsuario = null;

if (isset($_COOKIE['TIPO'])) {
    $partsTipo = explode('|', $_COOKIE['TIPO']);

    if (count($partsTipo) === 2) {
        $valorTipo = $partsTipo[0];
        $firmaTipo = $partsTipo[1];
        $firmaValida = hash_hmac('sha256', $valorTipo, 'clave_secreta');

        if (hash_equals($firmaTipo, $firmaValida)) {
            $tipoUsuario = $valorTipo;
        }
    }
}

if ($tipoUsuario !== 'admin') {
    $response['message'] = 'No tenes permisos para eliminar gastos de servicio.';
    echo json_encode($response);
    exit;
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $response['message'] = 'No se recibio un ID valido.';
    echo json_encode($response);
    exit;
}

$gastoServicioId = (int) $_GET['id'];

$sql = "DELETE FROM gastos_servicios WHERE _id = ?";
$stmt = mysqli_prepare($con, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $gastoServicioId);

    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            $response['success'] = true;
            $response['message'] = 'Gasto de servicio eliminado correctamente.';
        } else {
            $response['message'] = 'No se encontro el gasto de servicio.';
        }
    } else {
        $response['message'] = 'Error al eliminar el gasto de servicio: ' . mysqli_error($con);
    }

    mysqli_stmt_close($stmt);
} else {
    $response['message'] = 'Error al preparar la consulta: ' . mysqli_error($con);
}

mysqli_close($con);
echo json_encode($response);
?>
