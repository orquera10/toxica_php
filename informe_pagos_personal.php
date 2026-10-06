<?php
// Incluir archivos necesarios
$pageTitle = "Informe Personal";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include('config.php');  // Conexión a la base de datos

// Obtener el ID del empleado seleccionado, o 'todos' si se elige esa opción
$empleado_id = isset($_GET['empleado']) ? $_GET['empleado'] : 'todos';
$fecha_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-d', strtotime('last Sunday'));
$fecha_fin = isset($_GET['fin']) ? $_GET['fin'] : date('Y-m-d');

// Convertir las fechas de entrada al formato d-m-Y para comparar con la base de datos
$fecha_inicio_db = date('d-m-Y', strtotime($fecha_inicio));
$fecha_fin_db = date('d-m-Y', strtotime($fecha_fin));

// Verificar si el filtro de empleados es "todos" o un empleado específico
if ($empleado_id == 'todos') {
    // Consultas para obtener todas las ventas y pagos de todos los empleados de tipo 'USER'
    $sql_ventas = "SELECT tp.FECHA, tp.MONTO, tp.DETALLE, u.NOMBRE AS nombre_empleado
                   FROM turnos_personal tp
                   JOIN usuarios u ON tp.id_USER = u._id
                   WHERE STR_TO_DATE(tp.FECHA, '%d-%m-%Y') BETWEEN STR_TO_DATE('$fecha_inicio_db', '%d-%m-%Y') AND STR_TO_DATE('$fecha_fin_db', '%d-%m-%Y')
                   AND u.TIPO = 'USER'";
    $sql_pagos = "SELECT pe.FECHA, pe.MONTO, u.NOMBRE AS nombre_empleado
                  FROM pago_empleado pe
                  JOIN usuarios u ON pe.id_USER = u._id
                  WHERE STR_TO_DATE(pe.FECHA, '%d-%m-%Y') BETWEEN STR_TO_DATE('$fecha_inicio_db', '%d-%m-%Y') AND STR_TO_DATE('$fecha_fin_db', '%d-%m-%Y')
                  AND u.TIPO = 'USER'";
} else {
    // Consultas para obtener las ventas y pagos de un empleado específico
    $sql_ventas = "SELECT tp.FECHA, tp.MONTO, tp.DETALLE, u.NOMBRE AS nombre_empleado
                   FROM turnos_personal tp
                   JOIN usuarios u ON tp.id_USER = u._id
                   WHERE STR_TO_DATE(tp.FECHA, '%d-%m-%Y') BETWEEN STR_TO_DATE('$fecha_inicio_db', '%d-%m-%Y') AND STR_TO_DATE('$fecha_fin_db', '%d-%m-%Y')
                   AND tp.id_USER = '$empleado_id' AND u.TIPO = 'USER'";
    $sql_pagos = "SELECT pe.FECHA, pe.MONTO, u.NOMBRE AS nombre_empleado
                  FROM pago_empleado pe
                  JOIN usuarios u ON pe.id_USER = u._id
                  WHERE STR_TO_DATE(pe.FECHA, '%d-%m-%Y') BETWEEN STR_TO_DATE('$fecha_inicio_db', '%d-%m-%Y') AND STR_TO_DATE('$fecha_fin_db', '%d-%m-%Y')
                  AND pe.id_USER = '$empleado_id' AND u.TIPO = 'USER'";
}

// Ejecutar las consultas
$ventas_result = mysqli_query($con, $sql_ventas);
$pagos_result = mysqli_query($con, $sql_pagos);

// Inicializar variables para los totales
$total_ventas = 0;
$total_pagos = 0;
?>

