<?php
require_once 'config.php';

function smtp_leer_respuesta($socket)
{
    $respuesta = '';
    while ($linea = fgets($socket, 515)) {
        $respuesta .= $linea;
        if (strlen($linea) >= 4 && $linea[3] === ' ') {
            break;
        }
    }

    return $respuesta;
}

function smtp_comando($socket, $comando, $codigos_ok)
{
    fwrite($socket, $comando . "\r\n");
    $respuesta = smtp_leer_respuesta($socket);
    $codigo = (int) substr($respuesta, 0, 3);

    return in_array($codigo, $codigos_ok, true);
}

function enviar_mail_smtp($destino, $nombre_destino, $asunto, $html)
{
    global $mail_host, $mail_port, $mail_username, $mail_password, $mail_from, $mail_from_name;

    if (empty($mail_username) || empty($mail_password) || empty($mail_from)) {
        return [false, 'Falta configurar el correo de Gmail en config.php.'];
    }

    $socket = fsockopen($mail_host, $mail_port, $errno, $errstr, 20);
    if (!$socket) {
        return [false, 'No se pudo conectar al servidor de correo.'];
    }

    stream_set_timeout($socket, 20);
    smtp_leer_respuesta($socket);

    if (
        !smtp_comando($socket, 'EHLO localhost', [250]) ||
        !smtp_comando($socket, 'STARTTLS', [220]) ||
        !stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) ||
        !smtp_comando($socket, 'EHLO localhost', [250]) ||
        !smtp_comando($socket, 'AUTH LOGIN', [334]) ||
        !smtp_comando($socket, base64_encode($mail_username), [334]) ||
        !smtp_comando($socket, base64_encode($mail_password), [235]) ||
        !smtp_comando($socket, 'MAIL FROM:<' . $mail_from . '>', [250]) ||
        !smtp_comando($socket, 'RCPT TO:<' . $destino . '>', [250, 251]) ||
        !smtp_comando($socket, 'DATA', [354])
    ) {
        fclose($socket);
        return [false, 'No se pudo autenticar o preparar el envio de correo.'];
    }

    $from_name = mb_encode_mimeheader($mail_from_name, 'UTF-8');
    $to_name = mb_encode_mimeheader($nombre_destino, 'UTF-8');
    $subject = mb_encode_mimeheader($asunto, 'UTF-8');
    $headers = [
        'From: ' . $from_name . ' <' . $mail_from . '>',
        'To: ' . $to_name . ' <' . $destino . '>',
        'Subject: ' . $subject,
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
    ];

    $mensaje = implode("\r\n", $headers) . "\r\n\r\n" . $html . "\r\n.";
    fwrite($socket, $mensaje . "\r\n");
    $respuesta = smtp_leer_respuesta($socket);
    smtp_comando($socket, 'QUIT', [221]);
    fclose($socket);

    if ((int) substr($respuesta, 0, 3) !== 250) {
        return [false, 'El servidor de correo rechazo el mensaje.'];
    }

    return [true, 'Correo enviado.'];
}

function enviar_codigo_verificacion_cliente($email, $nombre, $codigo)
{
    $html = '
        <div style="font-family:Arial,sans-serif;background:#162426;color:#f9f5d2;padding:24px">
            <div style="max-width:520px;margin:auto;background:#315257;padding:24px;border-radius:8px">
                <h1 style="color:#fcc30c;margin-top:0">Validar correo</h1>
                <p>Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ', usá este código para activar tu acceso de cliente:</p>
                <p style="font-size:30px;font-weight:bold;letter-spacing:6px;color:#fcc30c">' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</p>
                <p>El código vence en 30 minutos.</p>
            </div>
        </div>';

    return enviar_mail_smtp($email, $nombre, 'Codigo de verificacion - La Toxica', $html);
}

function enviar_codigo_recuperacion_cliente($email, $nombre, $codigo)
{
    $html = '
        <div style="font-family:Arial,sans-serif;background:#162426;color:#f9f5d2;padding:24px">
            <div style="max-width:520px;margin:auto;background:#315257;padding:24px;border-radius:8px">
                <h1 style="color:#fcc30c;margin-top:0">Recuperar clave</h1>
                <p>Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . ', usá este código para crear una nueva clave:</p>
                <p style="font-size:30px;font-weight:bold;letter-spacing:6px;color:#fcc30c">' . htmlspecialchars($codigo, ENT_QUOTES, 'UTF-8') . '</p>
                <p>El código vence en 30 minutos. Si no pediste este cambio, podés ignorar este correo.</p>
            </div>
        </div>';

    return enviar_mail_smtp($email, $nombre, 'Recuperar clave - La Toxica', $html);
}
?>
