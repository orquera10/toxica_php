<?php
// Incluir archivos necesarios
$pageTitle = "Productos";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
include 'config.php'; // Suponiendo que aquí se encuentra la configuración de la conexión a la base de datos

// Variable para almacenar la consulta SQL
$sql = "SELECT producto.*, (EN_CATALOGO + 0) AS MOSTRAR_CATALOGO
        FROM producto
        WHERE VISIBLE = 1
        ORDER BY IMPORTANCIA, NOMBRE";

// Variable para almacenar el resultado de la consulta SQL
$result = mysqli_query($con, $sql);

// Variable para almacenar los resultados de la búsqueda
$productos = [];

// Verificar si se obtuvieron resultados
if (mysqli_num_rows($result) > 0) {
    // Almacenar los productos en un arreglo
    while ($row = mysqli_fetch_assoc($result)) {
        $productos[] = $row;
    }
}
?>

<div class="container">
    <div class="row m-0 p-0 filtrosProductos">
        <div class="d-flex col-12 col-md-6 mt-4">
            <p class="my-auto me-3">Busqueda:</p>
            <input type="text" class="form-control" id="buscarProducto" placeholder="Buscar por nombre">
        </div>
        <!-- Menú desplegable para seleccionar el criterio de ordenamiento -->
        <div class="d-flex mt-4 col-md-3">
            <p class="my-auto me-3">Filtro:</p>
            <select class="form-select" id="ordenarPor" onchange="ordenarProductos()">
                
                <option value="prioridad">Prioridad</option>
                <option value="nombre">Nombre</option>
                <option value="precio">Precio</option>
                <option value="stock">Stock</option>
            </select>
        </div>
    </div>
    <!-- Campo de búsqueda -->

    <div class="d-flex justify-content-center mt-3">
        <button class="btn" id="agregarProducto" onclick="abrirModalAgregarProductos()">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="currentColor"
                class="bi bi-plus-circle-fill iconAdd" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
            </svg>
        </button>
    </div>


    <div class="row d-flex align-items-stretch">
        <!-- Columna izquierda -->
        <div class="col-md-6">
            <div class="rounded tablaTurnosAll tablaProductos tabModIzq my-4 shadow py-2 px-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Prioridad</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Catálogo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Mostrar los productos en la tabla
                        foreach ($productos as $producto) {
                            if ($producto['TIPO'] !== 'BEBIDA') {
                                continue;
                            }
                            echo "<tr class='align-middle'>";
                            echo "<td><img src='" . $producto['URL_IMG'] . "' alt='" . $producto['NOMBRE'] . "' style='max-width: 50px; max-height: 50px;'></td>";
                            echo "<td>" . $producto['IMPORTANCIA'] . "</td>";
                            echo "<td>" . $producto['NOMBRE'] . "</td>";
                            echo "<td>" . $producto['PRECIO'] . " $</td>";

                            // Verificar si el stock es menor a 5
                            $stockClass = $producto['STOCK'] <= 5 ? 'color:red !important; font-weight: bold !important;' : '';
                            echo "<td style='" . $stockClass . "'>" . $producto['STOCK'] . "</td>";
                            $visibleCatalogo = (int) $producto['MOSTRAR_CATALOGO'] === 1;
                            echo "<td>";
                            echo "<div class='form-check form-switch d-flex justify-content-center'>";
                            echo "<input class='form-check-input' type='checkbox' role='switch' aria-label='Mostrar " . htmlspecialchars($producto['NOMBRE'], ENT_QUOTES, 'UTF-8') . " en el catálogo' " . ($visibleCatalogo ? "checked" : "") . " onchange='cambiarVisibilidadCatalogo(this, " . (int) $producto['_id'] . ")'>";
                            echo "</div>";
                            echo "</td>";
                            echo "<td>";
                            echo "<a href='#' onclick='abrirModalEditar(" . json_encode($producto) . ")'><i class='fas fa-edit iconEditProducto'></i></a>";
                            echo "<a href='#' onclick='eliminarProducto(" . $producto['_id'] . ")'><i class='fas fa-trash-alt iconTrashProducto'></i></a>";
                            echo "<a href='#' onclick='abrirModalStock(" . json_encode($producto) . ")' class='btn btn-danger ms-2'>Stock</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Columna derecha -->
        <div class="col-md-6 d-flex flex-column">
            <div class="rounded tablaTurnosAll tablaProductos tabModDer mt-4 mb-2 shadow py-2 px-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Prioridad</th>
                            <th>Nombre</th>
                            <th>Precio</th>

                            <th>Stock</th>
                            <th>Catálogo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Mostrar los productos en la tabla
                        foreach ($productos as $producto) {
                            if ($producto['TIPO'] !== 'BEBIDA ALCOHOLICA') {
                                continue;
                            }
                            echo "<tr class='align-middle'>";
                            echo "<td><img src='" . $producto['URL_IMG'] . "' alt='" . $producto['NOMBRE'] . "' style='max-width: 50px; max-height: 50px;'></td>";
                            echo "<td>" . $producto['IMPORTANCIA'] . "</td>";
                            echo "<td>" . $producto['NOMBRE'] . "</td>";
                            echo "<td>" . $producto['PRECIO'] . " $</td>";
                            // Verificar si el stock es menor a 5
                            $stockClass = $producto['STOCK'] <= 5 ? 'color:red !important; font-weight: bold !important;' : '';
                            echo "<td style='" . $stockClass . "'>" . $producto['STOCK'] . "</td>";
                            $visibleCatalogo = (int) $producto['MOSTRAR_CATALOGO'] === 1;
                            echo "<td>";
                            echo "<div class='form-check form-switch d-flex justify-content-center'>";
                            echo "<input class='form-check-input' type='checkbox' role='switch' aria-label='Mostrar " . htmlspecialchars($producto['NOMBRE'], ENT_QUOTES, 'UTF-8') . " en el catálogo' " . ($visibleCatalogo ? "checked" : "") . " onchange='cambiarVisibilidadCatalogo(this, " . (int) $producto['_id'] . ")'>";
                            echo "</div>";
                            echo "</td>";
                            echo "<td>";
                            echo "<a href='#' onclick='abrirModalEditar(" . json_encode($producto) . ")'><i class='fas fa-edit iconEditProducto'></i></a>";
                            echo "<a href='#' onclick='eliminarProducto(" . $producto['_id'] . ")'><i class='fas fa-trash-alt iconTrashProducto'></i></a>";
                            echo "<a href='#' onclick='abrirModalStock(" . json_encode($producto) . ")' class='btn btn-danger ms-2'>Stock</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            <div class="rounded tablaTurnosAll tablaProductos tabModDer mb-4 mt-2 shadow py-2 px-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Prioridad</th>
                            <th>Nombre</th>
                            <th>Precio</th>

                            <th>Stock</th>
                            <th>Catálogo</th>
                            <th>Accion</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Mostrar los productos en la tabla
                        foreach ($productos as $producto) {
                            if ($producto['TIPO'] !== 'COMIDA') {
                                continue;
                            }
                            echo "<tr class='align-middle'>";
                            echo "<td><img src='" . $producto['URL_IMG'] . "' alt='" . $producto['NOMBRE'] . "' style='max-width: 50px; max-height: 50px;'></td>";
                            echo "<td>" . $producto['IMPORTANCIA'] . "</td>";
                            echo "<td>" . $producto['NOMBRE'] . "</td>";
                            echo "<td>" . $producto['PRECIO'] . " $</td>";

                            // Verificar si el stock es menor a 5
                            $stockClass = $producto['STOCK'] <= 5 ? 'color:red !important; font-weight: bold !important;' : '';
                            echo "<td style='" . $stockClass . "'>" . $producto['STOCK'] . "</td>";
                            $visibleCatalogo = (int) $producto['MOSTRAR_CATALOGO'] === 1;
                            echo "<td>";
                            echo "<div class='form-check form-switch d-flex justify-content-center'>";
                            echo "<input class='form-check-input' type='checkbox' role='switch' aria-label='Mostrar " . htmlspecialchars($producto['NOMBRE'], ENT_QUOTES, 'UTF-8') . " en el catálogo' " . ($visibleCatalogo ? "checked" : "") . " onchange='cambiarVisibilidadCatalogo(this, " . (int) $producto['_id'] . ")'>";
                            echo "</div>";
                            echo "</td>";
                            echo "<td>";
                            echo "<a href='#' onclick='abrirModalEditar(" . json_encode($producto) . ")'><i class='fas fa-edit iconEditProducto'></i></a>";
                            echo "<a href='#' onclick='eliminarProducto(" . $producto['_id'] . ")'><i class='fas fa-trash-alt iconTrashProducto'></i></a>";
                            echo "<a href='#' onclick='abrirModalStock(" . json_encode($producto) . ")' class='btn btn-danger ms-2'>Stock</a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<?php
include 'modalModificarProducto.php';
include 'modalAgregarProducto.php';
include 'modalStock.php';
include 'common_scripts.php';
?>

<script>
    // Función para filtrar productos por nombre
    function cambiarVisibilidadCatalogo(check, idProducto) {
        var visible = check.checked ? 1 : 0;
        check.disabled = true;

        fetch('actualizar_visibilidad_producto.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: new URLSearchParams({ id_producto: idProducto, visible: visible })
        })
            .then(function (respuesta) {
                return respuesta.json().then(function (datos) {
                    if (!respuesta.ok || !datos.success) {
                        throw new Error(datos.message || 'No se pudo actualizar el catálogo.');
                    }
                    return datos;
                });
            })
            .then(function (datos) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: datos.message,
                    showConfirmButton: false,
                    timer: 1800
                });
            })
            .catch(function (error) {
                check.checked = !check.checked;
                Swal.fire({ title: 'No se pudo guardar', text: error.message, icon: 'error' });
            })
            .finally(function () {
                check.disabled = false;
            });
    }

    document.getElementById("buscarProducto").addEventListener("keyup", function () {
        var filtro = this.value.toLowerCase();
        var tablas = document.querySelectorAll(".tablaProductos");
        tablas.forEach(function (tabla) {
            var filas = tabla.querySelectorAll("tbody tr");
            filas.forEach(function (fila) {
                // Nombre está en el segundo td (índice 1 porque el 0 es la imagen)
                var nombre = fila.getElementsByTagName("td")[2].textContent.toLowerCase();
                fila.style.display = nombre.includes(filtro) ? "table-row" : "none";
            });
        });
    });

