<?php
// Incluir archivos necesarios
$pageTitle = "Clientes";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php'; // Suponiendo que aquí se encuentra la configuración de la conexión a la base de datos

// Variable para almacenar la consulta SQL
$sql = "SELECT * FROM clientes WHERE VISIBLE = 1";

// Variable para almacenar el resultado de la consulta SQL
$result = mysqli_query($con, $sql);

// Variable para almacenar los resultados de la búsqueda
$clientes = [];

// Verificar si se obtuvieron resultados
if (mysqli_num_rows($result) > 0) {
    // Almacenar los clientes en un arreglo
    while ($row = mysqli_fetch_assoc($result)) {
        $clientes[] = $row;
    }
}
?>
<div class="container">
    <div class="row">
        <div class="col msjs position-absolute top-0 start-50 translate-middle-x" style="z-index: 1000;">
            <?php
            include ('msjs.php');
            ?>
        </div>
    </div>
</div>
<div class="container">
    <div class="row m-0 p-0 filtrosProductos">
        <div class="d-flex col-12 col-md-6 mt-4">
            <p class="my-auto me-3">Busqueda:</p>
            <input type="text" class="form-control" id="buscarCliente" placeholder="Buscar por nombre">
        </div>
        <div class="d-flex col-12 col-md-3 mt-4">
            <p class="my-auto me-3">Filtro:</p>
            <select class="form-select" id="filtroCliente" onchange="ordenarClientes()">
                <option value="todos">Todos</option>
                <option value="id">ID</option>
                <option value="nombre">Nombre</option>
                <option value="email">Email</option>
                <option value="telefono">Teléfono</option>
            </select>
        </div>
    </div>
    <!-- Campo de búsqueda -->
    <div class="rounded tablaTurnosAll tablaProductos tablaClientes my-4 shadow py-2 px-4">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th class="nombre">Nombre</th>
                    <th class="email">Email</th>
                    <th class="telefono">Teléfono</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Mostrar los clientes en la tabla
                foreach ($clientes as $cliente) {
                    echo "<tr class='align-middle'>";
                    echo "<td>" . $cliente['_id'] . "</td>";
                    echo "<td class='nombre'>" . $cliente['NOMBRE'] . "</td>";
                    echo "<td class='email'>" . $cliente['MAIL'] . "</td>";
                    echo "<td class='telefono'>" . $cliente['TELEFONO'] . "</td>";
                    echo "<td>";
                    echo "<a href='#' onclick='abrirModalModificarCliente(" . json_encode($cliente) . ")'><i class='fas fa-edit iconEditProducto'></i></a>";
                    echo "<a href='#' onclick='eliminarCliente(" . $cliente['_id'] . ")'><i class='fas fa-trash-alt iconTrashProducto'></i></a>";
                    echo "</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
    <div class="d-flex justify-content-center">
        <button class="btn" id="agregarCliente" onclick="abrirModalAgregarCliente()">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor"
                class="bi bi-plus-circle-fill iconAdd" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
            </svg>
        </button>
    </div>
</div>

<?php
include 'modalModificarCliente.php';
include 'modalNuevoCliente.php';
include 'common_scripts.php';
?>

<script>
    setTimeout(function () {
        $(".alert").slideUp(300);
    }, 3000);
</script>

<script>
    function abrirModalAgregarCliente() {
        $('#modalNuevoCliente').modal('show');
    }
</script>

<script>
    // Función para filtrar productos por nombre
    document.getElementById("buscarCliente").addEventListener("keyup", function () {
        var filtro = this.value.toLowerCase();
        var filas = document.querySelectorAll(".tablaClientes tbody tr");
        filas.forEach(function (fila) {
            var nombre = fila.getElementsByTagName("td")[1].textContent.toLowerCase();
            if (nombre.includes(filtro)) {
                fila.style.display = "table-row";
            } else {
                fila.style.display = "none";
            }
        });
    });
</script>

<script>
    // Función para ordenar clientes según el criterio seleccionado en el menú desplegable
    function ordenarClientes() {
        var criterio = document.getElementById("filtroCliente").value;

        // Obtener las filas de la tabla de clientes
        var filas = document.querySelectorAll(".tablaClientes tbody tr");

        // Convertir las filas en un array para poder ordenarlas
        var filasArray = Array.from(filas);

        // Ordenar las filas según el criterio seleccionado
        if (criterio === "nombre") {
            filasArray.sort(function (a, b) {
                var nombreA = a.querySelector(".nombre").textContent.toLowerCase();
                var nombreB = b.querySelector(".nombre").textContent.toLowerCase();
                if (nombreA < nombreB) {
                    return -1;
                }
                if (nombreA > nombreB) {
                    return 1;
                }
                return 0;
            });
        } else if (criterio === "email") {
            filasArray.sort(function (a, b) {
                var emailA = a.querySelector(".email").textContent.toLowerCase();
                var emailB = b.querySelector(".email").textContent.toLowerCase();
                if (emailA < emailB) {
                    return -1;
                }
                if (emailA > emailB) {
                    return 1;
                }
                return 0;
            });
        } else if (criterio === "telefono") {
            filasArray.sort(function (a, b) {
                var telefonoA = a.querySelector(".telefono").textContent;
                var telefonoB = b.querySelector(".telefono").textContent;
                return telefonoA.localeCompare(telefonoB);
            });
        } else if (criterio === "id") {
            filasArray.sort(function (a, b) {
                var idA = parseInt(a.querySelector("td").textContent);
                var idB = parseInt(b.querySelector("td").textContent);
                return idA - idB;
            });
        }

        // Eliminar las filas existentes de la tabla
        var tabla = document.querySelector(".tablaClientes tbody");
        tabla.innerHTML = "";

        // Agregar las filas ordenadas a la tabla
        filasArray.forEach(function (fila) {
            tabla.appendChild(fila);
        });
    }
</script>


<script>
    // Función para eliminar un cliente y cambiar el atributo VISIBLE
    function eliminarCliente(id) {
        // Confirmar con el usuario antes de eliminar el cliente
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
                // Realizar una solicitud AJAX para cambiar el atributo VISIBLE del cliente
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "eliminar_cliente.php?id=" + id, true);
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        // Obtener la respuesta del servidor
                        var response = JSON.parse(xhr.responseText);
                        // Mostrar una alerta con Sweet Alert
                        if (response.success) {
                            Swal.fire({
                                title: '¡Eliminado!',
                                text: response.message,
                                icon: 'success'
                            }).then(function () {
                                // Recargar la página después de eliminar el cliente
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
                xhr.send();
            }
        });
    }
</script>


</body>

</html>