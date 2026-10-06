<div class="cliente-navbar">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="cliente_reservas.php" class="cliente-brand">
            <img src="img/logoToxica2.png" alt="logo la toxica">
        </a>
        <div class="dropdown">
            <button class="btn cliente-profile-toggle dropdown-toggle" type="button" data-bs-toggle="dropdown"
                aria-expanded="false">
                <i class="fa-solid fa-user"></i>
                <span><?php echo htmlspecialchars($cliente['NOMBRE']); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end cliente-profile-menu">
                <li>
                    <a class="dropdown-item" href="cliente_perfil.php">
                        <i class="fa-solid fa-id-card"></i>
                        Perfil
                    </a>
                </li>
                <li>
                    <hr class="dropdown-divider">
                </li>
                <li>
                    <a class="dropdown-item" href="cerrar_sesion_cliente.php">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Cerrar sesion
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>

<a
    class="cliente-whatsapp-float"
    href="https://wa.me/5493886002759"
    target="_blank"
    rel="noopener"
    aria-label="Contactar por WhatsApp">
    <i class="fab fa-whatsapp"></i>
</a>
