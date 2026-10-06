<!-- Modal de clientes -->
<div class="modal fade" id="clientesModal" tabindex="-1" aria-labelledby="clientesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clientesModalLabel">Seleccionar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col">
                        <!-- Campo de búsqueda -->
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" id="buscarCliente" placeholder="Buscar cliente...">
                            <!-- <button class="btn btn-primary" type="button" id="btnBuscarCliente">Buscar</button> -->
                            <button class="btn" id="agregarCliente" onclick="abrirModalNuevoCliente()">
                                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor"
                                    class="bi bi-plus-circle-fill iconAdd" viewBox="0 0 16 16">
                                    <path
                                        d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Contenido dinámico de la lista de clientes -->
                <?php
                // Consulta SQL para seleccionar los nombres de los clientes
                $sql_clientes = "SELECT _id, NOMBRE FROM clientes WHERE VISIBLE = 1";
                // Ejecutar la consulta
                $clientes = mysqli_query($con, $sql_clientes);
                if (mysqli_num_rows($clientes) > 0) {
                    echo '<ul class="list-group listaNombres">';
                    while ($fila_cliente = mysqli_fetch_assoc($clientes)) {
                        echo '<li class="list-group-item nombreCliente"><a href="#" class="seleccionar-cliente" data-id="' . $fila_cliente['_id'] . '">' . $fila_cliente['NOMBRE'] . '</a></li>';
                    }
                    echo '</ul>';
                } else {
                    echo "<p class='text-center'>No se encontraron clientes en la base de datos.</p>";
                }
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


