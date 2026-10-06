<?php
require 'vendor/autoload.php'; // Asegúrate de que Dompdf esté instalado vía Composer
use Dompdf\Dompdf;
use Dompdf\Options;

// Conexión a la base de datos
include 'config.php';

$mesSeleccionado = isset($_GET['date']) ? $_GET['date'] : date("Y-m");

// Inicializar variables para evitar warnings
$totalEfectivo = 0;
$totalTransferencias = 0;
$totalEfectivoGastos = 0;
$totalTransferenciaGastos = 0;
$totalEfectivoGastosServicios = 0;
$totalTransferenciaGastosServicios = 0;

ob_start(); // Comienza a capturar la salida del buffer
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Informe Mensual</title>
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

        th,
        td {
            border: 1px solid #333;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }

        .tarjetaTotal {
            border: 1px solid #333;
            padding: 20px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }

        .divisor {
            border-bottom: 1px solid #ccc;
            margin-bottom: 15px;
        }

        .totales {
            font-weight: bold;
            font-size: 1.4rem;
            text-align: right;
        }

        .d-flex {
            display: flex;
            justify-content: space-between;
        }

        .ps-3 {
            padding-left: 10px;
        }

        .p-1 {
            padding: 5px;
        }

        .m-0 {
            margin: 0;
        }
        
        .page-break-before {
            page-break-before: always;
        }

        .mb-3 {
            margin-bottom: 15px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }
    </style>
</head>

<body>
    <h1>Informe Mensual - <?php echo $mesSeleccionado; ?></h1>

    <!-- Tabla de Ventas -->
    <h2>Ventas por Cancha</h2>
    <table>
        <thead>
            <tr>
                <th>ID Cancha</th>
                <th>Nombre</th>
                <th>Seña</th>
                <th>Cancha</th>
                <th>Extra</th>
                <th>Productos</th>
                <th>Total</th>
                <th>Efectivo</th>
                <th>Transferencia</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Definimos las variables antes de usarlas
            $total_cancha = 0;
            $total_senia = 0;
            $total_productos = 0;
            $total_extra = 0;
            $total_general = 0;
            $total_efectivo = 0;
            $total_transferencia = 0;

            $sqlVentas = "SELECT cn._id AS id_cancha, cn.NOMBRE AS nombre_cancha, 
                SUM(tk.SENIA) AS total_senia, SUM(tk.TOTAL_CANCHA) AS total_cancha,
                SUM(tk.EXTRA) AS total_extra, SUM(tk.TOTAL_DETALLE) AS total_productos,
                SUM(tk.TOTAL) AS total_general, SUM(tk.PAGO_EFECTIVO) AS total_efectivo,
                SUM(tk.PAGO_TRANSFERENCIA) AS total_transferencia
                FROM turnos t
                INNER JOIN ticket tk ON t._id = tk.id_TURNO
                INNER JOIN canchas cn ON t.id_CANCHA = cn._id
                WHERE DATE_FORMAT(STR_TO_DATE(tk.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'
                AND t.FINALIZADO = 1
                GROUP BY cn._id, cn.NOMBRE";

            $resultado = mysqli_query($con, $sqlVentas);
            while ($row = mysqli_fetch_assoc($resultado)) {
                echo "<tr>
                    <td>{$row['id_cancha']}</td>
                    <td>{$row['nombre_cancha']}</td>
                    <td>{$row['total_senia']}</td>
                    <td>{$row['total_cancha']}</td>
                    <td>{$row['total_extra']}</td>
                    <td>{$row['total_productos']}</td>
                    <td>{$row['total_general']}</td>
                    <td>{$row['total_efectivo']}</td>
                    <td>{$row['total_transferencia']}</td>
                </tr>";
                $total_cancha += $row['total_cancha'];
                $total_senia += $row['total_senia'];
                $total_productos += $row['total_productos'];
                $total_extra += $row['total_extra'];
                $total_general += $row['total_general'];
                $total_efectivo += $row['total_efectivo'];
                $total_transferencia += $row['total_transferencia'];
                
            }

            // Asegúrate de que estas variables tienen un valor calculado antes de usarlas en el PDF
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6"><strong>Totales</strong></td>
                <td><strong><?php echo $total_general; ?></strong></td>
                <td><strong><?php echo $total_efectivo; ?></strong></td>
                <td><strong><?php echo $total_transferencia; ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tabla de Señas -->
    <h2>Señas por Cancha</h2>
    <table>
        <thead>
            <tr>
                <th>Cancha</th>
                <th>Total Señas</th>
                <th>Transferencia</th>
                <th>Efectivo</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $totalSenia = 0;
            $transferenciaSenia = 0;
            $efectivoSenia = 0;
            $sqlSenias = "SELECT cn.NOMBRE AS nombre_cancha, 
                            SUM(s.MONTO) AS totalSenia, 
                            SUM(s.TRANSFERENCIA) AS transferenciaSenia, 
                            SUM(s.EFECTIVO) AS efectivoSenia
                        FROM senias s
                        INNER JOIN turnos t ON s.id_TURNO = t._id
                        INNER JOIN canchas cn ON t.id_CANCHA = cn._id
                        WHERE DATE_FORMAT(STR_TO_DATE(s.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'
                        GROUP BY cn.NOMBRE";

            $resultado = mysqli_query($con, $sqlSenias);

            while ($row = mysqli_fetch_assoc($resultado)) {
                echo "<tr>
                    <td>{$row['nombre_cancha']}</td>
                    <td>{$row['totalSenia']}</td>
                    <td>{$row['transferenciaSenia']}</td>
                    <td>{$row['efectivoSenia']}</td>
                </tr>";
                $totalSenia += $row['totalSenia'];
                $transferenciaSenia += $row['transferenciaSenia'];
                $efectivoSenia += $row['efectivoSenia'];
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="1"><strong>Totales</strong></td>
                <td><strong><?php echo $totalSenia; ?></strong></td>
                <td><strong><?php echo $transferenciaSenia; ?></strong></td>
                <td><strong><?php echo $efectivoSenia; ?></strong></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tabla de Gastos -->
    <h2>Gastos del Mes</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Efectivo</th>
                <th>Transferencia</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sqlGastos = "SELECT g._id, u.USUARIO AS nombre_usuario, g.NOMBRE, g.FECHA, g.MONTO, g.EFECTIVO, g.TRANSFERENCIA
                          FROM gastos g
                          INNER JOIN usuarios u ON g.id_USUARIO = u._id
                          WHERE DATE_FORMAT(STR_TO_DATE(g.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'";

            $resultado = mysqli_query($con, $sqlGastos);
            $totalGastos = 0;
            $totalEfectivoGastos = 0;
            $totalTransferenciaGastos = 0;

            while ($row = mysqli_fetch_assoc($resultado)) {
                echo "<tr>
                    <td>{$row['_id']}</td>
                    <td>{$row['nombre_usuario']}</td>
                    <td>{$row['NOMBRE']}</td>
                    <td>{$row['FECHA']}</td>
                    <td>{$row['EFECTIVO']}</td>
                    <td>{$row['TRANSFERENCIA']}</td>
                    <td>{$row['MONTO']}</td>
                </tr>";
                $totalEfectivoGastos += $row['EFECTIVO'];
                $totalTransferenciaGastos += $row['TRANSFERENCIA'];
                $totalGastos += $row['MONTO'];
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Total Gastos</td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalGastos, 0, '', '.'); ?></td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalTransferenciaGastos, 0, '', '.'); ?></td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalEfectivoGastos, 0, '', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tabla de Gastos de Servicios -->
    <h2>Gastos de Servicios del Mes</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Efectivo</th>
                <th>Transferencia</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sqlGastosServicios = "SELECT gs._id, u.USUARIO AS nombre_usuario, gs.NOMBRE, gs.FECHA, gs.MONTO, gs.EFECTIVO, gs.TRANSFERENCIA
                                FROM gastos_servicios gs
                                INNER JOIN usuarios u ON gs.id_USUARIO = u._id
                                WHERE DATE_FORMAT(STR_TO_DATE(gs.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'";

            $resultado = mysqli_query($con, $sqlGastosServicios);
            $totalGastosServicios = 0;
            $totalEfectivoGastosServicios = 0;
            $totalTransferenciaGastosServicios = 0;

            while ($row = mysqli_fetch_assoc($resultado)) {
                echo "<tr>
                    <td>{$row['_id']}</td>
                    <td>{$row['nombre_usuario']}</td>
                    <td>{$row['NOMBRE']}</td>
                    <td>{$row['FECHA']}</td>
                    <td class='text-danger'>$" . number_format($row['MONTO'], 0, '', '.') . "</td>
                    <td class='text-danger'>$" . number_format($row['EFECTIVO'], 0, '', '.') . "</td>
                    <td class='text-danger'>$" . number_format($row['TRANSFERENCIA'], 0, '', '.') . "</td>
                </tr>";
                $totalGastosServicios += $row['MONTO'];
                $totalEfectivoGastosServicios += $row['EFECTIVO'];
                $totalTransferenciaGastosServicios += $row['TRANSFERENCIA'];
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" style="text-align: right; font-weight: bold;">Total Gastos de Servicios</td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalGastosServicios, 0, '', '.'); ?></td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalEfectivoGastosServicios, 0, '', '.'); ?></td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalTransferenciaGastosServicios, 0, '', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Tabla de Pagos a Empleados -->
    <h2>Pagos a Empleados</h2>
    <table>
        <thead>
            <tr>
                <th>Empleado</th>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $sqlPagos = "SELECT u.NOMBRE AS nombre_usuario, pe.FECHA, pe.MONTO
                         FROM pago_empleado pe
                         INNER JOIN usuarios u ON pe.id_USER = u._id
                         WHERE DATE_FORMAT(STR_TO_DATE(pe.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'";

            $resultado = mysqli_query($con, $sqlPagos);
            $totalPagos = 0;

            while ($row = mysqli_fetch_assoc($resultado)) {
                echo "<tr>
                    <td>{$row['nombre_usuario']}</td>
                    <td>{$row['FECHA']}</td>
                    <td class='text-danger'>$" . number_format($row['MONTO'], 0, '', '.') . "</td>
                </tr>";
                $totalPagos += $row['MONTO'];
            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right; font-weight: bold;">Total</td>
                <td class="text-danger" style="font-weight: bold;">$<?php echo number_format($totalPagos, 0, '', '.'); ?></td>
            </tr>
        </tfoot>
    </table>

    <!-- Ingresos y Egresos -->
    <h1 class="page-break-before" style="text-align: center; margin: 20px 0;">RESUMEN</h1>
    <div class="tarjetaTotal">
        <p class="h2">Ingresos</p>
        <div class="divisor"></div>
        <p><strong>Total en Cancha (Cancha - Seña):</strong> $<?php echo number_format($total_cancha - $total_senia, 0, '', '.'); ?></p>
        <p><strong>Total en Señas:</strong> $<?php echo number_format($totalSenia, 0, '', '.'); ?></p>
        <p><strong>Total en Productos:</strong> $<?php echo number_format($total_productos, 0, '', '.'); ?></p>
        <p><strong>Total en Extras:</strong> $<?php echo number_format($total_extra, 0, '', '.'); ?></p>
        <p style="font-weight: bold; font-size: 1.1rem;"><strong>TOTAL INGRESOS:</strong> $<?php echo number_format($total_cancha - $total_senia + $totalSenia + $total_productos + $total_extra, 0, '', '.'); ?></p>
    </div>

    <div class="tarjetaTotal">
        <p class="h2">Egresos</p>
        <div class="divisor"></div>
        <p><strong>Total Gastos:</strong> $<?php echo number_format($totalGastos, 0, '', '.'); ?></p>
        <p><strong>Total Gastos de Servicios:</strong> $<?php echo number_format($totalGastosServicios, 0, '', '.'); ?></p>
        <p><strong>Total Pagos a Empleados:</strong> $<?php echo number_format($totalPagos, 0, '', '.'); ?></p>
        <p style="font-weight: bold; font-size: 1.1rem; color: #dc3545;"><strong>TOTAL EGRESOS:</strong> $<?php echo number_format($totalGastos + $totalGastosServicios + $totalPagos, 0, '', '.'); ?></p>
    </div>

    <div class="tarjetaTotal" style="background-color:#253915; color: white;">
        <p class="h2">Beneficio (Ingresos - Egresos)</p>
        <div class="w-100 divisor mb-3"></div>
        <div class="d-flex">
            <p style="font-weight: bold; font-size:1.4rem"><span>Total:</span>
                <?php 
                $totalIngresos = $total_general + $totalSenia;
                $totalEgresos = $totalGastos + $totalGastosServicios + $totalPagos;
                $beneficio = $totalIngresos - $totalEgresos;
                $claseBeneficio = $beneficio >= 0 ? 'text-success' : 'text-danger';
                ?>
                <span class="<?php echo $claseBeneficio; ?>">
                    $<?php echo number_format($beneficio, 0, '', '.'); ?>
                </span>
            </p>
            <div class="ps-3 p-1 m-0">
                <p class="p-0 m-0">Efectivo:
                    <span class="text-white">
                        <?php 
                        $ingresosEfectivo = $total_efectivo + $efectivoSenia;
                        $egresosEfectivo = $totalEfectivoGastos + $totalEfectivoGastosServicios + $totalPagos;
                        $beneficioEfectivo = $ingresosEfectivo - $egresosEfectivo;
                        echo number_format($beneficioEfectivo, 0, '', '.') . ' $';
                        ?>
                    </span>
                </p>
                <p class="p-0 m-0">Transferencias:
                    <span class="text-white">
                        <?php 
                        $ingresosTransferencia = $total_transferencia + $transferenciaSenia;
                        $egresosTransferencia = $totalTransferenciaGastos + $totalTransferenciaGastosServicios;
                        $beneficioTransferencia = $ingresosTransferencia - $egresosTransferencia;
                        echo number_format($beneficioTransferencia, 0, '', '.') . ' $';
                        ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

</body>

</html>

<?php
$html = ob_get_clean();

// Configuración de Dompdf
$options = new Options();
$options->set("isHtml5ParserEnabled", true);
$options->set("isPhpEnabled", true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("informe-mensual-{$mesSeleccionado}.pdf", array("Attachment" => 0));
?>