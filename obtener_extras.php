<?php
require("config.php");

$idEvento = $_GET['idEvento'] ?? null;


$extras = [];

if ($idEvento) {
    $sql = "SELECT * FROM extras WHERE id_TURNO = ?";
    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "i", $idEvento);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $extras[] = [
            "monto" => $row['MONTO'],
            "fecha" => $row['FECHA'],
            "detalle" => $row['DETALLE']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($extras);
?>