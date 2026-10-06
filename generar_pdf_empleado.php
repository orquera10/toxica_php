<?php
require_once('vendor/autoload.php');
include('config.php'); // Asegúrate de que este archivo contiene la conexión a la base de datos

// Recoger parámetros de la URL
$empleado_id = isset($_GET['empleado']) ? $_GET['empleado'] : 'todos';
$fecha_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-d', strtotime('last Sunday'));
$fecha_fin = isset($_GET['fin']) ? $_GET['fin'] : date('Y-m-d');

// Convertir las fechas al formato d-m-Y
$fecha_inicio_db = date('d-m-Y', strtotime($fecha_inicio));
$fecha_fin_db = date('d-m-Y', strtotime($fecha_fin));

// Consultar las ventas
$query_ventas = "SELECT t.FECHA, t.MONTO, u.NOMBRE 
                 FROM turnos_personal t 
                 JOIN usuarios u ON t.id_USER = u._id
                 WHERE STR_TO_DATE(t.FECHA, '%d-%m-%Y') BETWEEN STR_TO_DATE('$fecha_inicio_db', '%d-%m-%Y') 
                 AND STR_TO_DATE('$fecha_fin_db', '%d-%m-%Y')";

if ($empleado_id !== 'todos') {
    $query_ventas .= " AND t.id_USER = '$empleado_id'";
}

$query_ventas .= " ORDER BY t.FECHA";
$result_ventas = mysqli_query($con, $query_ventas);

// Verificar si la consulta se ejecutó correctamente
if (!$result_ventas) {
    die('Error en la consulta de ventas: ' . mysqli_error($con));
}

// Consultar los pagos
$query_pagos = "SELECT p.FECHA, p.MONTO, u.NOMBRE 
               FROM pago_empleado p 
               JOIN usuarios u ON p.id_USER = u._id 
               WHERE STR_TO_DATE(p.FECHA, '%d-%m-%Y') BETWEEN STR_TO_DATE('$fecha_inicio_db', '%d-%m-%Y') 
               AND STR_TO_DATE('$fecha_fin_db', '%d-%m-%Y')";

if ($empleado_id !== 'todos') {
    $query_pagos .= " AND p.id_USER = '$empleado_id'";
}

$query_pagos .= " ORDER BY p.FECHA";
$result_pagos = mysqli_query($con, $query_pagos);

// Verificar si la consulta se ejecutó correctamente
if (!$result_pagos) {
    die('Error en la consulta de pagos: ' . mysqli_error($con));
}

// Inicializar variables
$total_ventas = 0;
$total_pagos = 0;
$fechas_ventas = [];
$monto_ventas = [];
$empleados_ventas = [];
$fechas_pagos = [];
$monto_pagos = [];
$empleados_pagos = [];

// Procesar resultados de ventas
while ($row = mysqli_fetch_assoc($result_ventas)) {
    $fechas_ventas[] = $row['FECHA'];
    $monto_ventas[] = $row['MONTO'];
    $empleados_ventas[] = $row['NOMBRE'];
    $total_ventas += $row['MONTO'];
}

// Procesar resultados de pagos
while ($row = mysqli_fetch_assoc($result_pagos)) {
    $fechas_pagos[] = $row['FECHA'];
    $monto_pagos[] = $row['MONTO'];
    $empleados_pagos[] = $row['NOMBRE'];
    $total_pagos += $row['MONTO'];
}

// Crear el objeto TCPDF
$pdf = new TCPDF();
$pdf->AddPage();

// Establecer título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Informe de Ventas y Pagos a Empleados', 0, 1, 'C');

// Establecer la fuente para el contenido
$pdf->SetFont('helvetica', '', 12);

// Generar contenido para el PDF con el período
$html = "<h4>Período: del $fecha_inicio_db al $fecha_fin_db</h4>";

// ** Tabla de Ventas **
$html .= "<h5>Ventas por Empleado</h5>";
$html .= "<table border='1' cellpadding='5'>
            <tr>
                <th>Fecha</th>
                <th>Empleado</th>
                <th>Monto Venta</th>
            </tr>";

foreach ($fechas_ventas as $index => $fecha) {
    $html .= "<tr>
                <td>$fecha</td>
                <td>{$empleados_ventas[$index]}</td>
                <td>$" . number_format($monto_ventas[$index], 2) . "</td>
              </tr>";
}

$html .= "</table><br>";

// ** Tabla de Pagos **
$html .= "<h5>Pagos a Empleados</h5>";
$html .= "<table border='1' cellpadding='5'>
            <tr>
                <th>Fecha</th>
                <th>Empleado</th>
                <th>Monto Pago</th>
            </tr>";

foreach ($fechas_pagos as $index => $fecha) {
    $html .= "<tr>
                <td>$fecha</td>
                <td>{$empleados_pagos[$index]}</td>
                <td>$" . number_format($monto_pagos[$index], 2) . "</td>
              </tr>";
}

$html .= "</table><br>";

// ** Totales al final **
$html .= "<h4>Total Ventas: $" . number_format($total_ventas, 2) . "</h4>";
$html .= "<h4>Total Pagos: $" . number_format($total_pagos, 2) . "</h4>";
$html .= "<h4>Diferencia (Ventas - Pagos): $" . number_format($total_ventas - $total_pagos, 2) . "</h4>";

// Escribir contenido al PDF
$pdf->writeHTML($html);

// Generar el PDF y ofrecerlo para descargar
$pdf->Output('informe_ventas_pagos.pdf', 'D');
?>
