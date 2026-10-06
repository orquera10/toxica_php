<?php
require_once __DIR__ . '/whatsapp_utils.php';

header('Content-Type: text/plain');

if (empty($whatsapp_access_token) || empty($whatsapp_phone_number_id)) {
    echo "Error: faltan WHATSAPP_ACCESS_TOKEN o WHATSAPP_PHONE_NUMBER_ID en config.php.\n";
    exit;
}

$to_number = $_GET['to'] ?? '+543885104530';

echo "Attempting to send a test WhatsApp message to " . $to_number . "...\n";

[$ok, $mensaje, $data] = whatsapp_enviar_plantilla($to_number, 'hello_world', 'en_US');

if ($ok) {
    echo "Message sent successfully!\n";
    echo "WhatsApp message ID: " . ($data['messages'][0]['id'] ?? 'sin id') . "\n";
    echo "Check your WhatsApp on " . $to_number . " for the message.\n";
    exit;
}

echo "Error sending WhatsApp message: " . $mensaje . "\n";
echo "Please ensure:\n";
echo "1. Your Meta WhatsApp test credentials in config.php are correct.\n";
echo "2. The recipient number is added in Meta Developers > WhatsApp > API Setup.\n";
echo "3. The 'to' number (" . $to_number . ") is valid and WhatsApp enabled.\n";

if ($data) {
    echo "Raw response:\n";
    print_r($data);
}
?>
