<?php
// Incluir archivos necesarios
$pageTitle = "Informe Diario de Ventas";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php'; // Suponiendo que aquí se encuentra la configuración de la conexión a la base de datos

$total_cancha = 0;
$extra = 0;
$senia = 0;
$total_productos = 0;
$total = 0;
$total_efectivo = 0;
$total_transferencia = 0;
$totalGastos = 0;
$totalGastoEfectivo = 0;
$totalGastoTransferencia = 0;
$totalSenias = 0;

$totalEfectivo = 0; // total efectivo señas
$totalTransferencias = 0; // total transeferencias señas


// Inicializar $resultado como un array vacío
$resultado = [];
$fechaSeleccionada = date("d-m-Y");

// Obtener ID del usuario desde la cookie
$cookieUsuario = $_COOKIE['ID_USUARIO'] ?? null;

if ($cookieUsuario) {
    // Separar ID y token
    $partes = explode('|', urldecode($cookieUsuario));
    $idUsuario = mysqli_real_escape_string($con, $partes[0]); // Sanitizar

    // Buscar el usuario en la base de datos
    $queryUsuario = "SELECT TIPO, HR_ENTRADA, HR_SALIDA FROM usuarios WHERE _id = '$idUsuario'";
    $resUsuario = mysqli_query($con, $queryUsuario);
    $usuario = mysqli_fetch_assoc($resUsuario);

    if ($usuario) {
        $tipoUsuario = $usuario['TIPO'];
        $entrada = $usuario['HR_ENTRADA'];
        $salida = $usuario['HR_SALIDA'];

        if ($tipoUsuario === 'admin') {
            // Admin: rango fijo
            $fechaSeleccionadaInicial = date('d-m-Y 04:01', strtotime($fechaSeleccionada));
            $fechaSeleccionadaFinal = date('d-m-Y 04:00', strtotime($fechaSeleccionada . ' +1 day'));
        } else {
            // User: usar horarios personalizados
            $horaEntrada = $entrada; // viene como string tipo "20:00"
            $horaSalida = $salida;   // puede ser "01:00"

            $fechaSeleccionadaInicial = date("d-m-Y $horaEntrada", strtotime($fechaSeleccionada));

            // Si la salida es menor o igual que la entrada, se considera al día siguiente
            if (strtotime($horaSalida) <= strtotime($horaEntrada)) {
                $fechaSeleccionadaFinal = date("d-m-Y $horaSalida", strtotime($fechaSeleccionada . ' +1 day'));
            } else {
                $fechaSeleccionadaFinal = date("d-m-Y $horaSalida", strtotime($fechaSeleccionada));
            }
        }
    }
}

