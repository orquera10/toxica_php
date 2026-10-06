<!-- Modal para agregar una nuevo gasto -->
<div class="modal fade" id="gastoModal" tabindex="-1" aria-labelledby="gastoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gastoModalLabel">Registrar Nuevo Gasto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formGasto">
                    <div class="form-group mt-2">
                        <label for="nombreGasto">Nombre del Gasto</label>
                        <input type="text" class="form-control" id="nombreGasto" name="nombreGasto" required>
                    </div>
                    <div class="form-group mt-2">
                        <label for="montoGasto">Monto</label>
                        <input type="number" class="form-control" id="montoGasto" name="montoGasto" required>
                    </div>
                    <div class="form-group mt-2 d-flex align-items-center">
                        <input type="radio" id="tipoGastoEfectivo" name="tipoGasto" value="efectivo" class="custom-radio"
                            checked>
                        <label for="tipoGastoEfectivo" class="ms-2">Efectivo</label>
                        <input type="radio" id="tipoGastoTransferencia" name="tipoGasto" value="transferencia"
                            class="custom-radio ms-3">
                        <label for="tipoGastoTransferencia" class="ms-2">Transferencia</label>
                    </div>
                    <div class="form-group mt-2">
                        <label for="fechaGasto">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fechaGasto" name="fechaGasto" required>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary" form="formGasto">Registrar Gasto</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para abrir el modal de gasto
    function abrirModalGasto() {
        $('#gastoModal').modal('show'); // Abre el modal de gasto
    }

    // Optional JavaScript for form handling
    document.getElementById('formGasto').addEventListener('submit', function (event) {
        event.preventDefault();
        // Obtener los datos del formulario
        const nombre = document.getElementById('nombreGasto').value;
        const monto = parseFloat(document.getElementById('montoGasto').value);
        const fecha = document.getElementById('fechaGasto').value;
        const tipoGasto = document.querySelector('input[name="tipoGasto"]:checked').value;

        // Enviar los datos mediante AJAX a guardar_gasto.php
        $.ajax({
            url: 'guardar_gasto.php',
            type: 'POST',
            data: {
                nombreGasto: nombre,
                montoGasto: monto,
                fechaGasto: fecha,
                tipoGasto: tipoGasto,
            },
            success: function (response) {
                // Mostrar mensaje de éxito con SweetAlert2
                Swal.fire({
                    icon: 'success',
                    title: 'Gasto registrado',
                    text: 'El gasto se ha registrado exitosamente'
                }).then(() => {
                    // Cerrar el modal después de enviar los datos
                    $('#gastoModal').modal('hide');
                    // Limpiar el formulario después de enviar los datos
                    document.getElementById('formGasto').reset();
                });
            },
            error: function (xhr, status, error) {
                // Mostrar mensaje de error con SweetAlert2
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al registrar el gasto: ' + error
                });
            }
        });
    });

</script>