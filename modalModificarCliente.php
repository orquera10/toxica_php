<!-- Modal para modificar cliente -->
<div class="modal fade" id="modalModificarCliente" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modificar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formModificarCliente">
                    <div class="mb-3">
                        <label for="nombreCliente" class="form-label">Nombre:</label>
                        <input type="text" class="form-control" id="nombreCliente" name="nombreCliente">
                    </div>
                    <div class="mb-3">
                        <label for="emailCliente" class="form-label">Email:</label>
                        <input type="email" class="form-control" id="emailCliente" name="emailCliente">
                    </div>
                    <div class="mb-3">
                        <label for="telefonoCliente" class="form-label">Teléfono:</label>
                        <input type="text" class="form-control" id="telefonoCliente" name="telefonoCliente">
                    </div>
                    <input type="hidden" id="idClienteModificar" name="idClienteModificar">
                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    // Función para abrir el modal de modificar cliente y cargar los datos del cliente en el formulario
    function abrirModalModificarCliente(cliente) {
        document.getElementById("nombreCliente").value = cliente['NOMBRE'];
        document.getElementById("emailCliente").value = cliente['MAIL'];
        document.getElementById("telefonoCliente").value = cliente['TELEFONO'];
        document.getElementById("idClienteModificar").value = cliente['_id'];
        $('#modalModificarCliente').modal('show');
    }
    // Función para manejar el envío del formulario de modificación del cliente
    document.getElementById("formModificarCliente").addEventListener("submit", function (event) {
        event.preventDefault();
        var formData = new FormData(this);
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "modificar_cliente.php", true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    Swal.fire({
                        title: '¡Éxito!',
                        text: response.message,
                        icon: 'success'
                    }).then(function () {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error',
                        text: response.message,
                        icon: 'error'
                    });
                }
            }
        };
        xhr.send(formData);
    });
</script>

