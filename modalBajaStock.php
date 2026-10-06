<?php
// Consulta SQL para obtener los productos
$sql = "SELECT _id, NOMBRE, PRECIO FROM producto WHERE VISIBLE=1";
$result = $con->query($sql);
?>

<!-- Modal para agregar una nueva venta -->
<div class="modal fade" id="modalBajaDeStock" tabindex="-1" role="dialog" aria-labelledby="modalBajaDeStockLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Devolucion de producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form name="formBajaStock" id="formBajaStock" class="form-horizontal" method="POST">
                <div class="modal-body">

                    <div class="row">
                        <div class="col-8">
                            <label for="productoBaja" class="form-label">Producto</label>
                            <select class="form-select" id="productoBaja" name="productoBaja"
                                oninput="filtrarProductos()">
                                <!-- Listar los productos obtenidos de la base de datos -->
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row["_id"] . "' data-precio='" . $row["PRECIO"] . "'>" . $row["NOMBRE"] . "</option>";
                                    }
                                } else {
                                    echo "<option value=''>No hay productos disponibles</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3 col-4">
                            <label for="cantidadStock" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidadStock" name="cantidadStock" value="1"
                                min="1" required>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <label for="detalleStock">Detalle</label>
                        <input type="text" class="form-control mt-2" id="detalleStock" name="detalleStock">
                    </div>
                    <div class="form-group mt-2">
                        <label for="fechaDevolucion">Fecha</label>
                        <input type="date" class="form-control mt-2" id="fechaDevolucion" name="fechaDevolucion" required>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" id="actualizarStockProducto" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Función para abrir el modal de venta
    function abrirModalBajaStock() {
        $('#modalBajaDeStock').modal('show'); // Abre el modal de venta
    }

    // Optional JavaScript for form handling
    document.getElementById('formBajaStock').addEventListener('submit', function (event) {
        event.preventDefault();
        // Obtener los datos del formulario
        const producto = document.getElementById('productoBaja').value;
        const cantidad = parseFloat(document.getElementById('cantidadStock').value);
        const detalle = document.getElementById('detalleStock').value;
        const fecha = document.getElementById('fechaDevolucion').value;

        // Enviar los datos mediante AJAX a guardar_gasto.php
        $.ajax({
            url: 'devolucion_producto.php',
            type: 'POST',
            data: {
                producto: producto,
                cantidad: cantidad,
                detalle: detalle,
                fecha: fecha
            },
            success: function (response) {
                // Mostrar mensaje de éxito con SweetAlert2
                Swal.fire({
                    icon: 'success',
                    title: 'Devolucion registrada',
                    text: 'La devolucion se ha registrado exitosamente'
                }).then(() => {
                    // Cerrar el modal después de enviar los datos
                    $('#modalBajaDeStock').modal('hide');
                    // Limpiar el formulario después de enviar los datos
                    document.getElementById('formBajaStock').reset();
                });
            },
            error: function (xhr, status, error) {
                // Mostrar mensaje de error con SweetAlert2
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al registrar la devolucion: ' + error
                });
            }
        });
    });
</script>