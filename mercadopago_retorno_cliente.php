<?php
require_once 'cliente_auth.php';
require_once 'mercadopago_utils.php';

$cliente = requerir_cliente();
$ticket_id = (int) ($_GET['ticket'] ?? 0);
$resultado = $_GET['resultado'] ?? '';
$payment_id = $_GET['payment_id'] ?? ($_GET['collection_id'] ?? '');
$status = $_GET['status'] ?? ($_GET['collection_status'] ?? '');

if ($ticket_id <= 0) {
    header("Location: cliente_reservas.php?error=" . urlencode("No se pudo identificar la reserva."));
    exit;
}

$cliente_id = (int) $cliente['_id'];
$stmt = $con->prepare("SELECT _id FROM ticket WHERE _id = ? AND id_CLIENTE = ? LIMIT 1");
$stmt->bind_param("ii", $ticket_id, $cliente_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    header("Location: cliente_reservas.php?error=" . urlencode("No se encontro la reserva."));
    exit;
}

if ($payment_id !== '') {
    [$ok, $mensaje, $pago] = consultar_pago_mercadopago($payment_id);
    if ($ok) {
        $status = $pago['status'] ?? $status;
        aplicar_pago_senia($ticket_id, $payment_id, $status);
    }
}

if ($status === 'approved') {
    $stmt = $con->prepare("SELECT ESTADO_RESERVA FROM ticket WHERE _id = ? AND id_CLIENTE = ? LIMIT 1");
    $stmt->bind_param("ii", $ticket_id, $cliente_id);
    $stmt->execute();
    $estado_actual = $stmt->get_result()->fetch_assoc()['ESTADO_RESERVA'] ?? '';
    $stmt->close();

    if ($estado_actual === 'confirmada') {
        header("Location: cliente_reservas.php?e=" . urlencode("Reserva confirmada. Recibimos la seña correctamente."));
        exit;
    }

    header("Location: cliente_reservas.php?error=" . urlencode("La reserva no pudo confirmarse. Si el pago fue debitado, comunicate con el local."));
    exit;
}

if ($resultado === 'failure' || $status === 'rejected' || $status === 'cancelled') {
    cancelar_reserva_pendiente($ticket_id);
    header("Location: cliente_reservas.php?error=" . urlencode("El pago no fue aprobado. La reserva no quedo confirmada."));
    exit;
}

header("Location: cliente_reservas.php?success=" . urlencode("Tu pago esta pendiente. Te avisaremos cuando Mercado Pago lo confirme."));
exit;
?>
