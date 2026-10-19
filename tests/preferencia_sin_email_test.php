<?php
$source = file_get_contents(__DIR__ . '/../mercadopago_utils.php');
$start = strpos($source, 'function crear_preferencia_senia(');
$end = strpos($source, 'function consultar_pago_mercadopago(', $start);
eval(substr($source, $start, $end - $start));
define('RESERVA_PENDIENTE_MINUTOS', 10);
$mercadopago_statement_descriptor = 'TEST';
$mercadopago_medios_pago_habilitados = ['account_money'];
function app_public_url() { return 'https://example.com'; }
function mercadopago_request($method, $url, $payload) {
    global $expected;
    if ($method !== 'POST' || $url !== '/checkout/preferences') throw new RuntimeException('Wrong payment endpoint');
    if (($payload['payer']['email'] ?? '') !== $expected) throw new RuntimeException('Email must be optional without inventing an address');
    if ($expected === '' && array_key_exists('email', $payload['payer'])) throw new RuntimeException('Empty email must be omitted');
    return [true, '', []];
}
foreach (['', 'cliente@example.com'] as $expected) crear_preferencia_senia(1, ['NOMBRE'=>'Maria Perez','MAIL'=>$expected], 'Turno', 30);
echo "OK: checkout payload with and without registered email, no network calls.\n";
