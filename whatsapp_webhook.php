<?php
$whatsapp_webhook_verify_token = getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '';
    $token = $_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '';
    $challenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '';

    if ($whatsapp_webhook_verify_token !== '' && $mode === 'subscribe' && hash_equals($whatsapp_webhook_verify_token, $token)) {
        header('Content-Type: text/plain');
        echo $challenge;
        exit;
    }

    http_response_code(403);
    echo 'Token de verificacion invalido.';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $log_dir = __DIR__ . '/logs';

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $body . PHP_EOL;
    file_put_contents($log_dir . '/whatsapp_webhook.log', $line, FILE_APPEND);

    http_response_code(200);
    echo 'EVENT_RECEIVED';
    exit;
}

http_response_code(405);
echo 'Metodo no permitido.';
?>
