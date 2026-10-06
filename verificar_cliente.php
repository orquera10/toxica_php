<?php
$pageTitle = "Verificar correo";
include 'header.php';

$cliente_id = (int) ($_GET['cliente'] ?? $_POST['cliente_id'] ?? 0);
?>
<div class="container-fluid min-vh-100 cliente-login">
    <div class="row min-vh-100">
        <div class="col-12 col-lg-5 d-flex align-items-center justify-content-center p-4">
            <div class="cliente-auth-panel w-100">
                <div class="text-center mb-4">
                    <img src="img/logoLogin.png" alt="logo la toxica" class="cliente-auth-logo">
                    <h1 class="cliente-title mt-4">Validar correo</h1>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <form action="confirmar_cliente.php" method="POST" data-loading-form="true">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                    <p class="cliente-verificacion-ayuda">
                        Si no ves el correo en tu bandeja de entrada, revisa Spam o Correo no deseado.
                    </p>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Codigo recibido</label>
                        <input type="text" class="form-control text-center cliente-code-input" id="codigo" name="codigo"
                            maxlength="6" inputmode="numeric" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Validar acceso</button>
                </form>
                <form action="reenviar_codigo_cliente.php" method="POST" class="mt-3" data-loading-form="true">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                    <button type="submit" class="btn btn-outline-warning w-100">Reenviar codigo</button>
                </form>
            </div>
        </div>
        <div class="d-none d-lg-block col-lg-7 cliente-login-media"></div>
    </div>
</div>
<?php include 'common_scripts.php'; ?>
<?php include 'cliente_form_utils.php'; ?>
</body>
</html>
