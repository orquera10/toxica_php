<!-- Modal para agregar una nueva seña -->
<div class="modal fade" id="seniaModal" tabindex="-1" aria-labelledby="seniaModalLabel" aria-hidden="true"
    data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="seniaModalLabel">Registrar Nueva Seña</h5>
            </div>
            <div class="modal-body">
                <form id="formSenia">
                    <div class="form-group mt-2">
                        <label for="dejaSenia">Persona que deja la seña</label>
                        <input type="text" class="form-control" id="dejaSenia" name="dejaSenia" required>
                    </div>
                    <div class="form-group mt-2">
                        <label for="montoSenia">Monto</label>
                        <input type="number" class="form-control" id="montoSenia" name="montoSenia" step="0.01"
                            required>
                    </div>
                    <div class="form-group mt-2 d-flex align-items-center">
                        <input type="radio" id="tipoPagoEfectivo" name="tipoPago" value="efectivo" class="custom-radio"
                            checked>
                        <label for="tipoPagoEfectivo" class="ms-2">Efectivo</label>
                        <input type="radio" id="tipoPagoTransferencia" name="tipoPago" value="transferencia"
                            class="custom-radio ms-3">
                        <label for="tipoPagoTransferencia" class="ms-2">Transferencia</label>
                    </div>
                    <div class="form-group mt-2">
                        <label for="fechaSenia">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fechaSenia" name="fechaSenia" required>
                    </div>

                    <div class="form-group mt-2">
                        <label for="recibeSenia">Persona que recibe la seña</label>
                        <input type="text" class="form-control" id="recibeSenia" name="recibeSenia" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal"
                    onclick="cerrarModalPago()">Cerrar</button>
                <button type="submit" class="btn btn-primary" form="formSenia">Registrar Seña</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para abrir el modal de gasto
    function abrirModalSenia() {
        $('#seniaModal').modal('show');
        $('#modalUpdateEvento').modal('hide');
    }

    document.getElementById('formSenia').addEventListener('submit', function (event) {
        event.preventDefault();

        const monto = parseFloat(document.getElementById('montoSenia').value);
        const fecha = document.getElementById('fechaSenia').value;
        const tipoPago = document.querySelector('input[name="tipoPago"]:checked').value;
        const dejaSenia = document.getElementById('dejaSenia').value.trim();
        const recibeSenia = document.getElementById('recibeSenia').value.trim();
        const idEvento = $('#idEvento').val();
        const idCliente = $('#idCliente').val();

        if (isNaN(monto) || !fecha || !idEvento || !idCliente || monto <= 0 || !dejaSenia || !recibeSenia) {
            Swal.fire({
                icon: 'warning',
                title: 'Datos incompletos',
                text: 'Por favor, completá todos los campos correctamente.'
            });
            return;
        }

        $.ajax({
            url: 'actualizar_senia.php',
            type: 'POST',
            dataType: 'json',
            data: {
                montoGasto: monto,
                fechaGasto: fecha,
                tipoPago: tipoPago,
                dejaSenia: dejaSenia,
                recibeSenia: recibeSenia,
                idEvento: idEvento,
                idCliente: idCliente
            },
            success: function (response) {
                if (response.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Seña registrada',
                        text: 'La seña se ha registrado exitosamente'
                    }).then(() => {
                        $('#seniaModal').modal('hide');
                        document.getElementById('formSenia').reset();
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message
                    });
                }
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Hubo un error al registrar la seña: ' + error
                });
            }
        });
    });
</script>