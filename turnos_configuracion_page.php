<?php
$pageTitle = 'Configuración de turnos';
include 'header.php';
include 'headerUsuario.php';
include 'barraNavegacion.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/reglas_reservas.php';

$configuracion_turnos = configuracion_plazos_reserva($con);
?>

<main class="container turnos-configuracion-contenedor">
    <section class="turnos-configuracion" aria-labelledby="tituloConfiguracionTurnos">
        <div class="turnos-configuracion-encabezado">
            <div class="turnos-configuracion-icono" aria-hidden="true">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div>
                <span class="turnos-configuracion-etiqueta">Administración</span>
                <h1 id="tituloConfiguracionTurnos">Alertas de turnos</h1>
                <p>Configurá quiénes reciben un aviso cuando se confirma una reserva para el día actual.</p>
            </div>
        </div>

        <div class="turnos-configuracion-reglas" aria-label="Funcionamiento de las alertas">
            <div><i class="fa-solid fa-circle-check" aria-hidden="true"></i><span>Sólo reservas confirmadas</span></div>
            <div><i class="fa-solid fa-calendar-day" aria-hidden="true"></i><span>Únicamente para hoy</span></div>
            <div><i class="fa-solid fa-calendar-xmark" aria-hidden="true"></i><span>Las reservas futuras no generan aviso</span></div>
        </div>

        <form id="formAlertasTurnos">
            <label for="telefonosAlertaReservaHoy" class="turnos-configuracion-label">
                <span class="turnos-configuracion-label-icono" aria-hidden="true"><i class="fa-brands fa-whatsapp"></i></span>
                <span>Números que recibirán las alertas<small>Ingresá un teléfono por línea, incluyendo el código de área.</small></span>
            </label>
            <textarea class="form-control turnos-configuracion-telefonos" id="telefonosAlertaReservaHoy"
                name="telefonos_alerta_reserva_hoy" rows="7"
                placeholder="3886002759&#10;3885123456"><?php echo htmlspecialchars($configuracion_turnos['telefonos_alerta_reserva_hoy'], ENT_QUOTES, 'UTF-8'); ?></textarea>

            <div class="turnos-configuracion-pie">
                <span><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Hasta 20 números. Los duplicados se eliminan automáticamente.</span>
                <button type="submit" class="btn btn-primary turnos-configuracion-guardar">
                    <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                    <span>Guardar teléfonos</span>
                </button>
            </div>
        </form>
    </section>
</main>

