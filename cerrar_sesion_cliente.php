<?php
require_once 'cliente_auth.php';

cerrar_sesion_cliente();
header("Location: index.php");
exit;
?>
