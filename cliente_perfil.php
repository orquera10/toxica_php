<?php
$pageTitle = "Mi perfil";
require_once 'cliente_auth.php';
$cliente = requerir_cliente();
include 'header.php';
include 'cliente_navbar.php';
?>
<main class="container cliente-page py-4">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show text-center" role="alert">
            <?php echo htmlspecialchars($_GET['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show text-center" role="alert">
            <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <section class="col-12 col-lg-5">
            <div class="cliente-section">
                <h1>Mi perfil</h1>
                <div class="cliente-profile-data mt-4">
                    <div>
                        <span>Nombre</span>
                        <strong><?php echo htmlspecialchars($cliente['NOMBRE']); ?></strong>
                    </div>
                    <div>
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars($cliente['MAIL']); ?></strong>
                    </div>
                    <div>
                        <span>Telefono</span>
                        <strong><?php echo htmlspecialchars($cliente['TELEFONO']); ?></strong>
                    </div>
                </div>
            </div>
        </section>

        <section class="col-12 col-lg-7">
            <div class="cliente-section">
                <h2>Cambiar clave</h2>
                <form action="actualizar_clave_cliente.php" method="POST" class="mt-4" id="formCambiarClave" data-loading-form="true">
                    <div class="mb-3">
                        <label for="claveActual" class="form-label">Clave actual</label>
                        <input type="password" class="form-control" id="claveActual" name="clave_actual" required>
                    </div>
                    <div class="mb-3">
                        <label for="claveNueva" class="form-label">Nueva clave</label>
                        <input type="password" class="form-control" id="claveNueva" name="clave_nueva" minlength="6" required>
                    </div>
                    <div class="mb-4">
                        <label for="repetirClaveNueva" class="form-label">Repetir nueva clave</label>
                        <input type="password" class="form-control" id="repetirClaveNueva" name="repetir_clave_nueva" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa-solid fa-key me-2"></i>
                        Guardar clave
                    </button>
                </form>
            </div>
        </section>
    </div>
</main>
<?php include 'common_scripts.php'; ?>
<?php include 'cliente_form_utils.php'; ?>
<script>
    const formCambiarClave = document.getElementById('formCambiarClave');

    if (formCambiarClave) {
        const claveNueva = document.getElementById('claveNueva');
        const repetirClaveNueva = document.getElementById('repetirClaveNueva');

        function validarCambioClave() {
            if (repetirClaveNueva.value === '' || claveNueva.value === repetirClaveNueva.value) {
                repetirClaveNueva.setCustomValidity('');
                return true;
            }

            repetirClaveNueva.setCustomValidity('Las claves no coinciden');
            return false;
        }

        claveNueva.addEventListener('input', validarCambioClave);
        repetirClaveNueva.addEventListener('input', validarCambioClave);

        formCambiarClave.addEventListener('submit', function (event) {
            if (!validarCambioClave()) {
                event.preventDefault();
                repetirClaveNueva.reportValidity();
            }
        });
    }

    setTimeout(function () {
        $(".alert").slideUp(300);
    }, 3500);
</script>
</body>
</html>
