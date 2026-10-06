<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['csrf_precios'] = $_SESSION['csrf_precios'] ?? bin2hex(random_bytes(32));
// Incluir archivos necesarios
$pageTitle = "Canchas";
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
require_once 'horarios_canchas.php';
require_once 'reglas_reservas.php';
include 'config.php'; // Suponiendo que aquÃ­ se encuentra la configuraciÃ³n de la conexiÃ³n a la base de datos

// Variable para almacenar la consulta SQL
$sql = "SELECT * FROM canchas WHERE _id <> 9";

// Variable para almacenar el resultado de la consulta SQL
$result = mysqli_query($con, $sql);

// Variable para almacenar los resultados de la bÃºsqueda
$canchas = [];

// Verificar si se obtuvieron resultados
if (mysqli_num_rows($result) > 0) {
    // Almacenar las canchas en un arreglo
    while ($row = mysqli_fetch_assoc($result)) {
        $canchas[] = $row;
    }
}
?>

<?php $configuracion_plazos = configuracion_plazos_reserva($con); ?>

<div class="container">
    <section class="configuracion-reservas mt-5" aria-labelledby="tituloConfiguracionReservas">
        <div class="configuracion-reservas-encabezado">
            <div class="configuracion-reservas-icono" aria-hidden="true">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div>
                <span class="configuracion-reservas-etiqueta">Reservas online</span>
                <h2 id="tituloConfiguracionReservas">Anticipación de reservas</h2>
                <p>Definí hasta cuántos días hacia adelante pueden reservar los clientes.</p>
            </div>
        </div>
        <form id="formConfiguracionReservas" class="row g-3 align-items-end">
                <div class="col-lg-5 col-md-6">
                    <label for="diasAnticipacionTurnos" class="configuracion-reservas-label">
                        <span class="configuracion-reservas-label-icono" aria-hidden="true"><i class="fa-solid fa-futbol"></i></span>
                        <span>Reservas de canchas<small>Fútbol 5, Fútbol 6 y Fútbol 7/8</small></span>
                    </label>
                    <div class="input-group configuracion-reservas-input">
                        <input type="number" class="form-control" id="diasAnticipacionTurnos"
                            name="dias_anticipacion_turnos" min="1" max="3650" step="1" required
                            value="<?php echo (int) $configuracion_plazos['turnos']; ?>">
                        <span class="input-group-text">días</span>
                    </div>
                </div>
                <div class="col-lg-5 col-md-6">
                    <label for="diasAnticipacionCumpleanos" class="configuracion-reservas-label">
                        <span class="configuracion-reservas-label-icono" aria-hidden="true"><i class="fa-solid fa-cake-candles"></i></span>
                        <span>Reservas de cumpleaños<small>Turnos especiales de tres horas</small></span>
                    </label>
                    <div class="input-group configuracion-reservas-input">
                        <input type="number" class="form-control" id="diasAnticipacionCumpleanos"
                            name="dias_anticipacion_cumpleanos" min="1" max="3650" step="1" required
                            value="<?php echo (int) $configuracion_plazos['cumpleanos']; ?>">
                        <span class="input-group-text">días</span>
                    </div>
                </div>
                <div class="col-lg-2 col-12">
                    <button type="submit" class="btn btn-primary configuracion-reservas-guardar w-100">
                        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                        <span>Guardar</span>
                    </button>
                </div>
        </form>
    </section>
    <div class="row tarjetasCanchas my-5">
        <?php foreach ($canchas as $cancha): ?>
            <div class="col-md-3 my-2">
                <div class="card">
                    <img src="img/canchas/cancha_(<?php echo $cancha['_id']; ?>).png" class="card-img-top"
                        alt="Imagen de la cancha <?php echo $cancha['_id']; ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $cancha['NOMBRE']; ?></h5>
                        <p class="card-text"><?php echo $cancha['DESCRIPCION']; ?></p>

                        <div class="form-group row align-items-center">
                            <div class="col-4">
                                <label for="precio_<?php echo $cancha['_id']; ?>" class="mb-0">Precio base:</label>
                            </div>
                            <div class="col-8">
                                <input type="number" class="form-control" id="precio_<?php echo $cancha['_id']; ?>"
                                    value="<?php echo $cancha['PRECIO']; ?>">
                            </div>
                        </div>
                        <button class="btn btn-primary btn-modificar-precio mt-4"
                            data-cancha-id="<?php echo $cancha['_id']; ?>">Modificar</button>
                        <button type="button" class="btn btn-primary btn-precios-horarios mt-3" data-cancha-id="<?php echo (int) $cancha['_id']; ?>" data-cancha-nombre="<?php echo htmlspecialchars($cancha['NOMBRE'], ENT_QUOTES, 'UTF-8'); ?>">Precios por horario</button>
                        <button class="btn btn-success btn-horarios mt-4" data-cancha-id="<?php echo $cancha['_id']; ?>" data-cancha-nombre="<?php echo htmlspecialchars($cancha['NOMBRE'], ENT_QUOTES, 'UTF-8'); ?>">Horarios</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .configuracion-reservas {
        position: relative;
        overflow: hidden;
        padding: 1.5rem;
        color: var(--color-secundario);
        background: var(--color-cuaternario);
        border-radius: 20px;
        box-shadow: 0 10px 28px rgba(0, 0, 0, .18);
    }
    .configuracion-reservas::after {
        content: '';
        position: absolute;
        top: -65px;
        right: -65px;
        width: 180px;
        height: 180px;
        border: 28px solid rgba(252, 195, 12, .35);
        border-radius: 50%;
        pointer-events: none;
    }
    .configuracion-reservas-encabezado {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.4rem;
        padding-bottom: 1.1rem;
        border-bottom: 1px solid rgba(22, 36, 38, .18);
    }
    .configuracion-reservas-icono {
        display: grid;
        flex: 0 0 54px;
        width: 54px;
        height: 54px;
        place-items: center;
        color: var(--color-primario);
        background: var(--color-secundario);
        border-radius: 16px;
        font-size: 1.35rem;
        box-shadow: 4px 4px 0 var(--color-primario);
    }
    .configuracion-reservas-etiqueta {
        display: block;
        margin-bottom: .15rem;
        color: var(--color-terciario);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .configuracion-reservas h2 {
        margin: 0;
        color: var(--color-secundario);
        font-size: clamp(1.25rem, 2vw, 1.6rem);
        font-weight: 800;
    }
    .configuracion-reservas-encabezado p {
        margin: .25rem 0 0;
        color: var(--color-terciario);
        font-size: .92rem;
    }
    .configuracion-reservas form {
        position: relative;
        z-index: 1;
    }
    .configuracion-reservas-label {
        display: flex;
        align-items: center;
        gap: .65rem;
        margin-bottom: .55rem;
        color: var(--color-secundario);
        font-weight: 800;
        line-height: 1.15;
    }
    .configuracion-reservas-label small {
        display: block;
        margin-top: .2rem;
        color: var(--color-terciario);
        font-size: .75rem;
        font-weight: 500;
    }
    .configuracion-reservas-label-icono {
        display: grid;
        flex: 0 0 34px;
        width: 34px;
        height: 34px;
        place-items: center;
        color: var(--color-primario);
        background: var(--color-terciario);
        border-radius: 10px;
    }
    .configuracion-reservas-input .form-control {
        min-height: 48px;
        color: var(--color-primario);
        background: var(--color-secundario);
        border: 2px solid var(--color-secundario);
        font-size: 1.05rem;
        font-weight: 800;
    }
    .configuracion-reservas-input .form-control:focus {
        border-color: var(--color-primario);
        box-shadow: 0 0 0 .2rem rgba(252, 195, 12, .28);
    }
    .configuracion-reservas-input .input-group-text {
        min-width: 62px;
        justify-content: center;
        color: var(--color-secundario);
        background: var(--color-primario);
        border: 2px solid var(--color-secundario);
        font-weight: 800;
    }
    .configuracion-reservas-guardar {
        display: flex;
        min-height: 48px;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        border-radius: .6rem;
    }
    .configuracion-reservas-guardar:disabled {
        cursor: wait;
        opacity: .7;
    }
    @media (max-width: 767.98px) {
        .configuracion-reservas {
            padding: 1.15rem;
        }
        .configuracion-reservas::after {
            opacity: .45;
        }
        .configuracion-reservas-encabezado {
            align-items: flex-start;
        }
        .configuracion-reservas-icono {
            flex-basis: 46px;
            width: 46px;
            height: 46px;
        }
    }
    #modalHorarios .dia-habilitado {
        width: 1.4rem;
        height: 1.4rem;
        cursor: pointer;
        appearance: none !important;
        -webkit-appearance: none !important;
        border: 2px solid #198754 !important;
        border-radius: .25rem;
        background-color: #fff !important;
        background-image: none !important;
        box-shadow: 0 0 0 1px rgba(25, 135, 84, .15);
    }
    #modalHorarios .dia-habilitado:checked {
        background-color: #198754 !important;
        border-color: #198754 !important;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M3 8l3 3 7-7'/%3E%3C/svg%3E") !important;
        background-repeat: no-repeat !important;
        background-position: center !important;
        background-size: 1rem 1rem !important;
    }
    #modalHorarios .dia-habilitado:focus {
        outline: 3px solid rgba(25, 135, 84, .3) !important;
        outline-offset: 2px;
    }
    #modalHorarios .text-muted {
        color: #d5d9dc !important;
    }
    #modalHorarios .fila-cerrada td {
        color: #6c757d;
        background-color: #f1f3f5;
    }
    #modalHorarios .fila-cerrada td:first-child {
        text-decoration: line-through;
    }
    #modalHorarios .fila-cerrada .hora-dia {
        color: #8b9298;
        background-color: #dfe3e6;
        border-color: #c6cbd0;
        opacity: .7;
    }
    #modalHorarios .franja-editor {
        min-width: 170px;
        padding: .65rem;
        border: 1px solid rgba(255, 255, 255, .15);
        border-radius: .5rem;
    }
    #modalHorarios .franja-editor.is-disabled {
        opacity: .55;
    }
    #modalHorarios .franja-titulo {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .5rem;
        font-weight: 600;
    }
    #modalHorarios .franja-horas {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: .4rem;
    }
    #modalHorarios .franja-horas label {
        margin: 0;
        font-size: .75rem;
    }
    #modalHorarios .franja-horas input {
        min-width: 0;
    }
