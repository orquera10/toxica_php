<?php
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

date_default_timezone_set('America/Argentina/Buenos_Aires');
$usuario  = getenv('DB_USER') ?: "root";
$password = getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : "";
$servidor = getenv('DB_HOST') ?: "localhost";
$basededatos = getenv('DB_NAME') ?: "la_toxica";

// Configuracion para validar clientes por correo.
// Completar con tu cuenta de Google y una clave de aplicacion.
$mail_host = "smtp.gmail.com";
$mail_port = 587;
$mail_username = getenv('MAIL_USERNAME') ?: '';
$mail_password = getenv('MAIL_PASSWORD') ?: '';
$mail_from = getenv('MAIL_FROM') ?: $mail_username;
$mail_from_name = "La Toxica";

// Configuracion Mercado Pago Checkout Pro.
// Completar con el Access Token de Mercado Pago y la URL publica del sistema.
$mercadopago_access_token = getenv('MERCADOPAGO_ACCESS_TOKEN') ?: '';
$mercadopago_public_url = getenv('APP_PUBLIC_URL') ?: '';
$mercadopago_statement_descriptor = "LA TOXICA";
// Medios habilitados en Checkout Pro. Se pueden combinar o dejar solo uno.
// Opciones: account_money, credit_card, debit_card, prepaid_card, ticket,
// bank_transfer, atm, digital_currency. Mercado Pago siempre ofrece
// account_money (saldo en cuenta), aunque se quite de esta lista.
$mercadopago_medios_pago_habilitados = [
    'account_money',
];

// Configuracion WhatsApp Cloud API de Meta.
// Completar con los datos de Meta Developers > WhatsApp > API Setup.
$whatsapp_api_version = getenv('WHATSAPP_API_VERSION') ?: "v23.0";
$whatsapp_access_token = getenv('WHATSAPP_ACCESS_TOKEN') ?: '';
$whatsapp_phone_number_id = getenv('WHATSAPP_PHONE_NUMBER_ID') ?: '';
$whatsapp_template_confirmacion = getenv('WHATSAPP_TEMPLATE_CONFIRMACION') ?: "reserva_confirmada"; // Ejemplo: reserva_confirmada
$whatsapp_template_language = getenv('WHATSAPP_TEMPLATE_LANGUAGE') ?: "es_AR";
$whatsapp_webhook_verify_token = getenv('WHATSAPP_WEBHOOK_VERIFY_TOKEN') ?: '';

$con = mysqli_connect($servidor, $usuario, $password) or die("No se ha podido conectar al Servidor");
$db = mysqli_select_db($con, $basededatos) or die("Upps! Error en conectar a la Base de Datos");

// PHP trabaja con la hora de Buenos Aires. Aplicamos la misma zona a MySQL
// para que NOW() compare correctamente los vencimientos de las reservas.
if (!mysqli_query($con, "SET time_zone = '-03:00'")) {
    die("No se pudo configurar la zona horaria de MySQL");
}

$api_key = getenv('API_KEY') ?: '';
$admin_api_key = getenv('ADMIN_API_KEY') ?: '';
$wp_bot_send_endpoint = getenv('WP_BOT_SEND_ENDPOINT') ?: "https://bot-wp.darioapp.online/clients/toxica_negocio/send";
$wp_bot_birthday_endpoint = getenv('WP_BOT_BIRTHDAY_ENDPOINT') ?: "https://bot-wp.darioapp.online/clients/toxica_negocio/birthday-invitation";
?>
