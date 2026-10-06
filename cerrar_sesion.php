<?php
// Iniciar la sesión si no está iniciada
session_start();

// Destruir la sesión
session_destroy();

// Eliminar la cookie de usuario manualmente
setcookie("USUARIO", "", time() - 32400, '/', '', true, true); // 9 horas = 9 * 3600 segundos

// Eliminar la cookie de tipo de usuario manualmente
setcookie("TIPO", "", time() - 32400, '/', '', true, true); // 9 horas = 9 * 3600 segundos

// Redirigir al usuario a la página de inicio
header("Location: admin/");
exit(); // Asegurar que el script se detenga después de redirigir
?>