</style>
<div class="modal fade" id="modalHorarios" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Horario hábil — <span id="nombreCanchaHorario"></span></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<form id="formHorarios"><div class="modal-body"><input type="hidden" name="cancha_id" id="canchaIdHorario"><p class="text-muted">Límites permitidos: mañana de 07:00 a 12:00, tarde de 12:00 a 20:00 y noche de 20:00 a 02:00. Cada horario debe quedar completamente dentro de su franja. La seña se calcula sobre el precio total de la cancha.</p><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Día</th><th>Mañana (07–12)</th><th>Tarde (12–20)</th><th>Noche (20–02)</th><th>Seña (%)</th></tr></thead><tbody id="filasHorarios"></tbody></table></div></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar horarios</button></div></form>
</div></div></div>

<?php
include 'common_scripts.php';
?>

<script>
    const horariosCanchas = <?php $todosHorarios=[]; foreach($canchas as $cancha) $todosHorarios[(int)$cancha['_id']]=horarios_semanales_cancha($con,(int)$cancha['_id']); echo json_encode($todosHorarios, JSON_UNESCAPED_UNICODE); ?>;
    const nombresDias = ['Lunes','Martes','Miércoles','Jueves','Viernes','Sábado','Domingo'];
    const limitesFranjas={manana:{minimo:420,maximo:720,etiqueta:'07:00 a 12:00'},tarde:{minimo:720,maximo:1200,etiqueta:'12:00 a 20:00'},noche:{minimo:1200,maximo:1560,etiqueta:'20:00 a 02:00'}};
    // Script para manejar la modificaciÃ³n de precios
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('formConfiguracionReservas').addEventListener('submit', async function (event) {
            event.preventDefault();

            const boton = this.querySelector('button[type="submit"]');
            boton.disabled = true;

            try {
                const respuesta = await fetch('guardar_configuracion_reservas.php', {
                    method: 'POST',
                    body: new FormData(this),
                });
                const datos = await respuesta.json();

                if (!respuesta.ok || !datos.success) {
                    throw new Error(datos.message || 'No se pudieron guardar los plazos.');
                }

                await Swal.fire({
                    icon: 'success',
                    title: 'Plazos actualizados',
                    text: datos.message,
                });
                window.location.reload();
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message,
                });
            } finally {
                boton.disabled = false;
            }
        });

        var botonesModificar = document.querySelectorAll('.btn-modificar-precio');
        botonesModificar.forEach(function (boton) {
            boton.addEventListener('click', function () {
                var canchaId = this.getAttribute('data-cancha-id');
                var nuevoPrecio = document.getElementById('precio_' + canchaId).value;

                // Enviar la solicitud AJAX para modificar el precio
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "modificar_precio_cancha.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.onreadystatechange = function () {
                    if (xhr.readyState == 4 && xhr.status == 200) {
                        // Manejar la respuesta del servidor
                        if (xhr.responseText.includes("actualizado correctamente")) {
                            // Mostrar una notificaciÃ³n con SweetAlert2
                            Swal.fire({
                                icon: 'success',
                                title: '¡Precio actualizado!',
                                text: xhr.responseText,
                            }).then(function () {
                                // Recargar la pÃ¡gina
                                window.location.reload();
                            });
                        } else {
                            // Mostrar una notificaciÃ³n de error con SweetAlert2
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseText,
                            });
                        }
                    }
                };
                xhr.send("cancha_id=" + encodeURIComponent(canchaId) + "&nuevo_precio=" + encodeURIComponent(nuevoPrecio));
            });
        });
        const modalHorarios = new bootstrap.Modal(document.getElementById('modalHorarios'));
        document.querySelectorAll('.btn-horarios').forEach(boton => boton.addEventListener('click', function () {
            const id=this.dataset.canchaId; document.getElementById('canchaIdHorario').value=id; document.getElementById('nombreCanchaHorario').textContent=this.dataset.canchaNombre;
            const atributosLimite=nombre=>nombre==='noche'?'':` min="${nombre==='manana'?'07:00':'12:00'}" max="${nombre==='manana'?'12:00':'20:00'}"`;
            const editorFranja=(dia,nombre,etiqueta,franja)=>`<div class="franja-editor" data-franja="${nombre}"><label class="franja-titulo"><input class="form-check-input franja-habilitada" type="checkbox" name="dias[${dia}][franjas][${nombre}][habilitada]" ${franja.habilitada?'checked':''}>${etiqueta}</label><div class="franja-horas"><label>Desde<input class="form-control hora-franja campo-horario" type="time" step="1800"${atributosLimite(nombre)} name="dias[${dia}][franjas][${nombre}][apertura]" value="${franja.apertura}"></label><label>Hasta<input class="form-control hora-franja campo-horario" type="time" step="1800"${atributosLimite(nombre)} name="dias[${dia}][franjas][${nombre}][cierre]" value="${franja.cierre}"></label></div></div>`;
            document.getElementById('filasHorarios').innerHTML=nombresDias.map((nombre,i)=>{const dia=i+1,h=horariosCanchas[id][dia];return `<tr><td><strong>${nombre}</strong><label class="d-flex align-items-center gap-2 mt-2"><input class="form-check-input dia-habilitado" type="checkbox" name="dias[${dia}][habilitado]" ${h.habilitado?'checked':''}> Abierto</label></td><td>${editorFranja(dia,'manana','Mañana',h.franjas.manana)}</td><td>${editorFranja(dia,'tarde','Tarde',h.franjas.tarde)}</td><td>${editorFranja(dia,'noche','Noche',h.franjas.noche)}</td><td><div class="input-group"><input class="form-control campo-horario porcentaje-senia" type="number" min="1" max="100" step="0.01" name="dias[${dia}][porcentaje_senia]" value="${h.porcentaje_senia}" required><span class="input-group-text">%</span></div></td></tr>`}).join('');
            document.querySelectorAll('#filasHorarios tr').forEach(fila=>{
                const checkDia=fila.querySelector('.dia-habilitado');
                const actualizarFranja=editor=>{
                    const checkFranja=editor.querySelector('.franja-habilitada');
                    editor.querySelectorAll('.hora-franja').forEach(input=>input.disabled=!checkDia.checked||!checkFranja.checked);
                    editor.classList.toggle('is-disabled',!checkDia.checked||!checkFranja.checked);
                };
                fila.querySelectorAll('.franja-editor').forEach(editor=>{
                    editor.querySelector('.franja-habilitada').addEventListener('change',()=>actualizarFranja(editor));
                });
                const actualizarDia=()=>{
                    fila.querySelectorAll('.franja-habilitada').forEach(check=>check.disabled=!checkDia.checked);
                    fila.querySelector('.porcentaje-senia').disabled=!checkDia.checked;
                    fila.querySelectorAll('.franja-editor').forEach(actualizarFranja);
                    fila.classList.toggle('fila-cerrada',!checkDia.checked);
                    checkDia.setAttribute('aria-label',checkDia.checked?'Día habilitado':'Día cerrado');
                };
                checkDia.addEventListener('change',actualizarDia);
                actualizarDia();
            });
            modalHorarios.show();
        }));
        const minutosFranja=(hora,nombre)=>{const [h,m]=hora.split(':').map(Number);let total=h*60+m;if(nombre==='noche'&&total<420)total+=1440;return total};
        const validarFranjas=()=>{document.querySelectorAll('#filasHorarios tr').forEach(fila=>{if(!fila.querySelector('.dia-habilitado').checked)return;fila.querySelectorAll('.franja-editor').forEach(editor=>{if(!editor.querySelector('.franja-habilitada').checked)return;const nombre=editor.dataset.franja,limite=limitesFranjas[nombre],horas=editor.querySelectorAll('.hora-franja'),inicio=minutosFranja(horas[0].value,nombre),fin=minutosFranja(horas[1].value,nombre);if(inicio<limite.minimo||fin>limite.maximo||inicio>=fin)throw new Error(`La franja ${nombre} debe estar dentro de ${limite.etiqueta}.`)})})};
        document.getElementById('formHorarios').addEventListener('submit',async function(e){e.preventDefault();this.querySelectorAll('.campo-horario:disabled').forEach(x=>x.disabled=false);try{validarFranjas();const r=await fetch('guardar_horarios_cancha.php',{method:'POST',body:new FormData(this)}),d=await r.json();if(!r.ok||!d.success)throw new Error(d.message||'No se pudieron guardar los horarios.');await Swal.fire({icon:'success',title:'Horarios actualizados',text:d.message});window.location.reload()}catch(error){Swal.fire({icon:'error',title:'Error',text:error.message})}});
    });
</script>

<?php include 'editor_precios_canchas.php'; ?>
</body>

</html>
