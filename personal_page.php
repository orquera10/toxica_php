<?php
$pageTitle = "Personal";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php';

// Consulta para obtener los empleados (usuarios visibles, opcional si querés filtrar)
$sql = "SELECT * FROM usuarios WHERE ACTIVO = 1 ORDER BY TIPO DESC";
$result = mysqli_query($con, $sql);
$empleados = [];

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $empleados[] = $row;
    }
}
?>

<div class="container">
    <div class="row m-0 p-0 filtrosProductos">
        <div class="d-flex col-12 col-md-6 mt-4">
            <p class="my-auto me-3">Búsqueda:</p>
            <input type="text" class="form-control" id="buscarEmpleado" placeholder="Buscar por usuario">
        </div>
    </div>

    <div class="rounded tablaTurnosAll tablaProductos my-4 shadow py-2 px-4">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Entrada</th>
                    <th>Salida</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($empleados as $empleado): ?>
                    <tr class='align-middle'>
                        <td><?= $empleado['_id'] ?></td>
                        <td><?= htmlspecialchars($empleado['USUARIO']) ?></td>
                        <td><?= htmlspecialchars($empleado['NOMBRE']) ?></td>
                        <td><?= htmlspecialchars($empleado['TIPO']) ?></td>
                        <td><?= htmlspecialchars($empleado['HR_ENTRADA']) ?></td>
                        <td><?= htmlspecialchars($empleado['HR_SALIDA']) ?></td>
                        <td>
                            <a href="#" onclick='abrirModalEditarEmpleado(<?= json_encode($empleado) ?>)'>
                                <i class='fas fa-edit iconEditProducto'></i>
                            </a>
                            <a href="#" onclick='eliminarEmpleado(<?= $empleado['_id'] ?>)'>
                                <i class='fas fa-trash-alt iconTrashProducto'></i>
                            </a>
                            <a href="#"
                                onclick='abrirModalRestablecerClave(<?= $empleado['_id'] ?>, "<?= $empleado['USUARIO'] ?>")'>
                                <i class='fas fa-key iconResetPassword'></i>
                            </a>
                            <?php if (strtolower($empleado['TIPO']) === 'user'): ?>
                                <a class="btn btn-danger pe-4" href="#"
                                    onclick='abrirModalRegistrarPago(<?= $empleado['_id'] ?>, "<?= $empleado['NOMBRE'] ?>")'>
                                    <i class='fas fa-dollar-sign iconPagoEmpleado' title="Registrar pago"></i>Pagar
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center">
        <button class="btn" onclick="abrirModalAgregarEmpleado()">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor"
                class="bi bi-plus-circle-fill iconAdd" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
            </svg>
        </button>
    </div>
</div>

<!-- Modales -->
<?php
include 'modalAgregarEmpleado.php';
include 'modalEditarEmpleado.php';
include 'modalPagoEmpleado.php';
include 'common_scripts.php';
?>

<script>
    document.getElementById("buscarEmpleado").addEventListener("keyup", function () {
        var filtro = this.value.toLowerCase();
        var filas = document.querySelectorAll(".tablaProductos tbody tr");
        filas.forEach(function (fila) {
            var usuario = fila.getElementsByTagName("td")[1].textContent.toLowerCase();
            fila.style.display = usuario.includes(filtro) ? "table-row" : "none";
        });
    });

    function eliminarEmpleado(id) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Esta acción no se puede deshacer.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminarlo'
        }).then((result) => {
            if (result.isConfirmed) {
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "eliminar_empleado.php?id=" + id, true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        var response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: response.message,
                                icon: 'success'
                            }).then(function () {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    }
                };
                xhr.send();
            }
        });
    }

    function abrirModalEditarEmpleado(empleado) {
        if (empleado.TIPO !== 'user') {
            Swal.fire('Solo se pueden editar usuarios de tipo "user".');
            return;
        }

        document.getElementById("edit_id").value = empleado._id;
        document.getElementById("edit_nombre").value = empleado.NOMBRE || '';
        document.getElementById("edit_usuario").value = empleado.USUARIO || '';
        document.getElementById("edit_entrada").value = empleado.HR_ENTRADA || '';
        document.getElementById("edit_salida").value = empleado.HR_SALIDA || '';

        const modal = new bootstrap.Modal(document.getElementById('modalEditarEmpleado'));
        modal.show();
    }

    function abrirModalAgregarEmpleado() {
        const modal = new bootstrap.Modal(document.getElementById('modalAgregarEmpleado'));
        modal.show();
    }

    function abrirModalRegistrarPago(id, nombre) {
        // Setear ID y nombre del empleado
        document.getElementById('idEmpleadoPago').value = id;
        document.getElementById('nombreEmpleadoPago').textContent = nombre;

        // Setear la fecha actual en el input
        const hoy = new Date().toISOString().split('T')[0];
        document.getElementById('fechaPago').value = hoy;

        // Limpiar el monto por si quedó algo cargado
        document.getElementById('montoPago').value = '';

        // Mostrar el modal
        const modal = new bootstrap.Modal(document.getElementById('modalRegistrarPago'));
        modal.show();
    }


</script>
</body>

</html>