<?php
$pageTitle = "Recuperar clave";
include 'header.php';
?>
<div class="container-fluid min-vh-100 cliente-login">
    <div class="row min-vh-100">
        <div class="col-12 col-lg-5 d-flex align-items-center justify-content-center p-4">
            <div class="cliente-auth-panel w-100">
                <div class="text-center mb-4">
                    <img src="img/logoLogin.png" alt="logo la toxica" class="cliente-auth-logo">
                    <h1 class="cliente-title mt-4">Recuperar clave</h1>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <form action="enviar_recuperacion_cliente.php" method="POST" data-loading-form="true">
                    <p class="cliente-verificacion-ayuda">
                        Ingresa tu email o telefono. Te enviaremos un codigo al correo registrado.
                    </p>
                    <div class="mb-3">
                        <label for="login" class="form-label">Email o telefono</label>
                        <input type="text" class="form-control" id="login" name="login" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Enviar codigo</button>
                </form>
                <div class="text-center mt-3">
                    <a href="cliente_login.php" class="cliente-link">Volver al ingreso</a>
                </div>
            </div>
        </div>
        <div class="d-none d-lg-block col-lg-7 cliente-login-media"></div>
    </div>
</div>
<?php include 'common_scripts.php'; ?>
<?php include 'cliente_form_utils.php'; ?>
</body>
</html>
