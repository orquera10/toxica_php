<!-- Modal para editar producto -->
<div class="modal fade" id="modalStockProducto" tabindex="-1" aria-labelledby="exampleModalStockProducto"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalStockProducto">Editar Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarProducto" enctype="multipart/form-data" action="agregar_stock.php" method="POST">
                    <!-- Campos del formulario -->
                    <div class="mb-3">
                        <p id="nombreProductoStock" class="text-center"></p>
                    </div>

                    <div class="mb-3 row">
                        <div class="col-12 text-center">
                            <p id="stockProducto" class="h1" style="font-size: 5rem"></p>
                        </div>

                        <div class="col-6">
                            <label for="agregarAlStock" class="form-label text-center w-100">Agregar:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="agregarAlStock" name="agregarAlStock">
                                <button class="btn btn-outline-secondary" type="button"
                                    id="btnAgregarStock">Agregar</button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label for="quitarAlStock" class="form-label text-center w-100">Quitar:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="quitarAlStock" name="quitarAlStock">
                                <button class="btn btn-outline-secondary" type="button"
                                    id="btnQuitarAlStock">Quitar</button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <!-- Input oculto para almacenar el valor de agregarAlStock -->
                        <input type="hidden" id="agregarAlStockHidden" name="agregarAlStockHidden">
                    </div>

                    <div class="mb-3">
                        <label for="fechaStock" class="form-label">Fecha y Hora</label>
                        <input type="datetime-local" class="form-control" id="fechaStock" name="fechaStock" required>
                    </div>

                    <div class="mb-3">
                        <label for="detalleStock" class="form-label">Detalle</label>
                        <textarea class="form-control" id="detalleStock" name="detalleStock" rows="3" required></textarea>
                    </div>

                    <input type="hidden" id="idProductoEditar" name="idProductoEditar">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <!-- Botón de guardar cambios -->
                <button type="button" class="btn btn-primary" id="btnGuardarStock">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>

<script>
    

    // Función para abrir el modal de edición
    function abrirModalStock(producto) {
        // Llenar los campos del formulario con la información del producto seleccionado
        document.getElementById('nombreProductoStock').textContent = producto['NOMBRE'];
        document.getElementById('stockProducto').textContent = producto['STOCK'];
        document.getElementById('idProductoEditar').value = producto['_id'];
        document.getElementById('quitarAlStock').value = 0;
        document.getElementById('agregarAlStock').value = 0;
        document.getElementById('agregarAlStockHidden').value = 0;

        // Abrir el modal de edición
        var modal = new bootstrap.Modal(document.getElementById('modalStockProducto'));
        modal.show();
    }

    // Función para guardar los cambios del producto y agregar stock
    document.getElementById('btnGuardarStock').addEventListener('click', function () {

        var stock = document.getElementById('stockProducto').value;
        var idProducto = document.getElementById('idProductoEditar').value;
        
        var agregarAlStock = parseFloat(document.getElementById('agregarAlStockHidden').value);

        // Verificar si el valor ingresado es válido
        if (!isNaN(agregarAlStock) && agregarAlStock !== 0) {
            // Realizar una solicitud AJAX para agregar el stock
            $.ajax({
                url: 'agregar_stock.php',
                type: 'POST',
                data: {
                    id_producto: idProducto,
                    cantidad: agregarAlStock,
                    fecha: document.getElementById('fechaStock').value,
                    detalle: document.getElementById('detalleStock').value
                },
                success: function (response) {
                    if (agregarAlStock > 0) {
                        // Mostrar una alerta con Sweet Alert si la solicitud fue exitosa
                        Swal.fire({
                            title: '¡Cambios guardados!',
                            text: 'Se agregó stock.',
                            icon: 'success'
                        }).then(function () {
                            // Actualizar el valor del input oculto
                            document.getElementById('agregarAlStockHidden').value = 0;
                            // Recargar la página después de guardar los cambios
                            window.location.reload();
                        });
                    } else {
                        // Mostrar una alerta con Sweet Alert si la solicitud fue exitosa
                        Swal.fire({
                            title: '¡Cambios guardados!',
                            text: 'Se quitó stock.',
                            icon: 'success'
                        }).then(function () {
                            // Actualizar el valor del input oculto
                            document.getElementById('agregarAlStockHidden').value = 0;
                            // Recargar la página después de guardar los cambios
                            window.location.reload();
                        });
                    }
                },
                error: function () {
                    // Mostrar una alerta con Sweet Alert si hay un error en la solicitud
                    Swal.fire({
                        title: 'Error',
                        text: 'Ha ocurrido un error al agregar el stock',
                        icon: 'error'
                    });
                }
            });
        } else {
            // Mostrar una alerta con Sweet Alert si no hay stock para agregar
            Swal.fire({
                title: '¡No se cambio el Stock!',
                text: 'No se hicieron modificaciones al stock del producto.',
                icon: 'success'
            }).then(function () {
                // Recargar la página después de guardar los cambios
                window.location.reload();
            });
        }

    });




    // Función para agregar stock al producto
    document.getElementById('btnAgregarStock').addEventListener('click', function () {
        // Obtener el valor ingresado en el campo de agregar al stock
        var agregarAlStock = parseFloat(document.getElementById('agregarAlStock').value);

        // Obtener el valor actual del stock
        var stockActual = parseFloat(document.getElementById('stockProducto').textContent);

        // Verificar si el valor ingresado es válido
        if (!isNaN(agregarAlStock)) {
            // Sumar el valor ingresado al stock actual
            var nuevoStock = stockActual + agregarAlStock;

            // Actualizar el valor del campo de stock
            document.getElementById('stockProducto').textContent = nuevoStock;
            // Actualizar el valor del input oculto
            var stockAux = document.getElementById('agregarAlStockHidden').value;
            // Actualizar el valor del input oculto
            document.getElementById('agregarAlStockHidden').value = Number(stockAux) + Number(agregarAlStock);

            // Limpiar el campo de agregar al stock
            document.getElementById('agregarAlStock').value = '';
        } else {
            // Mostrar un mensaje de error si el valor ingresado no es válido
            alert('Por favor, ingrese un número válido.');
        }
    });


    // Función para quitar stock al producto
    document.getElementById('btnQuitarAlStock').addEventListener('click', function () {
        // Obtener el valor ingresado en el campo de agregar al stock
        var quitarAlStock = parseFloat(document.getElementById('quitarAlStock').value);
        quitarAlStock = -quitarAlStock

        // Obtener el valor actual del stock
        var stockActual = parseFloat(document.getElementById('stockProducto').textContent);

        // Verificar si el valor ingresado es válido
        if (!isNaN(quitarAlStock)) {
            // Sumar el valor ingresado al stock actual
            var nuevoStock = stockActual + quitarAlStock;

            // Actualizar el valor del campo de stock
            document.getElementById('stockProducto').textContent = nuevoStock;
            // Actualizar el valor del input oculto
            var stockAux = document.getElementById('agregarAlStockHidden').value;
            // Actualizar el valor del input oculto
            document.getElementById('agregarAlStockHidden').value = Number(stockAux) + Number(quitarAlStock);

            // Limpiar el campo de agregar al stock
            document.getElementById('quitarAlStock').value = '';
        } else {
            // Mostrar un mensaje de error si el valor ingresado no es válido
            alert('Por favor, ingrese un número válido.');
        }
    });

</script>