<style>
    .turnos-configuracion-contenedor {
        max-width: 980px;
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    .turnos-configuracion {
        position: relative;
        overflow: hidden;
        padding: 1.75rem;
        color: var(--color-secundario);
        background: var(--color-cuaternario);
        border-radius: 20px;
        box-shadow: 0 12px 32px rgba(0, 0, 0, .2);
    }
    .turnos-configuracion::after {
        content: '';
        position: absolute;
        top: -80px;
        right: -75px;
        width: 210px;
        height: 210px;
        border: 32px solid rgba(252, 195, 12, .35);
        border-radius: 50%;
        pointer-events: none;
    }
    .turnos-configuracion-encabezado {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        gap: 1rem;
        padding-bottom: 1.25rem;
        border-bottom: 1px solid rgba(22, 36, 38, .18);
    }
    .turnos-configuracion-icono {
        display: grid;
        flex: 0 0 58px;
        width: 58px;
        height: 58px;
        place-items: center;
        color: var(--color-primario);
        background: var(--color-secundario);
        border-radius: 17px;
        font-size: 1.45rem;
        box-shadow: 4px 4px 0 var(--color-primario);
    }
    .turnos-configuracion-etiqueta {
        display: block;
        margin-bottom: .15rem;
        color: var(--color-terciario);
        font-size: .72rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }
    .turnos-configuracion h1 {
        margin: 0;
        color: var(--color-secundario);
        font-size: clamp(1.35rem, 2.5vw, 1.8rem);
        font-weight: 800;
    }
    .turnos-configuracion-encabezado p {
        margin: .3rem 0 0;
        color: var(--color-terciario);
        font-size: .92rem;
    }
    .turnos-configuracion-reglas {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: .75rem;
        margin: 1.4rem 0;
    }
    .turnos-configuracion-reglas div {
        display: flex;
        align-items: center;
        gap: .55rem;
        min-height: 58px;
        padding: .75rem;
        color: var(--color-cuaternario);
        background: var(--color-terciario);
        border-radius: 12px;
        font-size: .82rem;
        font-weight: 700;
    }
    .turnos-configuracion-reglas i {
        color: var(--color-primario);
        font-size: 1rem;
    }
    .turnos-configuracion form {
        position: relative;
        z-index: 1;
    }
    .turnos-configuracion-label {
        display: flex;
        align-items: center;
        gap: .7rem;
        margin-bottom: .65rem;
        color: var(--color-secundario);
        font-weight: 800;
        line-height: 1.2;
    }
    .turnos-configuracion-label small {
        display: block;
        margin-top: .25rem;
        color: var(--color-terciario);
        font-size: .78rem;
        font-weight: 500;
    }
    .turnos-configuracion-label-icono {
        display: grid;
        flex: 0 0 38px;
        width: 38px;
        height: 38px;
        place-items: center;
        color: var(--color-primario);
        background: var(--color-terciario);
        border-radius: 11px;
        font-size: 1.15rem;
    }
    .turnos-configuracion-telefonos {
        min-height: 180px;
        resize: vertical;
        color: var(--color-primario);
        background: var(--color-secundario);
        border: 2px solid var(--color-secundario);
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.65;
    }
    .turnos-configuracion-telefonos::placeholder {
        color: rgba(249, 245, 210, .5);
    }
    .turnos-configuracion-telefonos:focus {
        color: var(--color-primario);
        background: var(--color-secundario);
        border-color: var(--color-primario);
        box-shadow: 0 0 0 .2rem rgba(252, 195, 12, .28);
    }
    .turnos-configuracion-pie {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1rem;
        color: var(--color-terciario);
        font-size: .8rem;
        font-weight: 600;
    }
    .turnos-configuracion-pie > span {
        display: flex;
        align-items: center;
        gap: .4rem;
    }
    .turnos-configuracion-guardar {
        display: flex;
        min-height: 48px;
        align-items: center;
        justify-content: center;
        gap: .5rem;
        white-space: nowrap;
        border-radius: .6rem;
    }
    .turnos-configuracion-guardar:disabled {
        cursor: wait;
        opacity: .7;
    }
    @media (max-width: 767.98px) {
        .turnos-configuracion-contenedor {
            padding-top: 1.5rem;
            padding-bottom: 1.5rem;
        }
        .turnos-configuracion {
            padding: 1.15rem;
        }
        .turnos-configuracion::after {
            opacity: .4;
        }
        .turnos-configuracion-encabezado {
            align-items: flex-start;
        }
        .turnos-configuracion-icono {
            flex-basis: 48px;
            width: 48px;
            height: 48px;
        }
        .turnos-configuracion-reglas {
            grid-template-columns: 1fr;
        }
        .turnos-configuracion-pie {
            align-items: stretch;
            flex-direction: column;
        }
        .turnos-configuracion-guardar {
            width: 100%;
        }
    }
</style>

<?php include 'common_scripts.php'; ?>

<script>
    document.getElementById('formAlertasTurnos').addEventListener('submit', async function (event) {
        event.preventDefault();

        const boton = this.querySelector('button[type="submit"]');
        const contenidoOriginal = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i><span>Guardando...</span>';

        try {
            const respuesta = await fetch('guardar_alertas_turnos.php', {
                method: 'POST',
                body: new FormData(this),
            });
            const datos = await respuesta.json();

            if (!respuesta.ok || !datos.success) {
                throw new Error(datos.message || 'No se pudieron guardar los teléfonos.');
            }

            await Swal.fire({
                icon: 'success',
                title: 'Alertas actualizadas',
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
            boton.innerHTML = contenidoOriginal;
        }
    });
</script>

</body>
</html>
