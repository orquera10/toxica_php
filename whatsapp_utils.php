<?php
require_once __DIR__ . '/config.php';

function whatsapp_normalizar_telefono($telefono)
{
    $numero = preg_replace('/[^0-9]/', '', (string) $telefono);

    if ($numero === '') {
        return '';
    }

    if (strpos($numero, '54') === 0) {
        return $numero;
    }

    if (strpos($numero, '0') === 0) {
        $numero = ltrim($numero, '0');
    }

    return '54' . $numero;
}

function whatsapp_api_request($payload)
{
    global $whatsapp_api_version, $whatsapp_access_token, $whatsapp_phone_number_id;

    if (empty($whatsapp_access_token) || empty($whatsapp_phone_number_id)) {
        return [false, 'Falta configurar WHATSAPP_ACCESS_TOKEN o WHATSAPP_PHONE_NUMBER_ID en config.php.', null];
    }

    $url = 'https://graph.facebook.com/' . $whatsapp_api_version . '/' . $whatsapp_phone_number_id . '/messages';
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $whatsapp_access_token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_TIMEOUT, 25);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [false, 'Error de conexion con WhatsApp API: ' . $error, null];
    }

    $data = json_decode($response, true);
    if ($status < 200 || $status >= 300) {
        $mensaje = $data['error']['message'] ?? 'WhatsApp API rechazo la solicitud.';
        return [false, $mensaje, $data];
    }

    return [true, 'Mensaje enviado.', $data];
}

function whatsapp_enviar_texto($telefono, $mensaje)
{
    $telefono = whatsapp_normalizar_telefono($telefono);

    if ($telefono === '') {
        return [false, 'Telefono de destino invalido.', null];
    }

    return whatsapp_api_request([
        'messaging_product' => 'whatsapp',
        'to' => $telefono,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $mensaje,
        ],
    ]);
}

function whatsapp_enviar_plantilla($telefono, $nombre_plantilla, $idioma = 'es_AR', $parametros = [])
{
    $telefono = whatsapp_normalizar_telefono($telefono);

    if ($telefono === '') {
        return [false, 'Telefono de destino invalido.', null];
    }

    $template = [
        'name' => $nombre_plantilla,
        'language' => [
            'code' => $idioma,
        ],
    ];

    if (!empty($parametros)) {
        $template['components'] = [
            [
                'type' => 'body',
                'parameters' => array_map(function ($valor) {
                    return [
                        'type' => 'text',
                        'text' => (string) $valor,
                    ];
                }, $parametros),
            ],
        ];
    }

    return whatsapp_api_request([
        'messaging_product' => 'whatsapp',
        'to' => $telefono,
        'type' => 'template',
        'template' => $template,
    ]);
}
?>