// Verificar si se ha seleccionado una fecha
if (isset($_POST['fechaInforme'])) {
    $fechaSeleccionada = $_POST['fechaInforme'];
    // Formatear la fecha al formato "d-m-Y"
    $fechaSeleccionada = date('d-m-Y', strtotime($fechaSeleccionada));

    if ($usuario) {
        $tipoUsuario = $usuario['TIPO'];
        $entrada = $usuario['HR_ENTRADA'];
        $salida = $usuario['HR_SALIDA'];

        if ($tipoUsuario === 'admin') {
            // Admin: rango fijo
            $fechaSeleccionadaInicial = date('d-m-Y 04:01', strtotime($fechaSeleccionada));
            $fechaSeleccionadaFinal = date('d-m-Y 04:00', strtotime($fechaSeleccionada . ' +1 day'));
        } else {
            // User: usar horarios personalizados
            $horaEntrada = $entrada; // viene como string tipo "20:00"
            $horaSalida = $salida;   // puede ser "01:00"

            $fechaSeleccionadaInicial = date("d-m-Y $horaEntrada", strtotime($fechaSeleccionada));

            // Si la salida es menor o igual que la entrada, se considera al día siguiente
            if (strtotime($horaSalida) <= strtotime($horaEntrada)) {
                $fechaSeleccionadaFinal = date("d-m-Y $horaSalida", strtotime($fechaSeleccionada . ' +1 day'));
            } else {
                $fechaSeleccionadaFinal = date("d-m-Y $horaSalida", strtotime($fechaSeleccionada));
            }
        }
    }




    $sql = "SELECT tk._id AS id_TICKET, 
    c.NOMBRE AS nombre_cliente, 
    t.FECHA AS fecha_reserva, 
    t.HORA_INICIO, 
    t.HORA_FIN, 
    cn.NOMBRE AS nombre_cancha, 
    tk.SENIA,
    tk.TOTAL_CANCHA,
    tk.EXTRA,  
    tk.FECHA AS fecha_ticket, 
    tk.TOTAL_DETALLE, 
    tk.TOTAL, 
    tk.PAGO_EFECTIVO, 
    tk.PAGO_TRANSFERENCIA 
    FROM turnos t
    INNER JOIN ticket tk ON t._id = tk.id_TURNO
    INNER JOIN clientes c ON tk.id_CLIENTE = c._id
    INNER JOIN canchas cn ON t.id_CANCHA = cn._id
    WHERE STR_TO_DATE(tk.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE('$fechaSeleccionadaInicial', '%d-%m-%Y %H:%i') AND STR_TO_DATE('$fechaSeleccionadaFinal', '%d-%m-%Y %H:%i')
    AND t.FINALIZADO = 1;
    ";

    // Ejecutar la consulta
    $resultado_query = mysqli_query($con, $sql);

    // Almacenar los resultados en un array
    while ($fila = mysqli_fetch_assoc($resultado_query)) {
        $resultado[] = $fila;
    }
}

?>

<div class="container tablasInformes">
    <p class="mt-5 h4">Informe Diario</p>
    <!-- Formulario para seleccionar la fecha -->
    <form id="fechaForm" method="post" action="" class="my-4">
        <div class="form-group mt-4 d-flex align-items-center">
            <p class="my-0 me-2 p-0" style="font-weight: bold; font-size: 0.8rem">Seleccionar fecha: </p>
            <input type="date" class="form-control despFecha" id="fechaInforme" name="fechaInforme"
                value="<?php echo date('Y-m-d', strtotime($fechaSeleccionada)); ?>"
                onchange="document.getElementById('fechaForm').submit()">
        </div>
    </form>

    <div class="col-12 divisor my-3"></div>

    <div class="row mt-5">
        <div class="col-12">
            <p>Detalle de ventas del día</p>
            <!-- Tabla para mostrar los turnos -->
            <div class="rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                <table class="table">
                    <!-- Cabecera de la tabla -->
                    <thead>
                        <tr class="align-middle">
                            <th>ID Ticket</th>
                            <th>Nombre Cliente</th>
                            <th>Fecha Pago</th>
                            <th>Cancha</th>
                            <th>Fecha Turno</th>
                            <th>Hora Inicio</th>
                            <th>Hora Fin</th>
                            <th>Seña</th>
                            <th>Total Cancha</th>
                            <th>Extra</th>
                            <th>Total Productos</th>
                            <th>Total</th>
                            <th>Pago Efectivo</th>
                            <th>Pago Transferencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Consulta para obtener los datos de la tabla ticket -->
                        <?php
                        $sqlTurnos = "SELECT tk._id AS id_TICKET, 
                                        c.NOMBRE AS nombre_cliente, 
                                        t.FECHA AS fecha_reserva, 
                                        t.HORA_INICIO, 
                                        t.HORA_FIN, 
                                        cn.NOMBRE AS nombre_cancha, 
                                        tk.SENIA, 
                                        tk.TOTAL_CANCHA,
                                        tk.EXTRA, 
                                        tk.FECHA AS fecha_ticket, 
                                        tk.TOTAL_DETALLE, 
                                        tk.TOTAL, 
                                        tk.PAGO_EFECTIVO, 
                                        tk.PAGO_TRANSFERENCIA 
                                    FROM turnos t
                                    INNER JOIN ticket tk ON t._id = tk.id_TURNO
                                    INNER JOIN clientes c ON tk.id_CLIENTE = c._id
                                    INNER JOIN canchas cn ON t.id_CANCHA = cn._id
                                    WHERE STR_TO_DATE(tk.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE('$fechaSeleccionadaInicial', '%d-%m-%Y %H:%i') AND STR_TO_DATE('$fechaSeleccionadaFinal', '%d-%m-%Y %H:%i')
                                    AND t.FINALIZADO = 1";

                        // Ejecutar la consulta
                        $resultado_turnos = mysqli_query($con, $sqlTurnos);

                        // Mostrar los resultados en la tabla
                        while ($filaTurno = mysqli_fetch_assoc($resultado_turnos)) {
                            // Mostrar los datos en la fila
                            echo "<tr class='align-middle'>";
                            echo "<td><a href='#' class='id-ticket-link' data-id-ticket='" . $filaTurno['id_TICKET'] . "'>" . $filaTurno['id_TICKET'] . "</a></td>";
                            echo "<td>" . $filaTurno['nombre_cliente'] . "</td>";
                            echo "<td>" . $filaTurno['fecha_ticket'] . "</td>";
                            echo "<td>" . $filaTurno['nombre_cancha'] . "</td>";
                            echo "<td>" . $filaTurno['fecha_reserva'] . "</td>";
                            echo "<td>" . $filaTurno['HORA_INICIO'] . "</td>";
                            echo "<td>" . $filaTurno['HORA_FIN'] . "</td>";
                            echo "<td>" . $filaTurno['SENIA'] . "</td>";
                            echo "<td>" . $filaTurno['TOTAL_CANCHA'] . "</td>";
                            echo "<td>" . $filaTurno['EXTRA'] . "</td>";
                            echo "<td>" . $filaTurno['TOTAL_DETALLE'] . "</td>";
                            echo "<td style='font-weight: bold;font-size: 0.8rem;'>" . $filaTurno['TOTAL'] + $filaTurno['SENIA'] . "</td>";
                            echo "<td>" . $filaTurno['PAGO_EFECTIVO'] . "</td>";
                            echo "<td>" . $filaTurno['PAGO_TRANSFERENCIA'] . "</td>";
                            echo "</tr>";

                            // Sumar a los totales
                            $total_cancha += $filaTurno['TOTAL_CANCHA'];
                            $extra += $filaTurno['EXTRA'];
                            $senia += $filaTurno['SENIA'];
                            $total_productos += $filaTurno['TOTAL_DETALLE'];
                            $total += $filaTurno['TOTAL'];
                            $total_efectivo += $filaTurno['PAGO_EFECTIVO'];
                            $total_transferencia += $filaTurno['PAGO_TRANSFERENCIA'];
                        }
                        ?>
                    </tbody>
                    <!-- Pie de la tabla -->
                    <tfoot>
                        <tr>
                            <td colspan="6"></td>
                            <td style='font-weight: bold;font-size: 0.8rem;'>Totales</td>
                            <td style='font-weight: bold;font-size: 0.8rem;'><?php echo $senia; ?></td>
                            <td style='font-weight: bold;font-size: 0.8rem;'><?php echo $total_cancha; ?></td>
                            <td style='font-weight: bold;font-size: 0.8rem;'><?php echo $extra; ?></td>
                            <td style='font-weight: bold;font-size: 0.8rem;'><?php echo $total_productos; ?></td>
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $total; ?></td>
                            <td style='font-weight: bold;font-size: 0.8rem;'><?php echo $total_efectivo; ?></td>
                            <td style='font-weight: bold;font-size: 0.8rem;'><?php echo $total_transferencia; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Tabla para mostrar las señas -->
        <div class="col-md-4">
            <p>Detalle de señas del día</p>
            <div class="tablaGastos rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                <table class="table">
                    <!-- Cabecera de la tabla -->
                    <thead>
                        <tr class="align-middle">
                            <th>ID</th>
                            <th>Cliente</th>
                            <th>Fecha Turno</th>
                            <th>Fecha Seña</th>
                            <th>Monto</th>
                            <th>Transf.</th>
                            <th>Efectivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php


                        // Consulta para obtener los gastos para la fecha seleccionada
                        $sqlSenias = "SELECT s._id, s.FECHA AS fecha_senia, s.MONTO, s.TRANSFERENCIA, s.EFECTIVO, c.NOMBRE, t.FECHA as fecha_turno FROM senias s INNER JOIN clientes c ON s.id_CLIENTE = c._id INNER JOIN turnos t ON s.id_TURNO = t._id WHERE STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE('$fechaSeleccionadaInicial', '%d-%m-%Y %H:%i') AND STR_TO_DATE('$fechaSeleccionadaFinal', '%d-%m-%Y %H:%i')";

                        // Ejecutar la consulta
                        $resultado_senias = mysqli_query($con, $sqlSenias);
                        // Verificar si la consulta se ejecutó correctamente
                        if ($resultado_senias) {

                            // Mostrar los resultados en la tabla
                            while ($filaSenia = mysqli_fetch_assoc($resultado_senias)) {
                                // Sumar el monto al total general
                                $totalSenias += $filaSenia['MONTO'];
                                $totalTransferencias += $filaSenia['TRANSFERENCIA'];
                                $totalEfectivo += $filaSenia['EFECTIVO'];

                                // Mostrar los datos en la fila
                                echo "<tr class='align-middle'>";
                                echo "<td>" . $filaSenia['_id'] . "</td>";
                                echo "<td>" . $filaSenia['NOMBRE'] . "</td>";
                                echo "<td>" . $filaSenia['fecha_turno'] . "</td>";
                                echo "<td>" . $filaSenia['fecha_senia'] . "</td>";
                                echo "<td>" . $filaSenia['MONTO'] . "</td>";
                                echo "<td>" . $filaSenia['TRANSFERENCIA'] . "</td>";
                                echo "<td>" . $filaSenia['EFECTIVO'] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            // Si hubo un error en la consulta, mostrar un mensaje de error
                            echo "<tr><td colspan='7'>Error al obtener los gastos: " . mysqli_error($con) . "</td></tr>";
                        }
                        ?>
                    </tbody>
                    <!-- Pie de la tabla -->
                    <tfoot>
                        <tr class="align-middle">
                            <td colspan="4" style="text-align: right; font-weight: bold; font-size: 0.8rem;">Total</td>
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $totalSenias; ?></td>
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $totalTransferencias; ?></td>
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $totalEfectivo; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>



        <!-- Tabla para mostrar los gastos -->
        <div class="col-md-4">
            <p>Detalle de gastos del día</p>
            <div class="tablaGastos rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                <table class="table">
                    <!-- Cabecera de la tabla -->
                    <thead>
                        <tr class="align-middle">
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
                        // Consulta para obtener los gastos para la fecha seleccionada
                        $sqlGastos = "SELECT g._id, g.id_USUARIO, g.NOMBRE, g.FECHA, g.MONTO, g.EFECTIVO, g.TRANSFERENCIA, u.USUARIO AS nombre_usuario FROM gastos g INNER JOIN usuarios u ON g.id_USUARIO = u._id WHERE STR_TO_DATE(g.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE('$fechaSeleccionadaInicial', '%d-%m-%Y %H:%i') AND STR_TO_DATE('$fechaSeleccionadaFinal', '%d-%m-%Y %H:%i')";
                        
                        // Si el usuario es tipo 'user', filtrar por su ID
                        if ($tipoUsuario == 'user') {
                            $sqlGastos .= " AND g.id_USUARIO = '$idUsuario'";
                        }
                        
                        // Ejecutar la consulta
                        $resultado_gastos = mysqli_query($con, $sqlGastos);
                        // Verificar si la consulta se ejecutó correctamente
                        if ($resultado_gastos) {
                            // Inicializar la variable para el total general de los montos
                        

                            // Mostrar los resultados en la tabla
                            while ($filaGasto = mysqli_fetch_assoc($resultado_gastos)) {
                                // Sumar el monto al total general
                                $totalGastos += $filaGasto['MONTO'];
                                $totalGastoEfectivo += $filaGasto['EFECTIVO'];
                                $totalGastoTransferencia += $filaGasto['TRANSFERENCIA'];

                                // Mostrar los datos en la fila
                                echo "<tr class='align-middle'>";
                                echo "<td>" . $filaGasto['_id'] . "</td>";
                                echo "<td>" . $filaGasto['nombre_usuario'] . "</td>";
                                echo "<td>" . $filaGasto['NOMBRE'] . "</td>";
                                echo "<td>" . $filaGasto['FECHA'] . "</td>";
                                echo "<td>" . $filaGasto['MONTO'] . "</td>";
                                echo "<td>" . $filaGasto['EFECTIVO'] . "</td>";
                                echo "<td>" . $filaGasto['TRANSFERENCIA'] . "</td>";
                                echo "</tr>";
                            }
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
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $totalGastos; ?></td>
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $totalGastoEfectivo; ?></td>
                            <td style='font-weight: bold;font-size: 1rem;'><?php echo $totalGastoTransferencia; ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>


        <div class="col-md-4">
            <p>Detalle de stock del día</p>
            <!-- Tabla para mostrar la información de los productos -->
            <div class="tablaProductosStock rounded tablaTurnosAll my-4 shadow py-2 px-4" style="overflow-x: auto;">
                <table class="table">
                    <!-- Cabecera de la tabla -->
                    <thead>
                        <tr class="align-middle">
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Precio</th>
                            <th>Cantidad</th>

                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Consulta para obtener los datos de la tabla detalle_ticket -->
                        <?php
                        $sqlProductos = "SELECT detalle_ticket.id_PRODUCTO, 
                                        producto.NOMBRE AS nombre_producto, 
                                        producto.PRECIO, 
                                        SUM(detalle_ticket.CANTIDAD) AS total_cantidad,
                                        SUM(detalle_ticket.PRECIO * detalle_ticket.CANTIDAD) AS total_producto
                                 FROM detalle_ticket 
                                 INNER JOIN ticket ON detalle_ticket.id_TICKET = ticket._id 
                                 INNER JOIN producto ON detalle_ticket.id_PRODUCTO = producto._id 
                                 WHERE STR_TO_DATE(ticket.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE('$fechaSeleccionadaInicial', '%d-%m-%Y %H:%i') AND STR_TO_DATE('$fechaSeleccionadaFinal', '%d-%m-%Y %H:%i')
                                 GROUP BY detalle_ticket.id_PRODUCTO";

                        // Variables para calcular el total general
                        $totalGeneral = 0;

                        // Ejecutar la consulta
                        $resultado_productos = mysqli_query($con, $sqlProductos);

                        // Mostrar los resultados en la tabla
                        while ($filaProducto = mysqli_fetch_assoc($resultado_productos)) {
                            // Calcular el total por producto
                            $totalProducto = $filaProducto['PRECIO'] * $filaProducto['total_cantidad'];

                            // Sumar al total general
                            $totalGeneral += $totalProducto;

                            // Mostrar los datos en la fila
                            echo "<tr class='align-middle'>";
                            echo "<td>" . $filaProducto['id_PRODUCTO'] . "</td>";
                            echo "<td>" . $filaProducto['nombre_producto'] . "</td>";
                            echo "<td>" . $filaProducto['PRECIO'] . "</td>";
                            echo "<td>" . $filaProducto['total_cantidad'] . "</td>";

                            echo "<td style='font-weight: bold;font-size: 0.8rem;'>" . $totalProducto . "</td>";
                            echo "</tr>";
                        }
                        ?>
                        <!-- Total general -->
                        <tr class='align-middle'>
                            <td colspan="3"></td>
                            <td style='font-weight: bold; font-size: 0.8rem;'>Total</td>
                            <td style='font-weight: bold; font-size: 1rem;'><?php echo $totalGeneral; ?></td>
                        </tr>
                    </tbody>
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
                        <?php echo $total_cancha - $senia; ?> $</p>
                    <p><span style="font-weight: bold;">Total en Productos:</span> <?php echo $total_productos; ?> $</p>
                    <p><span style="font-weight: bold;">Total en Extras:</span> <?php echo $extra; ?> $</p>
                    <p><span style="font-weight: bold;">Total en Señas:</span> <?php echo $totalSenias; ?> $</p>
                    <div class="w-100 divisor mb-3"></div>
                    <p style="font-weight: bold; font-size:1.4rem"><span>Total de Ingresos:</span>
                        <?php echo $total + $totalSenias; ?> $
                    </p>
                </div>
            </div>
            <div class="col-md-6 row m-0 p-0">
                <div class="col-12 m-0 p-0 mb-2">
                    <div class="tarjetaTotal p-3 h-100" style="background-color:#5B2935">
                        <p class="h2">Gastos</p>
                        <div class="w-100 divisor mb-3"></div>

                        <div class="d-flex">
                            <p style="font-weight: bold; font-size:1.4rem">
                                <span>Total de Gastos:</span>
                                <?php echo $totalGastos; ?> $
                            </p>
                            <div class="ps-3 p-1 m-0">
                                <p class="p-0 m-0">Efectivo:
                                    <span><?php echo $totalGastoEfectivo; ?>$</span>
                                </p>
                                <p class="p-0 m-0">Transferencias:
                                    <span><?php echo $totalGastoTransferencia; ?> $</span>
                                </p>
                            </div>
                        </div>


                    </div>
                </div>
                <div class="col-12 m-0 p-0 mt-2">
                    <div class="tarjetaTotal p-3 h-100" style="background-color:#253915">
                        <!-- Calcular y mostrar la diferencia -->
                        <p class="h2">Beneficio (Ingresos - Gastos)</p>
                        <div class="w-100 divisor mb-3"></div>
                        <div class="d-flex">
                            <p style="font-weight: bold; font-size:1.4rem">
                                <span>Total:</span>
                                <span id="totalImporte"><?php echo $total + $totalSenias - $totalGastos; ?></span> $
                            </p>
                            <div class="ps-3 p-1 m-0">
                                <p class="p-0 m-0">Efectivo:
                                    <span><?php echo $total + $totalSenias - $totalGastoEfectivo - $total_transferencia - $totalTransferencias; ?>$</span>
                                </p>
                                <p class="p-0 m-0">Transferencias:
                                    <span><?php echo $total_transferencia + $totalTransferencias - $totalGastoTransferencia; ?> $</span>
                                </p>
                            </div>
                        </div>


                    </div>

                </div>
            </div>

            <!-- Boton para cerrar el turno del empleado -->
            <?php if ($tipo_usuario === 'user'): ?>
                <div class="mt-3">
                    <button onclick="cerrarTurno()" class="btn btn-warning">Cerrar turno</button>
                </div>
            <?php endif; ?>

        </div>



    </div>
</div>

<!-- Agregar un Modal HTML -->
<div class="modal fade" id="detalleTicketModal" tabindex="-1" aria-labelledby="detalleTicketModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detalleTicketModalLabel">Detalles del Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="detallePagoTableBody">

            </div>
        </div>
    </div>
</div>

<?php
include 'common_scripts.php';
?>

<script>
    $(document).ready(function () {
        // Manejar el clic en los enlaces de ID Ticket
        $('.id-ticket-link').click(function (e) {
            e.preventDefault(); // Prevenir el comportamiento predeterminado del enlace

            // Obtener el ID del ticket del atributo data
            var idTicket = $(this).data('id-ticket');

            // Enviar una solicitud AJAX para obtener los detalles del ticket
            $.ajax({
                url: 'obtener_detalles_ticket.php',
                type: 'GET',
                data: { id: idTicket },
                success: function (response) {
                    // Mostrar los detalles del ticket en el modal
                    $('#detallePagoTableBody').html(response);
                    $('#detalleTicketModal').modal('show');
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    alert('Error al obtener los detalles del ticket.');
                }
            });
        });
    });

    function getCookie(nombre) {
        const cookies = document.cookie.split('; ');

        for (let i = 0; i < cookies.length; i++) {
            const [key, valor] = cookies[i].split('=');
            if (key === nombre) {
                return decodeURIComponent(valor).split('|')[0]; // solo el ID
            }
        }
        return null;
    }



    function cerrarTurno() {
        const idUsuario = getCookie("ID_USUARIO");
        const total = document.getElementById("totalImporte").textContent.trim();
        const fecha = document.getElementById("fechaInforme").value;

        if (!fecha) {
            Swal.fire({
                icon: 'warning',
                title: 'Fecha requerida',
                text: 'Seleccioná una fecha antes de cerrar el turno.',
                confirmButtonColor: '#3085d6'
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: `Vas a cerrar el turno con un total de $${total}`,
            icon: 'question',
            input: 'text',
            inputLabel: 'Detalle del turno (opcional)',
            inputPlaceholder: 'Escribí un detalle o comentario...',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#aaa',
            confirmButtonText: 'Sí, cerrar turno',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const detalle = result.value || '';  // Si no escribió nada, lo dejamos como cadena vacía

                const datos = new FormData();
                datos.append('id_usuario', idUsuario);
                datos.append('monto_total', total);
                datos.append('fecha', fecha);
                datos.append('detalle', detalle);  // Enviamos el detalle al backend

                fetch('registrar_turno_personal.php', {
                    method: 'POST',
                    body: datos
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Turno cerrado!',
                                text: data.message,
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message,
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error en la solicitud:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Hubo un error al intentar registrar el turno.',
                            confirmButtonColor: '#d33'
                        });
                    });
            }
        });
    }



</script>

</body>

</html>