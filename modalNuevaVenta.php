<?php

// Consulta SQL para obtener los productos
$sql = "SELECT _id, NOMBRE, PRECIO, STOCK FROM producto WHERE VISIBLE=1";
$result = $con->query($sql);
?>

<!-- Modal para agregar una nueva venta -->
<div class="modal fade" id="modalVenta" tabindex="-1" role="dialog" aria-labelledby="modalVentaLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Nueva Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form name="formVenta" id="formVenta" class="form-horizontal" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="evento" class="form-label">Cliente</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="eventoVenta" placeholder="Cliente" disabled
                                required>
                            <button type="button" class="btn btn-primary" onclick="abrirModalCliente()">Buscar</button>
                        </div>
                        <input type="hidden" name="cliente_id_evento_venta" id="cliente_id_evento_venta">
                    </div>
                    <div class="row">
                        <div class="mb-3 col-8">
                            <label for="producto" class="form-label">Producto</label>
                            <select class="form-select" id="producto" name="producto" oninput="filtrarProductos()">
                                <!-- Listar los productos obtenidos de la base de datos -->
                                <?php
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . $row["_id"] . "' data-precio='" . $row["PRECIO"] . "'>" . $row["NOMBRE"] . " -> Stock: " . $row["STOCK"] ."</option>";
                                    }
                                } else {
                                    echo "<option value=''>No hay productos disponibles</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3 col-4">
                            <label for="cantidad" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="cantidad" name="cantidad" value="1" min="1"
                                required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-center">
                        <button type="button" class="btn" onclick="agregarProducto()">
                            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor"
                                class="bi bi-plus-circle-fill iconAdd" viewBox="0 0 16 16">
                                <path
                                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                            </svg>
                        </button>
                    </div>

                    <div class="mb-3 mt-4">
                        <!-- Tabla para mostrar los productos agregados -->
                        <p style="font-weight: bold;" class="mb-1">Productos Agregados</p>
                        <div class="styleTabla">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Producto</th>
                                        <th scope="col">Precio</th>
                                        <th scope="col">Cantidad</th>
                                        <th scope="col">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="tablaProductosVenta">
                                    <!-- Aquí se mostrarán los productos agregados -->
                                </tbody>
                            </table>
                        </div>
                        <!-- Etiqueta para mostrar el total -->
                        <p style="font-weight: bold; font-size:1.2rem" class="mb-1 text-end">Total: <span
                                id="totalVenta">0</span> $</p>
                        <input type="hidden" name="totalVenta" id="totalVentaInput" value="">
                        <input type="hidden" name="detalleProductos" id="detalleProductos">
                    </div>
                    <div class="mb-3 row">
                        <h5>Pago:</h5>
                        <div class="form-group mt-2 mb-2">
                            <label for="fechaVenta">Fecha y Hora de la Venta</label>
                            <input type="datetime-local" class="form-control" id="fechaVenta" name="fechaVenta" value="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>
                        <div class="col-6">
                            <label for="pagoEfectivoVenta" class="col-sm-12 control-label">Pago efectivo: </label>
                            <input type="number" class="form-control" name="pagoEfectivoVenta" id="pagoEfectivoVenta"
                                value="0" required />
                        </div>
                        <div class="col-6">
                            <label for="pagoTransfVenta" class="col-sm-12 control-label">Pago Transferencia: </label>
                            <input type="number" class="form-control" name="pagoTransfVenta" id="pagoTransfVenta"
                                value="0" required />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" id="guardarVentaButton" class="btn btn-primary">Guardar Venta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Script para abrir el modal de clientes -->
<script>
    function abrirModalCliente() {
        $('#clientesModal').modal('show');
    }
    function abrirModalNuevoCliente() {
        $('#modalNuevoCliente').modal('show');
    }

</script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Obtener el campo de búsqueda y la lista de nombres de clientes
        const campoBuscar = document.getElementById('buscarCliente');
        const listaNombres = document.querySelectorAll('.nombreCliente');

        // Agregar un evento de escucha al campo de búsqueda
        campoBuscar.addEventListener('input', function () {
            const textoBuscar = campoBuscar.value.trim().toLowerCase();

            // Iterar sobre la lista de nombres y ocultar aquellos que no coincidan con la búsqueda
            listaNombres.forEach(function (nombre) {
                const nombreCliente = nombre.textContent.trim().toLowerCase();
                if (nombreCliente.includes(textoBuscar)) {
                    nombre.style.display = 'block';
                } else {
                    nombre.style.display = 'none';
                }
            });
        });
    });
