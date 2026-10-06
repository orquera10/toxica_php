<?php
// Incluir archivos necesarios
$pageTitle = "Informe Ventas Mensual";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php'; // Configuración de la conexión a la base de datos

$totalTransferencias = $totalEfectivo = $totalGastos = $totalSenias = $total_senia = $total_cancha = $total_extra = $total_productos = $total_general = $total_efectivo = $total_transferencia = 0;
$esAdmin = isset($tipo_usuario) && $tipo_usuario === 'admin';

// Inicializar $resultado como un array vacío
$resultado = [];
$mesSeleccionado = date("Y-m");

// Verificar si se ha seleccionado un mes
if (isset($_GET['date'])) {
    $mesSeleccionado = $_GET['date']; // Se espera en formato 'YYYY-MM'

    $sql = "SELECT cn._id AS id_cancha, 
                cn.NOMBRE AS nombre_cancha, 
                SUM(tk.SENIA) AS total_senia, 
                SUM(tk.TOTAL_CANCHA) AS total_cancha, 
                SUM(tk.EXTRA) AS total_extra, 
                SUM(tk.TOTAL_DETALLE) AS total_productos, 
                SUM(tk.TOTAL) AS total_general, 
                SUM(tk.PAGO_EFECTIVO) AS total_efectivo, 
                SUM(tk.PAGO_TRANSFERENCIA) AS total_transferencia
            FROM turnos t
            INNER JOIN ticket tk ON t._id = tk.id_TURNO
            INNER JOIN canchas cn ON t.id_CANCHA = cn._id
            WHERE DATE_FORMAT(STR_TO_DATE(tk.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'
            AND t.FINALIZADO = 1
            GROUP BY cn._id, cn.NOMBRE";

    // Ejecutar la consulta
    $resultado_query = mysqli_query($con, $sql);

    // Almacenar los resultados en un array
    while ($fila = mysqli_fetch_assoc($resultado_query)) {
        $resultado[] = $fila;
    }
}
?>

<div class="container tablasInformes">
    <p class="mt-5 h4">Informe Mensual</p>

    <!-- Formulario para seleccionar el mes -->
    <form method="GET" action="">
        <div class="form-group row formInfMensual my-4">
            <div class="col-md-5 row">
                <label for="date" class="col-12 col-form-label m-0">Seleccionar mes y año:</label>
                <div class="col-12 my-2">
                    <input type="month" id="date" name="date" class="form-control"
                        value="<?php echo isset($mesSeleccionado) ? $mesSeleccionado : date('Y-m'); ?>" min="2020-01">
                </div>
                <div class="col-12 my-2">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <button type="submit" class="btn btn-primary w-100">Ver Informe</button>
                        </div>
                        <div class="col-md-6">
                            <button type="button" class="btn btn-danger btnBaja w-100" onclick="abrirModalGastoServicio()">
                                + Gasto Servicio
                            </button>
                        </div>
                        <div class="col-12">
                            <a href="generar_pdf_mensual.php?date=<?= $mesSeleccionado ?>" class="btn btn-success w-100 mt-2" target="_blank">
                                Descargar PDF
                            </a>
                        </div>
                    </div>
                </div>


            </div>
        </div>
    </form>

    <?php if (isset($_GET['date'])): ?>

        <div class="col-12 divisor my-3"></div>

        <div class="row mt-5">
            <div class="col-12">
                <p>Detalle de ventas del mes</p>
                <!-- Tabla para mostrar los turnos -->
                <div class="rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                    <table class="table">
                        <!-- Cabecera de la tabla -->
                        <thead>
                            <tr class="align-middle">
                                <th>ID Cancha</th>
                                <th>Nombre Cancha</th>
                                <th>Total Cancha</th>
                                <th>Total Seña</th>
                                <th>Total Extra</th>
                                <th>Total Productos</th>
                                <th>Total General</th>
                                <th>Total Pago Efectivo</th>
                                <th>Total Pago Transferencia</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (!empty($resultado)) {
                                // Mostrar los resultados en la tabla
                                foreach ($resultado as $filaTurno) {
                                    echo "<tr class='align-middle'>";
                                    echo "<td>" . number_format($filaTurno['id_cancha'], 0, '', '.') . "</td>";
                                    echo "<td>" . $filaTurno['nombre_cancha'] . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_cancha'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_senia'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_extra'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_productos'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_general'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_efectivo'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaTurno['total_transferencia'], 0, '', '.') . "</td>";
                                    echo "</tr>";

                                    // Sumar a los totales generales
                                    $total_cancha += $filaTurno['total_cancha'];
                                    $total_senia += $filaTurno['total_senia'];
                                    $total_extra += $filaTurno['total_extra'];
                                    $total_productos += $filaTurno['total_productos'];
                                    $total_general += $filaTurno['total_general'];
                                    $total_efectivo += $filaTurno['total_efectivo'];
                                    $total_transferencia += $filaTurno['total_transferencia'];
                                }
                            } else {
                                echo "<tr><td colspan='9' class='text-center'>No hay resultados para este mes</td></tr>";
                            }
                            ?>
                        </tbody>
                        <!-- Pie de la tabla -->
                        <tfoot>
                            <tr>
                                <td colspan="2" style="font-weight: bold;">Totales</td>
                                <td style="font-weight: bold;">$<?php echo number_format($total_cancha, 0, '', '.'); ?></td>
                                <td style="font-weight: bold;">- $<?php echo number_format($total_senia, 0, '', '.'); ?></td>
                                <td style="font-weight: bold;">$<?php echo number_format($total_extra, 0, '', '.'); ?></td>
                                <td style="font-weight: bold;">$<?php echo number_format($total_productos, 0, '', '.'); ?></td>
                                <td style="font-weight: bold;">$<?php echo number_format($total_general, 0, '', '.'); ?></td>
                                <td style="font-weight: bold;">$<?php echo number_format($total_efectivo, 0, '', '.'); ?></td>
                                <td style="font-weight: bold;">$<?php echo number_format($total_transferencia, 0, '', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Tabla para mostrar las señas agrupadas por cancha -->
            <div class="col-md-6">
                <p>Detalle de señas por cancha</p>
                <div class="tablaGastos tablaLimite rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                    <table class="table">
                        <!-- Cabecera de la tabla -->
                        <thead>
                            <tr class="align-middle">
                                <th>Cancha</th>
                                <th>Transferencia</th>
                                <th>Efectivo</th>
                                <th>Total Señas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Consulta para obtener las señas agrupadas por cancha para el mes seleccionado
                            $sqlSenias = "
                    SELECT cn.NOMBRE AS nombre_cancha, 
                           SUM(s.TRANSFERENCIA) AS total_transferencia, 
                           SUM(s.EFECTIVO) AS total_efectivo,
                           SUM(s.MONTO) AS total_senia 
                    FROM senias s
                    INNER JOIN turnos t ON s.id_TURNO = t._id
                    INNER JOIN canchas cn ON t.id_CANCHA = cn._id
                    WHERE DATE_FORMAT(STR_TO_DATE(s.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'
                    GROUP BY cn.NOMBRE
                ";

                            // Ejecutar la consulta
                            $resultado_senias = mysqli_query($con, $sqlSenias);

                            // Verificar si la consulta se ejecutó correctamente
                            if ($resultado_senias) {
                                // Mostrar los resultados en la tabla
                                while ($filaSenia = mysqli_fetch_assoc($resultado_senias)) {
                                    // Sumar el monto al total general
                                    $totalSenias += $filaSenia['total_senia'];
                                    $totalTransferencias += $filaSenia['total_transferencia'];
                                    $totalEfectivo += $filaSenia['total_efectivo'];

                                    // Mostrar los datos en la fila
                                    echo "<tr class='align-middle'>";
                                    echo "<td>" . $filaSenia['nombre_cancha'] . "</td>";
                                    echo "<td>$" . number_format($filaSenia['total_transferencia'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaSenia['total_efectivo'], 0, '', '.') . "</td>";
                                    echo "<td>$" . number_format($filaSenia['total_senia'], 0, '', '.') . "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                // Si hubo un error en la consulta, mostrar un mensaje de error
                                echo "<tr><td colspan='4'>Error al obtener las señas: " . mysqli_error($con) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                        <!-- Pie de la tabla -->
                        <tfoot>
                            <tr class="align-middle">
                                <td style="text-align: right; font-weight: bold; font-size: 0.8rem;">Total</td>
                                <td style='font-weight: bold;font-size: 1rem;'>$<?php echo number_format($totalTransferencias, 0, '', '.'); ?></td>
                                <td style='font-weight: bold;font-size: 1rem;'>$<?php echo number_format($totalEfectivo, 0, '', '.'); ?></td>
                                <td style='font-weight: bold;font-size: 1rem;'>$<?php echo number_format($totalSenias, 0, '', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>


            <!-- Tabla para mostrar los gastos -->
            <div class="col-md-6">
                <p>Detalle de gastos del mes</p>
                <div class="tablaGastos tablaLimite rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                    <table class="table">
                        <!-- Cabecera de la tabla -->
                        <thead>
                            <tr class="align-middle">
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Nombre</th>
                                <th>Fecha</th>
                                <th>Transferencia</th>
                                <th>Efectivo</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Consulta para obtener los gastos para la fecha seleccionada
                            $sqlGastos = "SELECT g._id, g.id_USUARIO, g.NOMBRE, g.FECHA, g.EFECTIVO, g.TRANSFERENCIA, (g.EFECTIVO + g.TRANSFERENCIA) as TOTAL, u.USUARIO AS nombre_usuario 
                                         FROM gastos g 
                                         INNER JOIN usuarios u ON g.id_USUARIO = u._id 
                                         WHERE DATE_FORMAT(STR_TO_DATE(g.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'";

                            // Ejecutar la consulta
                            $resultado_gastos = mysqli_query($con, $sqlGastos);
                            // Verificar si la consulta se ejecutó correctamente
                            if ($resultado_gastos) {
                                // Inicializar la variable para el total general de los montos
                                $totalEfectivoGastos = 0;
                                $totalTransferenciaGastos = 0;
                                $totalGastos = 0;

                                // Mostrar los resultados en la tabla
                                while ($filaGasto = mysqli_fetch_assoc($resultado_gastos)) {
                                    // Sumar los montos a los totales generales
                                    $totalEfectivoGastos += $filaGasto['EFECTIVO'];
                                    $totalTransferenciaGastos += $filaGasto['TRANSFERENCIA'];
                                    $totalGastos += $filaGasto['TOTAL'];

                                    // Mostrar los datos en la fila
                                    echo "<tr class='align-middle'>";
                                    echo "<td>" . number_format($filaGasto['_id'], 0, '', '.') . "</td>";
                                    echo "<td>" . $filaGasto['nombre_usuario'] . "</td>";
                                    echo "<td>" . $filaGasto['NOMBRE'] . "</td>";
                                    echo "<td>" . $filaGasto['FECHA'] . "</td>";
                                    echo "<td class='text-danger'>$" . number_format($filaGasto['TRANSFERENCIA'], 0, '', '.') . "</td>";
                                    echo "<td class='text-danger'>$" . number_format($filaGasto['EFECTIVO'], 0, '', '.') . "</td>";
                                    echo "<td class='text-danger fw-bold'>$" . number_format($filaGasto['TOTAL'], 0, '', '.') . "</td>";                       }
                            } else {
                                // Si hubo un error en la consulta, mostrar un mensaje de error
                                echo "<tr><td colspan='5'>Error al obtener los gastos: " . mysqli_error($con) . "</td></tr>";
                            }
                            ?>
                        </tbody>
                        <!-- Pie de la tabla -->
                        <tfoot>
                            <tr class="align-middle">
                                <td colspan="4" style="text-align: right; font-weight: bold; font-size: 0.8rem;">Total</td>
                                <td class='text-danger' style='font-weight: bold;font-size: 1rem;'>$<?php echo number_format($totalTransferenciaGastos, 0, '', '.'); ?></td>
                                <td class='text-danger' style='font-weight: bold;font-size: 1rem;'>$<?php echo number_format($totalEfectivoGastos, 0, '', '.'); ?></td>
                                <td class='text-danger' style='font-weight: bold;font-size: 1rem;'>$<?php echo number_format($totalGastos, 0, '', '.'); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <?php
            // Realizar la consulta para los pagos de los empleados
            $sqlPagos = "
                        SELECT u.NOMBRE AS nombre_usuario, 
                            pe.FECHA AS fecha_pago, 
                            pe.MONTO AS monto_pago 
                        FROM pago_empleado pe
                        INNER JOIN usuarios u ON pe.id_USER = u._id
                        WHERE DATE_FORMAT(STR_TO_DATE(pe.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'
                        ";

            // Ejecutar la consulta
            $resultado_pagos = mysqli_query($con, $sqlPagos);

            // Verificar si la consulta se ejecutó correctamente
            if ($resultado_pagos) {
                // Mostrar los resultados en una nueva tabla
                echo "<div class='col-md-6'>";
                echo "<p>Detalle de pagos a empleados</p>";
                echo "<div class='tablaPagos tablaLimite rounded tablaTurnosAll my-4 shadow py-2 px-4' style='overflow-x: auto;'>";
                echo "<table class='table'>";
                echo "<thead>";
                echo "<tr class='align-middle'>";
                echo "<th>Empleado</th>";
                echo "<th>Fecha de Pago</th>";
                echo "<th>Monto de Pago</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                // Mostrar los resultados de los pagos
                while ($filaPago = mysqli_fetch_assoc($resultado_pagos)) {
                    echo "<tr class='align-middle'>";
                    echo "<td>" . $filaPago['nombre_usuario'] . "</td>";
                    echo "<td>" . $filaPago['fecha_pago'] . "</td>";
                    echo "<td class='text-danger'>$" . number_format($filaPago['monto_pago'], 0, '', '.') . "</td>";
                    echo "</tr>";
                }

                echo "</tbody>";
                echo "<tfoot>";
                echo "<tr class='align-middle'>";
                echo "<td colspan='2' style='text-align: right; font-weight: bold; font-size: 0.8rem;'>Total</td>";

                // Sumar el total de pagos
                $totalPagos = 0;
                $resultado_pagos = mysqli_query($con, $sqlPagos);
                while ($filaPago = mysqli_fetch_assoc($resultado_pagos)) {
                    $totalPagos += $filaPago['monto_pago'];
                }
                echo "<td style='font-weight: bold;font-size: 1rem;'>$" . number_format($totalPagos, 0, '', '.') . "</td>";
                echo "</tr>";
                echo "</tfoot>";
                echo "</table>";
                echo "</div>";
                echo "</div>";
            } else {
                echo "<p>Error al obtener los pagos: " . mysqli_error($con) . "</p>";
            }
            ?>
            <!-- Tabla de Gastos de Servicio -->
            
            <div class="col-md-6">
                <p>Detalle de gastos de Servicios</p>
                <div class="tablaGastos tablaLimite rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                        <?php
                        // Consulta para obtener los gastos de servicio del mes seleccionado
                        $sqlGastosServicio = "SELECT gs._id, gs.NOMBRE, gs.FECHA, gs.EFECTIVO, gs.TRANSFERENCIA, 
                                            (gs.EFECTIVO + gs.TRANSFERENCIA) as TOTAL, u.USUARIO AS nombre_usuario 
                                            FROM gastos_servicios gs 
                                            INNER JOIN usuarios u ON gs.id_USUARIO = u._id 
                                            WHERE DATE_FORMAT(STR_TO_DATE(gs.FECHA, '%d-%m-%Y'), '%Y-%m') = '$mesSeleccionado'
                                            ORDER BY STR_TO_DATE(gs.FECHA, '%d-%m-%Y') DESC";
                        
                        $resultadoGastosServicio = mysqli_query($con, $sqlGastosServicio);
                        $totalEfectivoGastosServicio = 0;
                        $totalTransferenciaGastosServicio = 0;
                        $totalGeneralGastosServicio = 0;
                        ?>
                        
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre</th>
                                    <th>Fecha</th>
                                    <th>Efectivo</th>
                                    <th>Transferencia</th>
                                    <th>Total</th>
                                    <?php if ($esAdmin): ?>
                                        <th>Acciones</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($resultadoGastosServicio) > 0): ?>
                                    <?php while ($gasto = mysqli_fetch_assoc($resultadoGastosServicio)): 
                                        $totalEfectivoGastosServicio += $gasto['EFECTIVO'];
                                        $totalTransferenciaGastosServicio += $gasto['TRANSFERENCIA'];
                                        $totalGeneralGastosServicio += $gasto['TOTAL'];
                                    ?>
                                        <tr>
                                            <td><?php echo $gasto['_id']; ?></td>
                                            <td><?php echo htmlspecialchars($gasto['nombre_usuario']); ?></td>
                                            <td><?php echo htmlspecialchars($gasto['NOMBRE']); ?></td>
                                            <td><?php echo $gasto['FECHA']; ?></td>
                                            <td class="text-danger">$<?php echo number_format($gasto['EFECTIVO'], 0, '', '.'); ?></td>
                                            <td class="text-danger">$<?php echo number_format($gasto['TRANSFERENCIA'], 0, '', '.'); ?></td>
                                            <td class="text-danger fw-bold">$<?php echo number_format($gasto['TOTAL'], 0, '', '.'); ?></td>
                                            <?php if ($esAdmin): ?>
                                                <td>
                                                    <a href="#" onclick="eliminarGastoServicio(<?php echo $gasto['_id']; ?>); return false;" title="Eliminar">
                                                        <i class="fas fa-trash-alt iconTrashProducto"></i>
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="<?php echo $esAdmin ? 8 : 7; ?>" class="text-center">No hay gastos de servicio registrados para este mes</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td colspan="4" style="text-align: right; font-weight: bold; font-size: 0.8rem;">Total</td>
                                    <td class="text-danger" style="font-weight: bold;font-size: 1rem;">$<?php echo number_format($totalEfectivoGastosServicio, 0, '', '.'); ?></td>
                                    <td class="text-danger" style="font-weight: bold;font-size: 1rem;">$<?php echo number_format($totalTransferenciaGastosServicio, 0, '', '.'); ?></td>
                                    <td class="text-danger" style="font-weight: bold;font-size: 1rem;">$<?php echo number_format($totalGeneralGastosServicio, 0, '', '.'); ?></td>
                                    <?php if ($esAdmin): ?>
                                        <td></td>
                                    <?php endif; ?>
                                </tr>
                            </tfoot>
                        </table>
                </div>
            </div>
        
            <div class="col-12 divisor mb-3"></div>


            <div class="row m-0 p-3">
                <div class="col-md-6 m-0 p-0 ">
                    <div class="tarjetaTotal p-3 me-md-4 mb-md-0 mb-3">
                        <p class="h2">Ingresos</p>
                        <div class="w-100 divisor mb-3"></div>
                        <p><span style="font-weight: bold;">Total en Cancha (Cancha - Seña):</span>
                            <?php echo number_format($total_cancha - $total_senia, 0, '', '.'); ?> $</p>
                        <p><span style="font-weight: bold;">Total en Señas:</span> <?php echo number_format($totalSenias, 0, '', '.'); ?> $</p>
                        <p><span style="font-weight: bold;">Total en Productos:</span> <?php echo number_format($total_productos, 0, '', '.'); ?> $</p>
                        <p><span style="font-weight: bold;">Total en Extras:</span> <?php echo number_format($total_extra, 0, '', '.'); ?> $</p>
                        <div class="w-100 divisor mb-3"></div>
                        <p style="font-weight: bold; font-size:1.4rem"><span>Total de Ingresos:</span>
                            <?php echo number_format($total_general + $totalSenias, 0, '', '.'); ?> $
                        </p>
                    </div>
                </div>
                <div class="col-md-6 row m-0 p-0">
                    <div class="col-12 m-0 p-0 mb-2">
                        <div class="tarjetaTotal p-3 h-100" style="background-color:#5B2935">
                            <p class="h2">Egresos</p>

                            <div class="w-100 divisor mb-3"></div>
                            <p><span style="font-weight: bold;">Total de Gastos:</span> <?php echo number_format($totalGastos, 0, '', '.'); ?> $</p>
                            <p><span style="font-weight: bold;">Total de Gastos de Servicio:</span> <?php echo number_format($totalGeneralGastosServicio, 0, '', '.'); ?> $</p>
                            <p><span style="font-weight: bold;">Total en Sueldos:</span> <?php echo number_format($totalPagos, 0, '', '.'); ?> $</p>
                            <div class="w-100 divisor mb-3"></div>
                            <p style="font-weight: bold; font-size:1.4rem"><span>Total de Egresos:</span>
                                <?php echo number_format($totalGastos + $totalPagos + $totalGeneralGastosServicio, 0, '', '.'); ?> $
                            </p>
                        </div>
                    </div>
                    <div class="col-12 m-0 p-0 mt-2">
                        <div class="tarjetaTotal p-3 h-100" style="background-color:#253915">
                            <!-- Calcular y mostrar la diferencia -->
                            <p class="h2">Beneficio (Ingresos - Egresos)</p>
                            <div class="w-100 divisor mb-3"></div>
                            <div class="d-flex">
                                <p style="font-weight: bold; font-size:1.4rem"><span>Total:</span>
                                    <?php echo number_format($total_general + $totalSenias - $totalGeneralGastosServicio - $totalGastos - $totalPagos, 0, '', '.'); ?> $</p>
                                <div class="ps-3 p-1 m-0">
                                    <p class="p-0 m-0">Efectivo:
                                        <span><?php 
                                            $ingresosEfectivo = $total_efectivo + $totalEfectivo;
                                            $egresosEfectivo = $totalEfectivoGastos + $totalEfectivoGastosServicio + $totalPagos; // Asumiendo que los pagos son en efectivo
                                            echo number_format($ingresosEfectivo - $egresosEfectivo, 0, '', '.');
                                        ?> $</span>
                                    </p>
                                    <p class="p-0 m-0">Transferencias:
                                        <span><?php 
                                            $ingresosTransferencia = $total_transferencia + $totalTransferencias;
                                            $egresosTransferencia = $totalTransferenciaGastos + $totalTransferenciaGastosServicio;
                                            echo number_format($ingresosTransferencia - $egresosTransferencia, 0, '', '.');
                                        ?> $</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
    </div>
<?php endif; ?>

<?php include 'common_scripts.php';
// Incluir el modal de gasto de servicio
include 'modalGastoServicio.php';
?>

<script>
    function eliminarGastoServicio(id) {
        Swal.fire({
            title: 'Estas seguro?',
            text: 'Esta accion no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Si, eliminarlo',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'eliminar_gasto_servicio.php?id=' + encodeURIComponent(id), true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var response = JSON.parse(xhr.responseText);

                        if (response.success) {
                            Swal.fire({
                                title: 'Eliminado',
                                text: response.message,
                                icon: 'success'
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                };
                xhr.send();
            }
        });
    }
</script>

</body>

</html>
