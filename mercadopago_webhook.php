<?php
require_once 'mercadopago_utils.php';

try {
    $type = (string) ($_GET['type'] ?? $_GET['topic'] ?? '');
    $payment_id = (string) ($_GET['data_id'] ?? $_GET['id'] ?? '');
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);

    if (is_array($payload)) {
        $type = (string) ($payload['type'] ?? $type);
        $payment_id = (string) ($payload['data']['id'] ?? $payment_id);
    }

    if ($type !== 'payment') {
        http_response_code(200);
        echo 'IGNORED';
        exit;
    }
    if ($payment_id === '') {
        error_log('Webhook Mercado Pago sin ID de pago.');
        http_response_code(400);
        echo 'MISSING_PAYMENT_ID';
        exit;
    }

    [$ok, $mensaje, $pago] = consultar_pago_mercadopago($payment_id);
    if (!$ok) {
        error_log("Webhook Mercado Pago {$payment_id}: {$mensaje}");
        http_response_code(502);
        echo 'PAYMENT_LOOKUP_FAILED';
        exit;
    }

    $ticket_id = (int) ($pago['external_reference'] ?? 0);
    if ($ticket_id <= 0) {
        error_log("Webhook Mercado Pago {$payment_id}: external_reference invalida.");
        http_response_code(422);
        echo 'INVALID_EXTERNAL_REFERENCE';
        exit;
    }

    $procesado = aplicar_pago_senia($ticket_id, $payment_id, (string) ($pago['status'] ?? ''), $pago);
    if (($pago['status'] ?? '') === 'approved') {
        $stmt = $con->prepare('SELECT ESTADO_RESERVA FROM ticket WHERE _id = ? LIMIT 1');
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        $estado = $stmt->get_result()->fetch_assoc()['ESTADO_RESERVA'] ?? '';
        $stmt->close();
        if ($estado !== 'confirmada') {
            error_log("Webhook Mercado Pago {$payment_id}: ticket {$ticket_id} quedo en estado {$estado}.");
            http_response_code(500);
            echo 'RESERVATION_NOT_CONFIRMED';
            exit;
        }
        if (!$procesado) {
            error_log("Webhook Mercado Pago {$payment_id}: no se pudo notificar al cliente del ticket {$ticket_id}.");
            http_response_code(502);
            echo 'CUSTOMER_NOTIFICATION_FAILED';
            exit;
        }
    }

    http_response_code(200);
    echo 'OK';
} catch (Throwable $error) {
    error_log('Error procesando webhook Mercado Pago: ' . $error->getMessage());
    http_response_code(500);
    echo 'INTERNAL_ERROR';
}
?>
