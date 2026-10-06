<div class="modal fade" id="modalExtra" tabindex="-1" aria-labelledby="modalExtraLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalExtraLabel">Agregar Extra</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formExtra">
                    <div class="mb-3">
                        <label for="montoExtra" class="form-label">Monto</label>
                        <input type="number" class="form-control" id="montoExtra" name="montoExtra"
                            placeholder="Ej: 500" required>
                    </div>
                    <div class="mb-3">
                        <label for="fechaExtra" class="form-label">Fecha</label>
                        <input type="datetime-local" class="form-control" id="fechaExtra" name="fechaExtra" required>
                    </div>
                    <div class="mb-3">
                        <label for="detalleExtra" class="form-label">Detalle</label>
                        <textarea class="form-control" id="detalleExtra" name="detalleExtra" rows="2"
                            placeholder="Ej: Metegol, Sapo..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="guardarExtra">Guardar</button>
            </div>
        </div>
    </div>
</div>