</script>

<script>
    // Función para abrir el modal de venta
    function abrirModalVenta() {
        $('#modalVenta').modal('show'); // Abre el modal de venta
    }
</script>

<script>
    function agregarProducto() {
        // Obtener los valores del producto, precio y cantidad
        const productoSelect = document.getElementById('producto');
        const productoId = productoSelect.value;
        const productoNombre = productoSelect.options[productoSelect.selectedIndex].text;
        const precio = parseFloat(productoSelect.options[productoSelect.selectedIndex].getAttribute('data-precio'));
        const cantidad = parseInt(document.getElementById('cantidad').value);
        const total = precio * cantidad;

        // Verificar si el campo detalleProductos ya tiene contenido
        let detalleProductos = [];
        const detalleProductosInput = document.getElementById('detalleProductos');
        if (detalleProductosInput.value !== '') {
            detalleProductos = JSON.parse(detalleProductosInput.value);
        }

        // Verificar si el producto ya está en la lista de detalles de productos
        const productoExistente = detalleProductos.find(producto => producto.id === productoId);

        if (productoExistente) {
            // Si el producto ya está en la lista, actualizar su cantidad y total
            productoExistente.cantidad += cantidad;
            productoExistente.total += total;
        } else {
            // Si el producto no está en la lista, agregarlo como un nuevo elemento
            detalleProductos.push({
                id: productoId,
                nombre: productoNombre,
                precio: precio,
                cantidad: cantidad,
                total: total
            });
        }

        // Convertir el arreglo a formato JSON y asignarlo al campo detalleProductos
        detalleProductosInput.value = JSON.stringify(detalleProductos);

        // Actualizar la tabla de productos agregados
        const tablaProductos = document.getElementById('tablaProductosVenta');

        // Obtener las filas existentes en la tabla
        const filasExistentes = tablaProductos.querySelectorAll('tr');

        // Iterar sobre los detalles de productos y actualizar o agregar las filas correspondientes
        detalleProductos.forEach(producto => {
            // Verificar si el producto ya está en la tabla
            const filaExistente = Array.from(filasExistentes).find(fila => fila.dataset.id === producto.id);

            if (filaExistente) {
                // Si la fila ya existe, actualizar los valores
                filaExistente.cells[2].textContent = producto.cantidad;
                filaExistente.cells[3].textContent = producto.total;
            } else {
                // Si la fila no existe, agregar una nueva fila
                const nuevaFila = tablaProductos.insertRow();
                nuevaFila.dataset.id = producto.id;
                nuevaFila.innerHTML = `
                <td>${producto.nombre}</td>
                <td>${producto.precio}</td>
                <td>${producto.cantidad}</td>
                <td>${producto.total}</td>
            `;
            }
        });

        // Calcular y mostrar el total de la venta
        calcularMostrarTotalVenta(detalleProductos);

        // Limpiar los campos después de agregar el producto
        document.getElementById('cantidad').value = 1;
    }

    function calcularMostrarTotalVenta(detalleProductos) {
        // Calcular el total de la venta
        let totalVenta = detalleProductos.reduce((total, producto) => total + producto.total, 0);

        // Mostrar el total de la venta
        document.getElementById('totalVenta').innerText = totalVenta;

        // Asignar el total de la venta al campo oculto
        document.getElementById('totalVentaInput').value = totalVenta;
    }

    function filtrarProductos() {
        // Obtener el valor de búsqueda ingresado por el usuario
        const input = document.getElementById("producto");
        const filtro = input.value.toUpperCase();

        // Obtener la lista de opciones
        const opciones = input.getElementsByTagName("option");

        // Iterar sobre las opciones y mostrar u ocultar según el filtro
        for (let i = 0; i < opciones.length; i++) {
            const txtValue = opciones[i].textContent || opciones[i].innerText;
            if (txtValue.toUpperCase().indexOf(filtro) > -1) {
                opciones[i].style.display = "";
            } else {
                opciones[i].style.display = "none";
            }
        }
    }
</script>