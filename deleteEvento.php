<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$id_turno = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

if ($id_turno <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'No se recibio un turno valido.',
    ]);
    exit;
}

mysqli_begin_transaction($con);

try {
    $stmt = $con->prepare("SELECT _id FROM ticket WHERE id_TURNO = ?");
    $stmt->bind_param("i", $id_turno);
    $stmt->execute();
    $resultado_tickets = $stmt->get_result();
    $tickets = $resultado_tickets->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($tickets as $ticket) {
        $id_ticket = (int) $ticket['_id'];

        $stmt = $con->prepare("SELECT id_PRODUCTO, CANTIDAD FROM detalle_ticket WHERE id_TICKET = ?");
        $stmt->bind_param("i", $id_ticket);
        $stmt->execute();
        $productos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($productos as $producto) {
            $id_producto = (int) $producto['id_PRODUCTO'];
            $cantidad = (int) $producto['CANTIDAD'];

            if ($id_producto <= 0 || $cantidad <= 0) {
                continue;
            }

            $stmt = $con->prepare("UPDATE producto SET STOCK = STOCK + ? WHERE _id = ?");
            $stmt->bind_param("ii", $cantidad, $id_producto);
            if (!$stmt->execute()) {
                throw new Exception("No se pudo devolver el stock del producto.");
            }
            $stmt->close();

            $fecha_y_hora = date('d-m-Y H:i');
            $detalle = 'Producto devuelto por turno eliminado';
            $stmt = $con->prepare("INSERT INTO stock (id_PRODUCTO, FECHA, INGRESO, DETALLE) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $id_producto, $fecha_y_hora, $cantidad, $detalle);
            if (!$stmt->execute()) {
                throw new Exception("No se pudo registrar la devolucion de stock.");
            }
            $stmt->close();
        }

        $stmt = $con->prepare("DELETE FROM detalle_ticket WHERE id_TICKET = ?");
        $stmt->bind_param("i", $id_ticket);
        if (!$stmt->execute()) {
            throw new Exception("No se pudo eliminar el detalle del ticket.");
        }
        $stmt->close();

        $stmt = $con->prepare("DELETE FROM detalle_pago WHERE id_TICKET = ?");
        $stmt->bind_param("i", $id_ticket);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $con->prepare("DELETE FROM extras WHERE id_TURNO = ?");
    $stmt->bind_param("i", $id_turno);
    if (!$stmt->execute()) {
        throw new Exception("No se pudieron eliminar los extras del turno.");
    }
    $stmt->close();

    $stmt = $con->prepare("DELETE FROM senias WHERE id_TURNO = ?");
    $stmt->bind_param("i", $id_turno);
    if (!$stmt->execute()) {
        throw new Exception("No se pudieron eliminar las senias del turno.");
    }
    $stmt->close();

    $stmt = $con->prepare("DELETE FROM ticket WHERE id_TURNO = ?");
    $stmt->bind_param("i", $id_turno);
    if (!$stmt->execute()) {
        throw new Exception("No se pudo eliminar el ticket del turno.");
    }
    $stmt->close();

    $stmt = $con->prepare("DELETE FROM turnos WHERE _id = ?");
    $stmt->bind_param("i", $id_turno);
    if (!$stmt->execute()) {
        throw new Exception("No se pudo eliminar el turno.");
    }

    $turnos_eliminados = $stmt->affected_rows;
    $stmt->close();

    if ($turnos_eliminados < 1) {
        throw new Exception("El turno ya no existe o no pudo eliminarse.");
    }

    mysqli_commit($con);

    echo json_encode([
        'success' => true,
        'message' => 'Turno eliminado correctamente.',
    ]);
} catch (Throwable $e) {
    mysqli_rollback($con);

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}
?>