</script>

<script>
    // Función para manejar el clic en el botón de eliminar producto
    function eliminarProducto(id) {
        // Confirmar con el usuario antes de eliminar el producto
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
                // Realizar una solicitud AJAX para eliminar el producto
                var xhr = new XMLHttpRequest();
                xhr.open("GET", "eliminar_producto.php?id=" + id, true);
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
                                // Recargar la página después de eliminar el producto
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

<script>
    function ordenarProductos() {
        var criterio = document.getElementById("ordenarPor").value;
        var tablas = document.querySelectorAll(".tablaProductos");

        tablas.forEach(function (tabla) {
            var tbody = tabla.querySelector("tbody");
            var filas = Array.from(tbody.querySelectorAll("tr"));

            filas.sort(function (a, b) {
                let valA, valB;

                switch (criterio) {
                    case "prioridad":
                        valA = parseInt(a.getElementsByTagName("td")[1].textContent);
                        valB = parseInt(b.getElementsByTagName("td")[1].textContent);
                        return valA - valB;

                    case "nombre":
                        valA = a.getElementsByTagName("td")[2].textContent.toLowerCase();
                        valB = b.getElementsByTagName("td")[2].textContent.toLowerCase();
                        return valA.localeCompare(valB);

                    case "precio":
                        valA = parseFloat(a.getElementsByTagName("td")[3].textContent);
                        valB = parseFloat(b.getElementsByTagName("td")[3].textContent);
                        return valA - valB;

                    case "stock":
                        valA = parseInt(a.getElementsByTagName("td")[4].textContent);
                        valB = parseInt(b.getElementsByTagName("td")[4].textContent);
                        return valA - valB;

                    default:
                        return 0;
                }
            });

            tbody.innerHTML = "";
            filas.forEach(function (fila) {
                tbody.appendChild(fila);
            });
        });
    }
</script>




</body>

</html>
