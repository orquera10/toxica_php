<?php
require_once 'cliente_auth.php';
require_once 'mercadopago_utils.php';

$cliente = requerir_cliente();
cancelar_reservas_pendientes_vencidas($con);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_reservas.php");
    exit;
}

$ticket_id = (int) ($_POST['ticket_id'] ?? 0);
$cliente_id = (int) $cliente['_id'];

if ($ticket_id <= 0) {
    header("Location: cliente_reservas.php?error=" . urlencode("No se pudo identificar la reserva."));
    exit;
}

$stmt = $con->prepare("SELECT tk._id, tk.FECHA, tk.MP_SENIA, tk.ESTADO_RESERVA, tk.PENDIENTE_EXPIRA, t.HORA_INICIO, t.HORA_FIN, c.NOMBRE AS cancha
    FROM ticket tk
    INNER JOIN turnos t ON t._id = tk.id_TURNO
    INNER JOIN canchas c ON c._id = t.id_CANCHA
    WHERE tk._id = ? AND tk.id_CLIENTE = ? AND tk.ESTADO_RESERVA = 'pendiente_pago'
    LIMIT 1");
$stmt->bind_param("ii", $ticket_id, $cliente_id);
$stmt->execute();
$ticket = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$ticket) {
    header("Location: cliente_reservas.php?error=" . urlencode("La reserva no esta pendiente de seña."));
    exit;
}

if (!empty($ticket['PENDIENTE_EXPIRA']) && strtotime($ticket['PENDIENTE_EXPIRA']) <= time()) {
    cancelar_reserva_pendiente($ticket_id);
    header("Location: cliente_reservas.php?error=" . urlencode("La reserva pendiente vencio. Volve a elegir el horario."));
    exit;
}
$descripcion = "Seña reserva " . $ticket['cancha'] . " " . $ticket['FECHA'] . " " . $ticket['HORA_INICIO'] . " a " . $ticket['HORA_FIN'];
[$mp_ok, $mp_mensaje, $preferencia] = crear_preferencia_senia($ticket_id, $cliente, $descripcion, (float) $ticket['MP_SENIA'], $ticket['PENDIENTE_EXPIRA'] ?? null);

if (!$mp_ok || empty($preferencia['init_point'])) {
    header("Location: cliente_reservas.php?error=" . urlencode($mp_mensaje ?: "No se pudo iniciar el pago de la seña."));
    exit;
}

$preference_id = $preferencia['id'] ?? '';
$stmt = $con->prepare("UPDATE ticket SET MP_PREFERENCE_ID = ?, MP_STATUS = NULL WHERE _id = ?");
$stmt->bind_param("si", $preference_id, $ticket_id);
$stmt->execute();
$stmt->close();

header("Location: " . $preferencia['init_point']);
exit;
?>
