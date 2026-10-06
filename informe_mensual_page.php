<?php
// Incluir archivos necesarios
$pageTitle = "Informe de Stock Mensual";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php';

// Obtener los tipos de producto para el filtro
$tipos = [];
$tiposQuery = $con->query("SELECT DISTINCT TIPO FROM producto ORDER BY TIPO");
while ($rowTipo = $tiposQuery->fetch_assoc()) {
    $tipos[] = $rowTipo['TIPO'];
}

$tipoSeleccionado = isset($_GET['tipo_producto']) ? $_GET['tipo_producto'] : '';

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['fecha_inicio']) && isset($_GET['fecha_fin'])) {
    $fechaInicio = $_GET['fecha_inicio'];
    $fechaFin = $_GET['fecha_fin'];

    // Ajusta los rangos para incluir todo el día final
    $fechaInicioSQL = $fechaInicio . " 00:00:00";
    $fechaFinSQL = $fechaFin . " 23:59:59";

    $sql = "SELECT 
        p._id AS id_producto,
        p.NOMBRE,
        COALESCE(SUM(CASE 
            WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i:%s') < ? 
            THEN s.INGRESO - s.EGRESO 
            ELSE 0 END), 0) AS stock_inicial,
        COALESCE(SUM(CASE 
            WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i:%s') BETWEEN ? AND ? 
            THEN s.INGRESO 
            ELSE 0 END), 0) AS total_ingresos,
        COALESCE(SUM(CASE 
            WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i:%s') BETWEEN ? AND ? 
            THEN s.EGRESO 
            ELSE 0 END), 0) AS total_egresos,
        COALESCE(SUM(CASE 
            WHEN STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i:%s') <= ? 
            THEN s.INGRESO - s.EGRESO 
            ELSE 0 END), 0) AS stock_final
        FROM producto p
        LEFT JOIN stock s ON s.id_PRODUCTO = p._id
        WHERE p.VISIBLE = 1";

    $params = [
        $fechaInicioSQL, // para stock_inicial
        $fechaInicioSQL,
        $fechaFinSQL, // para total_ingresos
        $fechaInicioSQL,
        $fechaFinSQL, // para total_egresos
        $fechaFinSQL     // para stock_final
    ];
    $types = "ssssss";

    // Si se seleccionó un tipo, agregarlo al WHERE y a los parámetros
    if ($tipoSeleccionado !== '') {
        $sql .= " AND p.TIPO = ?";
        $params[] = $tipoSeleccionado;
        $types .= "s";
    }

    $sql .= " GROUP BY p._id, p.NOMBRE
        ORDER BY p.IMPORTANCIA";

    $stmt = $con->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<div class="container my-4">
    <form method="GET" action="">
        <div class="form-group row formInfMensual my-4">
            <div class="col-md-5 row">
                <label class="col-12 col-form-label m-0">Seleccionar rango de fechas:</label>
                <div class="col-6 my-2">
                    <input type="date" name="fecha_inicio" class="form-control"
                        value="<?php echo isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01'); ?>">
                </div>
                <div class="col-6 my-2">
                    <input type="date" name="fecha_fin" class="form-control"
                        value="<?php echo isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t'); ?>">
                </div>
                <div class="col-12 my-2">
                    <select name="tipo_producto" class="form-control">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo htmlspecialchars($tipo); ?>" <?php if ($tipoSeleccionado == $tipo)
                                   echo 'selected'; ?>>
                                <?php echo htmlspecialchars($tipo); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 my-2">
                    <button type="submit" class="btn btn-primary w-100">Ver Informe</button>
                    <a href="generar_pdf_stock_mensual.php?fecha_inicio=<?= isset($_GET['fecha_inicio']) ? $_GET['fecha_inicio'] : date('Y-m-01') ?>&fecha_fin=<?= isset($_GET['fecha_fin']) ? $_GET['fecha_fin'] : date('Y-m-t') ?>&tipo_producto=<?= isset($_GET['tipo_producto']) ? urlencode($_GET['tipo_producto']) : '' ?>"
                        class="btn btn-success w-100 mt-2" target="_blank">
                        Descargar PDF
                    </a>
                </div>
            </div>
        </div>
    </form>

    <?php if (isset($result)): ?>
    <div >
        <!-- Columna principal: tabla -->
        <div >
            <div id="informePDF" class="row">
                <p class="tituloInformeMes">Informe de Stock -
                    <?php echo date('d/m/Y', strtotime($_GET['fecha_inicio'])) . " al " . date('d/m/Y', strtotime($_GET['fecha_fin'])); ?>
                    <?php if ($tipoSeleccionado !== ''): ?>
                        <br><span>Tipo: <?php echo htmlspecialchars($tipoSeleccionado); ?></span>
                    <?php endif; ?>
                </p>
                <div class=" py-2 px-4 tablaTurnosAll tablaMes shadow col-lg-8 col-md-7 col-12">
                    <div class="mx-1 rounded tablaTurnosAll tablaMes my-4 ">
                        <table class="table tablaStockMes">
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
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['id_producto']; ?></td>
                                        <td><?php echo $row['NOMBRE']; ?></td>
                                        <td><?php echo $row['stock_inicial']; ?></td>
                                        <td><?php echo $row['total_ingresos']; ?></td>
                                        <td><?php echo $row['total_egresos']; ?></td>
                                        <td><?php echo $row['stock_final']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Columna secundaria: tarjeta de detalle -->
                    <!-- Tarjeta de detalle de producto -->
                <div class="col-lg-4 col-md-5 col-12  my-4  py-2 px-4">
                    <div id="detalleProducto" class="card mx-1 rounded tablaTurnosAll tablaMes shadow" style="display:none;">
                        <div class="card-header">
                            <span id="detalleNombre"></span>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <img id="detalleImg" src="" alt="Imagen producto" class="img-thumbnail rounded" style="max-height:140px; object-fit:contain; background:#fff;">
                            </div>
                            <h6 class="text-secondary mb-2">Movimientos de stock</h6>
                            <div class="table-responsive tablaDetProd">
                                <table class="table ">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Ingreso</th>
                                            <th>Egreso</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detalleMovimientos">
                                        <!-- Aquí se cargan los movimientos -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        
    </div>
    <?php endif; ?>
</div>

<?php
include 'common_scripts.php';
if (isset($stmt)) {
    $stmt->close();
}
?>

<!-- Asegúrate de tener jQuery cargado -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Delegación de eventos para soportar recarga dinámica de la tabla
        $('.tablaStockMes tbody').on('click', 'tr', function () {
            var idProducto = $(this).find('td:eq(0)').text();
            $.ajax({
                url: 'movimientos_producto.php',
                type: 'GET',
                data: {
                    id_producto: idProducto,
                    fecha_inicio: $('input[name="fecha_inicio"]').val(),
                    fecha_fin: $('input[name="fecha_fin"]').val()
                },
                dataType: 'json',
                success: function (data) {
                    if (data && data.producto) {
                        $('#detalleNombre').text(data.producto.NOMBRE);
                        $('#detalleImg').attr('src', data.producto.URL_IMG && data.producto.URL_IMG.trim() !== '' ? data.producto.URL_IMG : 'https://via.placeholder.com/150');
                        var html = '';
                        if (data.movimientos.length > 0) {
                            data.movimientos.forEach(function (mov) {
                                html += '<tr><td>' + mov.FECHA + '</td><td>' + mov.INGRESO + '</td><td>' + mov.EGRESO + '</td><td>' + mov.DETALLE + '</td></tr>';
                            });
                        } else {
                            html = '<tr><td colspan="4">Sin movimientos en el rango</td></tr>';
                        }
                        $('#detalleMovimientos').html(html);
                        $('#detalleProducto').show();
                    }
                }
            });
        });
    });
</script>
</body>

</html>