<!-- Modal para registrar pago -->
<div class="modal fade" id="modalRegistrarPago" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formRegistrarPago">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Registrar pago a <span id="nombreEmpleadoPago"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idEmpleadoPago" name="id_USER">
                    <div class="mb-3">
                        <label for="fechaPago" class="form-label">Fecha del Pago</label>
                        <input type="date" class="form-control" id="fechaPago" name="FECHA" required>
                    </div>
                    <div class="mb-3">
                        <label for="montoPago" class="form-label">Monto</label>
                        <input type="number" class="form-control" id="montoPago" name="MONTO" required min="0"
                            step="1000">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Registrar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>


<script>
    document.getElementById('formRegistrarPago').addEventListener('submit', function (e) {
        e.preventDefault();

        const montoInput = document.getElementById('montoPago');
        const monto = montoInput.value;

        if (!monto || isNaN(monto) || monto <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Monto inválido',
                text: 'Por favor, ingresá un monto válido.',
                confirmButtonColor: '#d33'
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: `¿Deseás registrar un pago de $${monto}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Sí, registrar',
            cancelButtonText: 'Cancelar',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((result) => {
            if (result.isConfirmed) {
                const datos = new FormData(e.target);

                fetch('registrar_pago.php', {
                    method: 'POST',
                    body: datos
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Pago registrado!',
                                text: 'El pago fue guardado correctamente.',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Ocurrió un problema al registrar el pago.',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(err => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo comunicar con el servidor.',
                            confirmButtonColor: '#d33'
                        });
                        console.error('Error al registrar pago:', err);
                    });
            }
        });
    });
</script>