<?php
// Conservamos la URL anterior por compatibilidad, pero llevamos al cliente
// a la nueva portada canonica y mantenemos sus mensajes de error/exito.
if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    $query = $_SERVER['QUERY_STRING'] ?? '';
    header('Location: index.php' . ($query !== '' ? '?' . $query : ''));
    exit;
}

$pageTitle = "Clientes";
include 'header.php';
?>
<div class="container-fluid min-vh-100 cliente-login">
    <div class="row min-vh-100">
        <div class="col-12 col-lg-5 d-flex align-items-center justify-content-center p-4">
            <div class="cliente-auth-panel w-100">
                <div class="text-center mb-4">
                    <img src="img/logoLogin.png" alt="logo la toxica" class="cliente-auth-logo">
                    <h1 class="cliente-title mt-4">Turnos para clientes</h1>
                </div>

                <?php include 'msjs.php'; ?>

                <ul class="nav nav-tabs cliente-tabs" id="clienteAuthTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#loginCliente"
                            type="button" role="tab">Ingresar</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="registro-tab" data-bs-toggle="tab" data-bs-target="#registroCliente"
                            type="button" role="tab">Registrarme</button>
                    </li>
                </ul>

                <div class="tab-content pt-4">
                    <div class="tab-pane fade show active" id="loginCliente" role="tabpanel">
                        <form action="iniciar_sesion_cliente.php" method="POST" data-loading-form="true">
                            <div class="mb-3">
                                <label for="login" class="form-label">Email o telefono</label>
                                <input type="text" class="form-control" id="login" name="login" required>
                            </div>
                            <div class="mb-3">
                                <label for="claveCliente" class="form-label">Clave</label>
                                <input type="password" class="form-control" id="claveCliente" name="clave" required>
                            </div>
                            <div class="text-end mb-3">
                                <a href="recuperar_clave_cliente.php" class="cliente-link">Olvide mi clave</a>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="registroCliente" role="tabpanel">
                        <form action="registrar_cliente.php" method="POST" data-loading-form="true">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Telefono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" required>
                            </div>
                            <div class="mb-3">
                                <label for="claveRegistro" class="form-label">Clave</label>
                                <input type="password" class="form-control" id="claveRegistro" name="clave" minlength="6" required>
                            </div>
                            <div class="mb-3">
                                <label for="repetirClaveRegistro" class="form-label">Repetir clave</label>
                                <input type="password" class="form-control" id="repetirClaveRegistro" name="repetir_clave" minlength="6" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Registrarme</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
        <div class="d-none d-lg-block col-lg-7 cliente-login-media"></div>
    </div>
</div>
<?php include 'common_scripts.php'; ?>
<?php include 'cliente_form_utils.php'; ?>
<script>
    const registroForm = document.querySelector('form[action="registrar_cliente.php"]');

    if (registroForm) {
        const clave = document.getElementById('claveRegistro');
        const repetirClave = document.getElementById('repetirClaveRegistro');

        function validarClavesRegistro() {
            if (repetirClave.value === '' || clave.value === repetirClave.value) {
                repetirClave.setCustomValidity('');
                return true;
            }

            repetirClave.setCustomValidity('Las claves no coinciden');
            return false;
        }

        clave.addEventListener('input', validarClavesRegistro);
        repetirClave.addEventListener('input', validarClavesRegistro);

        registroForm.addEventListener('submit', function (event) {
            if (!validarClavesRegistro()) {
                event.preventDefault();
                repetirClave.reportValidity();
            }
        });
    }
</script>
</body>
</html>
