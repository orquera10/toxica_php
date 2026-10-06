<?php
$tipo_usuario = null;

if (isset($_COOKIE['TIPO'])) {
    list($valor, $firma) = explode('|', $_COOKIE['TIPO']);
    $firma_valida = hash_hmac('sha256', $valor, 'clave_secreta');

    if (hash_equals($firma, $firma_valida)) {
        $tipo_usuario = $valor;
    }
}
?>


<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container">
        <a class="navbar-brand" href="page_turnos.php"><img src="img/logoToxica2.png" alt="logo la toxica"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor"
                class="bi bi-list iconMenu" viewBox="0 0 16 16">
                <path fill-rule="evenodd"
                    d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
            </svg>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link active m-2 m-lg-0 " aria-current="page" href="page_turnos.php">Turnos</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle m-2 m-lg-0" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Administración
                    </a>
                    <ul class="dropdown-menu dropMenu">
                        <li><a class="dropdown-item" href="clientes_page.php">Clientes</a></li>
                        <li><a class="dropdown-item" href="productos_page.php">Productos</a></li>
                        <li><a class="dropdown-item" href="canchas_page.php">Canchas</a></li>
                        <li><a class="dropdown-item" href="turnos_configuracion_page.php">Turnos</a></li>
                        <li><a class="dropdown-item" href="personal_page.php">Personal</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle m-2 m-lg-0" href="#" role="button" data-bs-toggle="dropdown"
                        aria-expanded="false">
                        Informes
                    </a>
                    <ul class="dropdown-menu dropMenu">
                        <li><a class="dropdown-item" href="informe_diario_page.php">Ventas Diario</a></li>
                        <li><a class="dropdown-item" href="informe_ventas_mensual.php">Ventas Mensual</a></li>
                        <li><a class="dropdown-item" href="informe_stock_page.php">Stock Diario</a></li>
                        <li><a class="dropdown-item" href="informe_mensual_page.php">Stock Mensual</a></li>
                        <?php if ($tipo_usuario === 'admin'): ?>
                            <li><a class="dropdown-item" href="informe_pagos_personal.php">Pagos Personal</a></li>
                        <?php endif; ?>
                    </ul>
                </li>

            </ul>

        </div>
    </div>
</nav>
