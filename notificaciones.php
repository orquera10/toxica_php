<?php
require_once __DIR__ . '/whatsapp_utils.php';

function enviar_whatsapp_confirmacion($telefono_cliente, $datos_reserva)
{
    global $whatsapp_template_confirmacion, $whatsapp_template_language;

    $cancha = $datos_reserva['cancha_nombre'] ?? '';
    $fecha = $datos_reserva['fecha'] ?? '';
    $hora_inicio = $datos_reserva['hora_inicio'] ?? '';
    $hora_fin = $datos_reserva['hora_fin'] ?? '';
    $monto_senia = (float) ($datos_reserva['monto_senia'] ?? 0);
    $horario = trim($hora_inicio . ' a ' . $hora_fin);
    $senia = '$' . number_format($monto_senia, 2, ',', '.');

    if (!empty($whatsapp_template_confirmacion)) {
        return whatsapp_enviar_plantilla($telefono_cliente, $whatsapp_template_confirmacion, $whatsapp_template_language, [
            $cancha,
            $fecha,
            $horario,
            $senia,
        ]);
    }

    $mensaje = "Hola! Tu reserva en La Toxica esta confirmada. Cancha: {$cancha}, fecha: {$fecha}, horario: {$horario}, sena: {$senia}. Te esperamos!";

    return whatsapp_enviar_texto($telefono_cliente, $mensaje);
}
?>
