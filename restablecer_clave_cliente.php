<?php
$pageTitle = "Nueva clave";
include 'header.php';

$cliente_id = (int) ($_GET['cliente'] ?? $_POST['cliente_id'] ?? 0);
?>
<div class="container-fluid min-vh-100 cliente-login">
    <div class="row min-vh-100">
        <div class="col-12 col-lg-5 d-flex align-items-center justify-content-center p-4">
            <div class="cliente-auth-panel w-100">
                <div class="text-center mb-4">
                    <img src="img/logoLogin.png" alt="logo la toxica" class="cliente-auth-logo">
                    <h1 class="cliente-title mt-4">Nueva clave</h1>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger text-center"><?php echo htmlspecialchars($_GET['error']); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success text-center"><?php echo htmlspecialchars($_GET['success']); ?></div>
                <?php endif; ?>

                <form action="actualizar_recuperacion_cliente.php" method="POST" id="formRecuperarClave" data-loading-form="true">
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_id; ?>">
                    <p class="cliente-verificacion-ayuda">
                        Si no ves el correo, revisa Spam o Correo no deseado.
                    </p>
                    <div class="mb-3">
                        <label for="codigo" class="form-label">Codigo recibido</label>
                        <input type="text" class="form-control text-center cliente-code-input" id="codigo" name="codigo"
                            maxlength="6" inputmode="numeric" required>
                    </div>
                    <div class="mb-3">
                        <label for="claveNueva" class="form-label">Nueva clave</label>
                        <input type="password" class="form-control" id="claveNueva" name="clave_nueva" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <label for="repetirClaveNueva" class="form-label">Repetir nueva clave</label>
                        <input type="password" class="form-control" id="repetirClaveNueva" name="repetir_clave_nueva" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Guardar nueva clave</button>
                </form>
                <form action="reenviar_recuperacion_cliente.php" method="POST" class="mt-3" data-loading-form="true">
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
<script>
    const formRecuperarClave = document.getElementById('formRecuperarClave');

    if (formRecuperarClave) {
        const claveNueva = document.getElementById('claveNueva');
        const repetirClaveNueva = document.getElementById('repetirClaveNueva');

        function validarRecuperacionClave() {
            if (repetirClaveNueva.value === '' || claveNueva.value === repetirClaveNueva.value) {
                repetirClaveNueva.setCustomValidity('');
                return true;
            }

            repetirClaveNueva.setCustomValidity('Las claves no coinciden');
            return false;
        }

        claveNueva.addEventListener('input', validarRecuperacionClave);
        repetirClaveNueva.addEventListener('input', validarRecuperacionClave);

        formRecuperarClave.addEventListener('submit', function (event) {
            if (!validarRecuperacionClave()) {
                event.preventDefault();
                repetirClaveNueva.reportValidity();
            }
        });
    }
</script>
</body>
</html>
