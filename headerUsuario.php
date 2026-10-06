<?php
// Verificar si la cookie de usuario está establecida
if (isset($_COOKIE['USUARIO']) && isset($_COOKIE['TIPO'])) {
    // Separar el nombre de usuario y la firma de la cookie
    $parts_usuario = explode('|', $_COOKIE['USUARIO']);
    $usuario = $parts_usuario[0];
    $firma_usuario = $parts_usuario[1];

    // Separar el tipo de usuario y la firma de la cookie
    $parts_tipo = explode('|', $_COOKIE['TIPO']);
    $tipo_usuario = $parts_tipo[0];
    $firma_tipo = $parts_tipo[1];

    // Verificar la firma de la cookie de usuario
    $firma_verificada_usuario = hash_hmac('sha256', $usuario, 'clave_secreta');
    // Verificar la firma de la cookie de tipo
    $firma_verificada_tipo = hash_hmac('sha256', $tipo_usuario, 'clave_secreta');


} else {
    // Si la cookie no está establecida, redirigir al usuario a la página de inicio de sesión
    header("Location: admin/");
    exit(); // Asegurar que el script se detenga después de redirigir
}
?>
<header class="p-3 border-bottom" style="border-bottom: none !important">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-end">
            <p class="m-0 me-3 textUser">
                <?php // Verificar si las firmas coinciden
                if ($firma_usuario === $firma_verificada_usuario && $firma_tipo === $firma_verificada_tipo) {
                    // Verificar si el tipo de usuario es admin
                    if ($tipo_usuario === 'admin' || $tipo_usuario === 'user') {
                        echo "Bienvenido, $usuario ";
                    } else {
                        echo "Acceso denegado. Debes ser administrador o empleado para acceder a esta página.";
                        echo "<a href='cerrar_sesion.php' class='mx-2'>Cerrar sesión</a>";
                        exit(); // Detener la ejecución del script si el usuario no es admin
                    }
                } else {
                    // Si las firmas no coinciden, se considera que la cookie ha sido modificada
                    echo "La cookie ha sido modificada. Por razones de seguridad, se cerrará la sesión.";
                    header("Location: cerrar_sesion.php");
                    exit(); // Asegurar que el script se detenga después de redirigir
                } ?>
            </p>
            <div class="dropdown text-end">

                <a href="#" class="d-block link-body-emphasis text-decoration-none dropdown-toggle"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fa-solid fa-user iconProfile"></i>
                </a>
                <ul class="dropdown-menu text-small" style="">
                    <li><a class="dropdown-item" href="#">Perfil</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="cerrar_sesion.php">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>
