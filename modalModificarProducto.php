<!-- Modal para editar producto -->
<div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarProducto" enctype="multipart/form-data">
                    <!-- Campos del formulario -->
                    <div class="mb-3">
                        <label for="editarNombreProducto" class="form-label">Nombre:</label>
                        <input type="text" class="form-control" id="editarNombreProducto" name="editarNombreProducto">
                    </div>
                    <div class="mb-3">
                        <label for="editarPrecioProducto" class="form-label">Precio:</label>
                        <input type="number" class="form-control" id="editarPrecioProducto" name="editarPrecioProducto">
                    </div>
                    <div class="mb-3">
                        <label for="editarTipoProducto" class="form-label">Tipo:</label>
                        <select class="form-select" id="editarTipoProducto" name="editarTipoProducto">
                            <option value="">Seleccionar...</option>
                            <option value="BEBIDA">Bebida</option>
                            <option value="BEBIDA ALCOHOLICA">Bebida Alcoholica</option>
                            <option value="COMIDA">Comida</option>
                            <option value="OTRO">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editarPrioridadProducto" class="form-label">Prioridad:</label>
                        <select class="form-select" id="editarPrioridadProducto" name="editarPrioridadProducto">
                            <option value="">Seleccionar...</option>
                            <!-- Generar opciones del 1 al 10 -->
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <!-- Input oculto para almacenar el valor de agregarAlStock -->
                        <input type="hidden" id="agregarAlStockHidden" name="agregarAlStockHidden">
                    </div>
                    <div class="mb-3">
                        <label for="editarImagenProducto" class="form-label">Imagen:</label>
                        <input type="file" class="form-control" id="editarImagenProducto" name="editarImagenProducto">
                    </div>
                    <input type="hidden" id="idProductoEditar" name="idProductoEditar">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <!-- Botón de guardar cambios -->
                <button type="button" class="btn btn-primary" id="btnGuardarCambios">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para abrir el modal de edición
    function abrirModalEditar(producto) {
        // Llenar los campos del formulario con la información del producto seleccionado
        document.getElementById('editarNombreProducto').value = producto['NOMBRE'];
        document.getElementById('editarPrecioProducto').value = producto['PRECIO'];
        document.getElementById('editarTipoProducto').value = producto['TIPO'];
        document.getElementById('idProductoEditar').value = producto['_id'];
        // document.getElementById('quitarAlStock').value = producto[''];
        // document.getElementById('agregarAlStock').value = producto[''];

        // Abrir el modal de edición
        var modal = new bootstrap.Modal(document.getElementById('modalEditarProducto'));
        modal.show();
    }

    // Función para guardar los cambios del producto y agregar stock
    document.getElementById('btnGuardarCambios').addEventListener('click', function () {
        // Obtener los valores del formulario
        var nombre = document.getElementById('editarNombreProducto').value;
        var precio = document.getElementById('editarPrecioProducto').value;
        var idProducto = document.getElementById('idProductoEditar').value;
        var imagenProducto = document.getElementById('editarImagenProducto').files[0]; // Imagen seleccionada
        var tipo = document.getElementById('editarTipoProducto').value;
        var prioridad = document.getElementById('editarPrioridadProducto').value; // Nueva línea

        // Crear un objeto FormData con los datos del formulario
        var formData = new FormData();
        formData.append('tipoProducto', tipo);
        formData.append('nombreProducto', nombre);
        formData.append('precioProducto', precio);
        formData.append('idProducto', idProducto);
        formData.append('imagenProducto', imagenProducto); // Agregar imagen
        formData.append('prioridadProducto', prioridad); // Nueva línea

        // Realizar una solicitud AJAX para guardar los cambios del producto
        $.ajax({
            url: 'modificar_producto.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Mostrar una alerta con Sweet Alert si la solicitud fue exitosa
                Swal.fire({
                    title: '¡Cambios guardados!',
                    text: 'Se modificó correctamente el producto.',
                    icon: 'success'
                }).then(function () {
                    window.location.reload(); // Recargar la página
                });
            },
            error: function () {
                // Mostrar una alerta si ocurre un error
                Swal.fire({
                    title: 'Error',
                    text: 'Ha ocurrido un error al guardar los cambios del producto',
                    icon: 'error'
                });
            }
        });
    });

</script>