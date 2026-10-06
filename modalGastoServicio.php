<!-- Modal para agregar un nuevo gasto de servicio -->
<div class="modal fade" id="gastoServicioModal" tabindex="-1" aria-labelledby="gastoServicioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="gastoServicioModalLabel">Registrar Gasto de Servicio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formGastoServicio">
                    <div class="form-group mt-2">
                        <label for="nombreGastoServicio">Nombre del Gasto</label>
                        <input type="text" class="form-control" id="nombreGastoServicio" name="nombreGastoServicio" required>
                    </div>
                    <div class="form-group mt-2">
                        <label for="montoGastoServicio">Monto</label>
                        <input type="number" class="form-control" id="montoGastoServicio" name="montoGastoServicio" required step="0.01">
                    </div>
                    <div class="form-group mt-2 d-flex align-items-center">
                        <input type="radio" id="tipoGastoServicioEfectivo" name="tipoGastoServicio" value="efectivo" class="custom-radio" checked>
                        <label for="tipoGastoServicioEfectivo" class="ms-2">Efectivo</label>
                        <input type="radio" id="tipoGastoServicioTransferencia" name="tipoGastoServicio" value="transferencia" class="custom-radio ms-3">
                        <label for="tipoGastoServicioTransferencia" class="ms-2">Transferencia</label>
                    </div>
                    <div class="form-group mt-2">
                        <label for="fechaGastoServicio">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fechaGastoServicio" name="fechaGastoServicio" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cerrar</button>
                <button type="submit" class="btn btn-primary" form="formGastoServicio">Registrar Gasto</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para abrir el modal de gasto de servicio
    function abrirModalGastoServicio() {
        // Establecer la fecha y hora actual en el campo de fecha
        const now = new Date();
        const fechaActual = now.toISOString().slice(0, 16); // Formato YYYY-MM-DDTHH:MM
        document.getElementById('fechaGastoServicio').value = fechaActual;
        
        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('gastoServicioModal'));
        modal.show();
    }

    // Manejar el envío del formulario de gasto de servicio
    document.getElementById('formGastoServicio').addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Obtener los datos del formulario
        const nombre = document.getElementById('nombreGastoServicio').value;
        const monto = parseFloat(document.getElementById('montoGastoServicio').value);
        const fecha = document.getElementById('fechaGastoServicio').value;
        const tipoGasto = document.querySelector('input[name="tipoGastoServicio"]:checked').value;

        // Enviar los datos mediante AJAX a guardar_gasto_servicio.php
        $.ajax({
            url: 'guardar_gasto_servicio.php',
            type: 'POST',
            data: {
                nombreGastoServicio: nombre,
                montoGastoServicio: monto,
                fechaGastoServicio: fecha,
                tipoGastoServicio: tipoGasto,
            },
            success: function(response) {
                // Mostrar mensaje de éxito con SweetAlert2
                Swal.fire({
                    icon: 'success',
                    title: 'Gasto de servicio registrado',
                    text: 'El gasto de servicio se ha registrado exitosamente',
                    showConfirmButton: false,
                    timer: 1500
                }).then(() => {
                    // Cerrar el modal y recargar la página
                    const modal = bootstrap.Modal.getInstance(document.getElementById('gastoServicioModal'));
                    modal.hide();
                    location.reload();
                });
            },
            error: function(xhr, status, error) {
                // Mostrar mensaje de error con SweetAlert2
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al registrar el gasto de servicio: ' + error
                });
            }
        });
    });
</script>
