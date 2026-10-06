<!-- modalEditarEmpleado.php -->
<div class="modal fade" id="modalEditarEmpleado" tabindex="-1" aria-labelledby="modalEditarEmpleadoLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarEmpleadoLabel">Editar Empleado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formEditarEmpleado">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="edit_nombre" name="NOMBRE" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_usuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="edit_usuario" name="USUARIO" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_entrada" class="form-label">Hora de Entrada</label>
                        <input type="time" class="form-control" id="edit_entrada" name="HR_ENTRADA" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_salida" class="form-label">Hora de Salida</label>
                        <input type="time" class="form-control" id="edit_salida" name="HR_SALIDA" required>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById("formEditarEmpleado").addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('editar_usuario.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Actualizado', data.message, 'success').then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            })
            .catch(() => {
                Swal.fire('Error', 'No se pudo conectar con el servidor.', 'error');
            });
    });

</script>