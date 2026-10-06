<?php
include ('config.php');

// Obtener el idEvento desde $_POST
if (isset($_POST['idEvento'])) {
    $idEvento = $_POST['idEvento'];

    // Consulta preparada SQL para obtener los productos
    $sql = "SELECT p.NOMBRE AS producto, d.CANTIDAD AS cantidad, d.PRECIO AS precio, d.CANTIDAD * d.PRECIO AS total, p._id AS idProducto, p.URL_IMG AS url_img
            FROM turnos e
            JOIN ticket t ON e._id = t.id_TURNO
            JOIN detalle_ticket d ON t._id = d.id_TICKET
            JOIN producto p ON d.id_PRODUCTO = p._id
            WHERE e._id = ?";

    // Preparar la declaración SQL
    $stmt = mysqli_prepare($con, $sql);

    // Vincular parámetros a la declaración SQL
    mysqli_stmt_bind_param($stmt, "i", $idEvento);

    // Ejecutar la consulta preparada
    mysqli_stmt_execute($stmt);

    // Obtener resultados de la consulta
    $result = mysqli_stmt_get_result($stmt);

    // Verificar si se obtuvieron resultados
    if (mysqli_num_rows($result) > 0) {
        // Construir el HTML de la tabla de productos
        $html = '';
        while ($row = mysqli_fetch_assoc($result)) {
            $html .= "<tr class='align-middle'>";
            $html .= "<td><img src='" . htmlspecialchars($row["url_img"]) . "' alt='Imagen de producto' class='img-small'></td>";
            $html .= "<td>" . htmlspecialchars($row["producto"]) . "</td>";
            $html .= "<td>" . htmlspecialchars($row["cantidad"]) . "</td>";
            $html .= "<td>" . htmlspecialchars($row["precio"]) . "</td>";
            $html .= "<td>" . htmlspecialchars($row["total"]) . "</td>";
            $html .= "<td><button class='btn btnEliminarProducto' data-idProducto='" . $row["idProducto"] . "'><svg xmlns='http://www.w3.org/2000/svg' width='20' height='20' fill='currentColor' class='bi bi-trash-fill iconTrash' viewBox='0 0 16 16'>
            <path d='M2.5 1a1 1 0 0 0-1 1v1a1 1 0 0 0 1 1H3v9a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V4h.5a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H10a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1zm3 4a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 .5-.5M8 5a.5.5 0 0 1 .5.5v7a.5.5 0 0 1-1 0v-7A.5.5 0 0 1 8 5m3 .5v7a.5.5 0 0 1-1 0v-7a.5.5 0 0 1 1 0'/>
          </svg></button></td>";
            $html .= "</tr>";
        }
        // Imprimir el HTML
        echo $html;
    } else {
        // Si no se encontraron productos, imprimir un mensaje
        echo "<tr><td colspan='4'>No se encontraron productos para este evento</td></tr>";
    }

    // Cerrar la declaración SQL
    mysqli_stmt_close($stmt);
} else {
    // Si no se encontraron productos, imprimir un mensaje
    echo "<tr><td colspan='4'>No se encuentra variable idEvento</td></tr>";
}
?>