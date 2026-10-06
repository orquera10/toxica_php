<?php
declare(strict_types=1);

// Este archivo contiene una tarea interna. No debe poder ejecutarse desde la web.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/mercadopago_utils.php';

try {
    $conciliacion = reconciliar_reservas_pendientes_mercadopago(100);
    $confirmaciones = reintentar_confirmaciones_wp_pendientes(100);
    $canceladas = count($conciliacion['errores']) === 0
        ? cancelar_reservas_pendientes_vencidas($con)
        : 0;

    fwrite(
        STDOUT,
        sprintf(
            "[%s] Reservas revisadas: %d; confirmadas por conciliacion: %d; confirmaciones WP reintentadas: %d; enviadas: %d; canceladas: %d; errores: %d%s",
            date('Y-m-d H:i:s'),
            $conciliacion['revisadas'],
            $conciliacion['confirmadas'],
            $confirmaciones['revisadas'],
            $confirmaciones['enviadas'],
            $canceladas,
            count($conciliacion['errores']) + count($confirmaciones['errores']),
            PHP_EOL
        )
    );

    foreach ($conciliacion['errores'] as $error_conciliacion) {
        fwrite(STDERR, $error_conciliacion . PHP_EOL);
    }
    foreach ($confirmaciones['errores'] as $error_confirmacion) {
        fwrite(STDERR, $error_confirmacion . PHP_EOL);
    }

    if (count($conciliacion['errores']) > 0) {
        fwrite(STDERR, "No se cancelaron reservas porque la conciliacion no pudo completarse." . PHP_EOL);
        exit(1);
    }
    if (count($confirmaciones['errores']) > 0) {
        exit(1);
    }
} catch (Throwable $error) {
    fwrite(
        STDERR,
        sprintf(
            "[%s] Error al cancelar reservas pendientes: %s%s",
            date('Y-m-d H:i:s'),
            $error->getMessage(),
            PHP_EOL
        )
    );
    exit(1);
}
