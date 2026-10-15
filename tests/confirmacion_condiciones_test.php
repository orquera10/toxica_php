<?php
// Exercise the real confirmation formatter without a database, email or WhatsApp calls.
$source = file_get_contents(__DIR__ . '/../mercadopago_utils.php');
$start = strpos($source, 'function enviar_confirmacion_wp_bot(');
$end = strpos($source, 'function crear_preferencia_senia(', $start);
eval(substr($source, $start, $end - $start));
$rules = file_get_contents(__DIR__ . '/../reglas_reservas.php');
$start = strpos($rules, 'function terminos_reserva_cumpleanios(');
$end = strpos($rules, 'function fecha_operativa_reserva(', $start);
eval(substr($rules, $start, $end - $start));
define('CANCHA_CUMPLE_ID', 4);
define('CANCHA_1_ID', 1);
function max_dias_anticipacion_reserva($id) { return 30; }
function reservar_confirmacion_wp($id) { global $locked; if ($locked) return false; $locked = true; return true; }
function liberar_confirmacion_wp($id, $error) { global $locked; $locked = false; }
function registrar_error_confirmacion_wp($id, $error) { throw new RuntimeException($error); }
function nombre_cancha_cliente($id, $name) { return $name; }
function enviar_alertas_reserva_confirmada_hoy($data) { return []; }
function wp_bot_normalizar_telefono($phone) { return $phone; }
function enviar_mail_confirmacion_reserva($data) { return [true, '']; }
function wp_bot_enviar_mensaje($phone, $message) { global $messages; $messages[] = $message; return [true, '']; }
function wp_bot_iniciar_invitacion_cumpleanios($phone, $data) { return [true, '']; }
class ConfirmationFakeConnection {
    function prepare($sql) { return $this; }
    function bind_param($type, &$id) {}
    function execute() {}
    function get_result() { return $this; }
    function fetch_assoc() { global $court; return ['cancha_id' => $court, 'cancha_nombre' => 'Cancha', 'TELEFONO' => '5493881234567', 'MP_SENIA' => 90, 'TOTAL' => 210, 'FECHA' => '24-10-2026', 'HORA_INICIO' => '17:00', 'HORA_FIN' => '20:00']; }
    function close() {}
}
$con = new ConfirmationFakeConnection();
$api_key = 'test-only';
$wp_bot_send_endpoint = 'test-only';
foreach ([1, CANCHA_CUMPLE_ID] as $court) {
    $locked = false;
    $messages = [];
    enviar_confirmacion_wp_bot(123);
    if (count($messages) !== 1) throw new RuntimeException('Missing confirmation');
    $message = $messages[0];
    if (strpos($message, 'quedo confirmada') > strpos($message, 'Terminos y condiciones')) throw new RuntimeException('Terms must follow confirmation');
    foreach (terminos_reserva($court) as $term) {
        if (strpos($message, $term) === false) throw new RuntimeException('Missing court condition');
    }
    enviar_confirmacion_wp_bot(123);
    if (count($messages) !== 1) throw new RuntimeException('Duplicate notification');
}
echo "OK: conditions follow confirmation for football and birthdays, without duplicate sends.\n";