<!-- Formulario de Selección -->
<div class="container tablasInformes">
    <p class="mt-5 h4">Informe de ventas del personal y de pagos al personal</p>
    <form method="GET" action="informe_pagos_personal.php">
        <div class="row">
            <div class="col-md-4">
                <label for="empleado" class="form-label">Empleado</label>
                <select class="form-select despFecha" id="empleado" name="empleado" required>
                    <option value="todos" <?php if ($empleado_id == 'todos')
                        echo 'selected'; ?>>Todos los empleados
                    </option>
                    <?php
                    // Obtener los empleados de tipo 'USER'
                    $sql_empleados = "SELECT _id, NOMBRE FROM usuarios WHERE TIPO = 'USER'";
                    $resultado_empleados = mysqli_query($con, $sql_empleados);

                    while ($empleado = mysqli_fetch_assoc($resultado_empleados)) {
                        $selected = ($empleado_id == $empleado['_id']) ? 'selected' : '';
                        echo "<option value='" . $empleado['_id'] . "' $selected>" . htmlspecialchars($empleado['NOMBRE']) . "</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="inicio" class="form-label">Fecha inicio</label>
                <input type="date" name="inicio" id="inicio" class="form-control despFecha" required
                    value="<?php echo $fecha_inicio; ?>">
            </div>

            <div class="col-md-4">
                <label for="fin" class="form-label">Fecha fin</label>
                <input type="date" name="fin" id="fin" class="form-control despFecha" required value="<?php echo $fecha_fin; ?>">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary">Generar informe</button>
                <a href="generar_pdf_empleado.php?empleado=<?php echo $empleado_id; ?>&inicio=<?php echo $fecha_inicio; ?>&fin=<?php echo $fecha_fin; ?>"
                    class="btn btn-success">Guardar en PDF</a>
            </div>
        </div>

    </form>
    <div class="col-12 divisor my-3"></div>
</div>

<div class="container tablasInformes mt-2">
    <?php
    // Mostrar las ventas
    echo "<p class='mt-3 h5'>Total Ventas:</p>";
    echo "<div class='rounded tablaTurnosAll tablaEmpleados my-4 shadow py-2 px-4' style='overflow-x: auto;'>";
    echo "<table class='table table-striped '>
            <thead>
                <tr>
                    <th>Fecha Venta</th>
                    <th>Detalle de la Venta</th>
                    <th>Monto Venta</th>
                    <th>Empleado</th>
                </tr>
            </thead>
            <tbody>";

    while ($venta = mysqli_fetch_assoc($ventas_result)) {
        echo "<tr>
                <td>" . date('d-m-Y', strtotime($venta['FECHA'])) . "</td>
                <td>" . $venta['DETALLE'] . "</td>
                <td>$" . number_format($venta['MONTO'], 2) . "</td>
                <td>" . htmlspecialchars($venta['nombre_empleado']) . "</td>
              </tr>";
        $total_ventas += $venta['MONTO'];
    }


    echo "</tbody></table>";
    echo "</div>";

    // Mostrar los pagos
    echo "<p class='mt-3 h5'>Total Pagos:</p>";
    echo "<div class='rounded tablaTurnosAll tablaEmpleados my-4 shadow py-2 px-4' style='overflow-x: auto;'>";
    echo "<table class='table table-striped'>
            <thead>
                <tr>
                    <th>Fecha Pago</th>
                    <th>Monto Pago</th>
                    <th>Empleado</th>
                </tr>
            </thead>
            <tbody>";

    while ($pago = mysqli_fetch_assoc($pagos_result)) {
        echo "<tr>
                <td>" . date('d-m-Y', strtotime($pago['FECHA'])) . "</td>
                <td>$" . number_format($pago['MONTO'], 2) . "</td>
                <td>" . htmlspecialchars($pago['nombre_empleado']) . "</td>
              </tr>";
        $total_pagos += $pago['MONTO'];
    }

    echo "</tbody></table>";
    echo "</div>";
    echo "<div class='col-12 divisor my-3 mb-4'></div>";
    // Mostrar totales
    echo "<p class='mt-2 h5'>Total Ventas: $" . number_format($total_ventas, 2) . "</p>";
    echo "<p class='mt-2 h5'>Total Pagos: $" . number_format($total_pagos, 2) . "</p>";

    // Mostrar la diferencia
    $diferencia = $total_ventas - $total_pagos;
    echo "<p class='mt-2 h5 mb-5'>Diferencia (Ventas - Pagos): $" . number_format($diferencia, 2) . "</p>";
    ?>
</div>

<?php
// Incluir scripts comunes
include 'common_scripts.php';
?>

</body>

</html>