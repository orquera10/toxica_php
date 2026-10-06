<?php
$pageTitle = 'Login de administracion';
$assetBase = '../';
include __DIR__ . '/../header.php';
?>
<div class="container-fluid vh-100">
    <div class="row h-100">
        <div class="col-12 col-md-4 h-100">
            <div class="row justify-content-center h-100 align-items-center contenedorLogin">
                <div class="col-12 formLogin">
                    <form action="../iniciar_sesion.php" method="POST" class="p-4">
                        <div class="d-flex justify-content-center">
                            <img src="../img/logoLogin.png" alt="logo la toxica">
                        </div>
                        <div class="col border-top border-white my-5"></div>

                        <?php include __DIR__ . '/../msjs.php'; ?>

                        <div class="mb-3">
                            <label for="usuario" class="form-label text-white">
                                <i class="fa-solid fa-user text-white"></i> Usuario
                            </label>
                            <input type="text" name="Usuario" class="form-control" id="usuario"
                                placeholder="Nombre de Usuario" required>
                        </div>
                        <div class="mb-3">
                            <label for="clave" class="form-label text-white">
                                <i class="fa-solid fa-unlock text-white"></i> Clave
                            </label>
                            <div class="input-group">
                                <input type="password" name="Clave" class="form-control" id="clave"
                                    placeholder="Clave" required>
                                <button class="btn btn-outline-secondary" type="button" id="toggleClave"
                                    aria-label="Mostrar u ocultar clave">
                                    <i class="fa-solid fa-eye" id="iconoClave"></i>
                                </button>
                            </div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary shadow mt-4">Ingresar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="d-none d-md-block col-12 col-md-8 fondoLogin h-100"></div>
    </div>
</div>
<?php include __DIR__ . '/../common_scripts.php'; ?>
<script>
    document.getElementById('toggleClave').addEventListener('click', function () {
        const claveInput = document.getElementById('clave');
        const icono = document.getElementById('iconoClave');
        const mostrar = claveInput.type === 'password';

        claveInput.type = mostrar ? 'text' : 'password';
        icono.classList.toggle('fa-eye', !mostrar);
        icono.classList.toggle('fa-eye-slash', mostrar);
    });
</script>
</body>
</html>
