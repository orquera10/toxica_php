<?php
require 'vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
include 'config.php';

$fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
$fechaFin = $_GET['fecha_fin'] ?? date('Y-m-t');
$tipoSeleccionado = $_GET['tipo_producto'] ?? '';

ob_start();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Informe de Stock Mensual</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 20px;
        }
        h1 {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 1.5rem;
            margin-top: 30px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>Informe de Stock Mensual</h1>
    <p style="text-align:center;">
        <strong>Desde:</strong> <?php echo $fechaInicio; ?> &nbsp; 
        <strong>Hasta:</strong> <?php echo $fechaFin; ?>
        <?php if ($tipoSeleccionado !== ''): ?>
            <br><strong>Tipo:</strong> <?php echo htmlspecialchars($tipoSeleccionado); ?>
        <?php endif; ?>
    </p>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Producto</th>
                <th>Stock Inicial</th>
                <th>Ingresos</th>
                <th>Egresos</th>
                <th>Stock Final</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sql = "SELECT 
                p._id AS id_producto,
                p.NOMBRE,
                p.STOCK AS stock_final,
                COALESCE(SUM(CASE 
                    WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y') BETWEEN ? AND ? THEN s.INGRESO 
                    ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE 
                    WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y') BETWEEN ? AND ? THEN s.EGRESO 
                    ELSE 0 END), 0) AS total_egresos,
                (p.STOCK 
                 - COALESCE(SUM(CASE 
                     WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y') BETWEEN ? AND ? THEN s.INGRESO 
                     ELSE 0 END), 0)
                 + COALESCE(SUM(CASE 
                     WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y') BETWEEN ? AND ? THEN s.EGRESO 
                     ELSE 0 END), 0)
                ) AS stock_inicial
                FROM producto p
                LEFT JOIN stock s ON s.id_PRODUCTO = p._id
                WHERE 1=1";
            $params = [$fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin, $fechaInicio, $fechaFin];
            $types = "ssssssss";

            if ($tipoSeleccionado !== '') {
                $sql .= " AND p.TIPO = ?";
                $params[] = $tipoSeleccionado;
                $types .= "s";
            }

            $sql .= " GROUP BY p._id, p.NOMBRE, p.STOCK
                ORDER BY p.IMPORTANCIA";

            $stmt = $con->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>{$row['id_producto']}</td>";
                echo "<td>{$row['NOMBRE']}</td>";
                echo "<td>{$row['stock_inicial']}</td>";
                echo "<td>{$row['total_ingresos']}</td>";
                echo "<td>{$row['total_egresos']}</td>";
                echo "<td>{$row['stock_final']}</td>";
                echo "</tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
$html = ob_get_clean();

$options = new Options();
$options->set("isHtml5ParserEnabled", true);
$options->set("isPhpEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("informe-stock-{$fechaInicio}_{$fechaFin}.pdf", array("Attachment" => 0));
?>