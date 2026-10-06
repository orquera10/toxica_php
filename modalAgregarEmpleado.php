<!-- Modal Agregar Empleado -->
<div class="modal fade" id="modalAgregarEmpleado" tabindex="-1" aria-labelledby="modalAgregarEmpleadoLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAgregarEmpleadoLabel">Agregar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form id="formAgregarEmpleado">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Nombre de usuario</label>
                        <input type="text" class="form-control" id="usuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="password" name="password" required
                            minlength="4">
                    </div>
                    <div class="mb-3">
                        <label for="tipo" class="form-label">Tipo de usuario</label>
                        <select class="form-select" id="tipo" name="tipo" required>
                            <option value="">Seleccionar...</option>
                            <option value="admin">Admin</option>
                            <option value="user">User</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="hr_entrada" class="form-label">Hora de entrada</label>
                        <input type="time" class="form-control" id="hr_entrada" name="hr_entrada">
                    </div>
                    <div class="mb-3">
                        <label for="hr_salida" class="form-label">Hora de salida</label>
                        <input type="time" class="form-control" id="hr_salida" name="hr_salida">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Agregar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Restablecer Contraseña -->
<div class="modal fade" id="modalRestablecerClave" tabindex="-1" aria-labelledby="modalRestablecerClaveLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formRestablecerClave">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalRestablecerClaveLabel">Restablecer contraseña</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="clave_id_usuario">
                    <p>Usuario: <strong id="clave_usuario_nombre"></strong></p>
                    <div class="mb-3">
                        <label for="nueva_clave" class="form-label">Nueva contraseña</label>
                        <input type="password" class="form-control" name="nueva_clave" id="nueva_clave" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>



<script>
    document.getElementById("formAgregarEmpleado").addEventListener("submit", function (e) {
        e.preventDefault();

        const form = e.target;
        const formData = new FormData(form);

        fetch('agregar_usuario.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Éxito", data.message, "success").then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(err => {
                console.error(err);
                Swal.fire("Error", "Ocurrió un error al agregar el usuario.", "error");
            });
    });
    
    function abrirModalRestablecerClave(id, usuario) {
        document.getElementById("clave_id_usuario").value = id;
        document.getElementById("clave_usuario_nombre").innerText = usuario;
        document.getElementById("nueva_clave").value = '';
        const modal = new bootstrap.Modal(document.getElementById('modalRestablecerClave'));
        modal.show();
    }

    document.getElementById("formRestablecerClave").addEventListener("submit", function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch("restablecer_clave.php", {
            method: "POST",
            body: formData
        })
            .then(resp => resp.json())
            .then(data => {
                if (data.success) {
                    Swal.fire("Éxito", data.message, "success").then(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('modalRestablecerClave'));
                        modal.hide();
                    });
                } else {
                    Swal.fire("Error", data.message, "error");
                }
            })
            .catch(() => Swal.fire("Error", "Ocurrió un error al restablecer la contraseña", "error"));
    });

</script>