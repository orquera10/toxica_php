<?php
require_once __DIR__ . '/cliente_auth.php';

// La portada es el acceso de clientes. Si ya inicio sesion, mostramos
// directamente su panel de reservas.
if (cliente_actual()) {
    header('Location: cliente_reservas.php');
    exit;
}

require __DIR__ . '/cliente_login.php';
