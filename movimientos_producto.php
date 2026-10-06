<?php
include 'config.php';

$id_producto = $_GET['id_producto'] ?? 0;
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

header('Content-Type: application/json');

$producto = $con->query("SELECT NOMBRE, URL_IMG FROM producto WHERE _id = " . intval($id_producto))->fetch_assoc();

$movimientos = [];
if ($fecha_inicio && $fecha_fin && $id_producto) {
    $stmt = $con->prepare("SELECT FECHA, INGRESO, EGRESO, DETALLE FROM stock WHERE id_PRODUCTO = ? AND STR_TO_DATE(FECHA, '%d-%m-%Y %H:%i:%s') BETWEEN ? AND ? ORDER BY STR_TO_DATE(FECHA, '%d-%m-%Y %H:%i:%s')");
    $fecha_inicio_sql = $fecha_inicio . " 00:00:00";
    $fecha_fin_sql = $fecha_fin . " 23:59:59";
    $stmt->bind_param("iss", $id_producto, $fecha_inicio_sql, $fecha_fin_sql);
    $stmt->execute();
    $res = $stmt->get_result();
    while($row = $res->fetch_assoc()){
        $movimientos[] = $row;
    }
}

echo json_encode([
    'producto' => $producto,
    'movimientos' => $movimientos
]);