<?php
include 'config.php';

$idEvento = intval($_GET['idEvento'] ?? 0);

$sql = "SELECT MONTO, FECHA, DEJA FROM senias WHERE id_TURNO = $idEvento ORDER BY FECHA DESC";
$res = mysqli_query($con, $sql);

$senias = [];

while ($row = mysqli_fetch_assoc($res)) {
    $senias[] = $row;
}

header('Content-Type: application/json');
echo json_encode($senias);
