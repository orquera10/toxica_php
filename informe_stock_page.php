<?php
// Incluir archivos necesarios
$pageTitle = "Informe Stock Diario";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php'; // Suponiendo que aquí se encuentra la configuración de la conexión a la base de datos

$fechaSeleccionada = date("d-m-Y");


// Verificar si se ha seleccionado una fecha
if (isset($_POST['fechaInforme'])) {
    $fechaSeleccionada = $_POST['fechaInforme'];
    // Formatear la fecha al formato "d-m-Y"
    $fechaSeleccionada = date('d-m-Y', strtotime($fechaSeleccionada));


    $sql = "SELECT s._id AS id_STOCK, 
    p.NOMBRE AS producto, 
    s.FECHA, 
    s.INGRESO,
    s.EGRESO,
    s.DETALLE 
    FROM stock s
    INNER JOIN producto p ON p._id = s.id_PRODUCTO
    WHERE STR_TO_DATE(s.FECHA, '%d-%m-%Y') = STR_TO_DATE('$fechaSeleccionada', '%d-%m-%Y')
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
                            <th>ID Movimiento</th>
                            <th>Nombre Producto</th>
                            <th>Fecha</th>
                            <th>Ingreso</th>
                            <th>Egreso</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Consulta para obtener los datos de la tabla ticket -->
                        <?php
                        $sqlStock = "SELECT s._id AS id_STOCK, 
                        p.NOMBRE AS producto, 
                        s.FECHA, 
                        s.INGRESO,
                        s.EGRESO,
                        s.DETALLE 
                        FROM stock s
                        INNER JOIN producto p ON p._id = s.id_PRODUCTO
                        WHERE STR_TO_DATE(s.FECHA, '%d-%m-%Y') = STR_TO_DATE('$fechaSeleccionada', '%d-%m-%Y')
                        ";

                        // Ejecutar la consulta
                        $resultado_stock = mysqli_query($con, $sqlStock);

                        // Mostrar los resultados en la tabla
                        while ($filaStock = mysqli_fetch_assoc($resultado_stock)) {
                            // Mostrar los datos en la fila
                            echo "<tr class='align-middle'>";
                            echo "<td>" . $filaStock['id_STOCK'] . "</td>";
                            echo "<td>" . $filaStock['producto'] . "</td>";
                            echo "<td>" . $filaStock['FECHA'] . "</td>";
                            echo "<td>" . $filaStock['INGRESO'] . "</td>";
                            echo "<td>" . $filaStock['EGRESO'] . "</td>";
                            echo "<td>" . $filaStock['DETALLE'] . "</td>";
                            echo "</tr>";

                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>


<?php
include 'common_scripts.php';
?>

</body>

</html>