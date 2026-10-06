<?php
$precios_por_cancha = [];
foreach ($canchas as $cancha_precio) {
    $precios_por_cancha[(int) $cancha_precio['_id']] = precios_horarios_cancha($con, (int) $cancha_precio['_id']);
}
?>
<link rel="stylesheet" href="css/precios_horarios.css?v=1">
<div class="modal fade" id="modalPrecios" tabindex="-1" aria-labelledby="tituloPrecios" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><div><h5 class="modal-title" id="tituloPrecios">Precios por horario</h5><p class="precios-subtitulo" id="nombreCanchaPrecios"></p></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <form id="formPreciosHorarios">
            <div class="modal-body">
                <p class="precios-intro">Defin&iacute; tarifas especiales para cada d&iacute;a y horario. Se repiten cada semana; fuera de esas franjas se cobra el precio base.</p>
                <div class="precios-ayuda"><p id="ayudaPrecioUnidad"></p><p>La madrugada (00:00 a 02:00) pertenece a la noche del d&iacute;a elegido.</p></div>
                <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Día</th><th>Desde</th><th>Hasta</th><th>Precio ($)</th><th><span class="visually-hidden">Acciones</span></th></tr></thead><tbody id="filasPrecios"></tbody></table></div>
                <div class="precios-vacio" id="preciosVacio"><strong>Esta cancha usa el precio base</strong><p>Agreg&aacute; una franja para ofrecer, por ejemplo, una promo los lunes a la siesta.</p></div>
                <button type="button" class="btn precios-agregar" id="agregarFranjaPrecio"><span aria-hidden="true">+</span> Agregar franja</button>
                <p class="precios-nota">Para volver al precio base, quitá la franja y guardá. Los turnos ya registrados conservan su importe.</p>
            </div>
            <div class="modal-footer"><button type="button" class="btn precios-cancelar" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn precios-guardar">Guardar precios</button></div>
        </form>
    </div></div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const precios = <?= json_encode($precios_por_cancha, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const csrf = <?= json_encode($_SESSION['csrf_precios']) ?>;
    const modal = new bootstrap.Modal(document.getElementById('modalPrecios'));
    const filas = document.getElementById('filasPrecios');
    const vacio = document.getElementById('preciosVacio');
    const actualizarVacio = () => { vacio.hidden = filas.children.length > 0; };
    let canchaId;
    const dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
    function agregar(regla = {dia_semana: 1, inicio: 780, fin: 1020, precio: ''}) {
        const tr = document.createElement('tr');
        const opcionesHora = valor => {
            let html = '';
            for (let m = 420; m <= 1560; m += 30) {
                const hora = String(Math.floor(m / 60) % 24).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
                html += `<option value="${m}" ${m === Number(valor) ? 'selected' : ''}>${hora}</option>`;
            }
            return html;
        };
        tr.innerHTML = `<td><span class="precio-campo-label">D&iacute;a</span><select class="form-select dia" aria-label="Día">${dias.map((d,i) => `<option value="${i+1}" ${i+1 === Number(regla.dia_semana) ? 'selected' : ''}>${d}</option>`).join('')}</select></td>
            <td><span class="precio-campo-label">Desde</span><select class="form-select inicio" aria-label="Desde">${opcionesHora(regla.inicio)}</select></td>
            <td><span class="precio-campo-label">Hasta</span><select class="form-select fin" aria-label="Hasta">${opcionesHora(regla.fin)}</select></td>
            <td><span class="precio-campo-label">Precio</span><div class="precio-moneda"><span aria-hidden="true">$</span><input class="form-control precio" aria-label="Precio" type="number" min="0.01" max="9999999999.99" step="0.01" required placeholder="0,00"></div></td>
            <td><button type="button" class="btn precios-quitar">Quitar</button></td>`;
        tr.querySelector('.precio').value = regla.precio;
        tr.querySelector('button').addEventListener('click', () => { tr.remove(); actualizarVacio(); });
        filas.appendChild(tr);
        actualizarVacio();
    }
    document.querySelectorAll('.btn-precios-horarios').forEach(boton => boton.addEventListener('click', () => {
        canchaId = boton.dataset.canchaId;
        document.getElementById('nombreCanchaPrecios').textContent = boton.dataset.canchaNombre;
        document.getElementById('ayudaPrecioUnidad').textContent = Number(canchaId) === 8
            ? 'Para cumpleaños, el precio corresponde al paquete de 3 horas y se elige según la hora de inicio.'
            : 'Los precios son por hora. Si un turno cruza franjas, se calcula cada parte con su precio correspondiente.';
        filas.replaceChildren();
        (precios[canchaId] || []).forEach(agregar);
        actualizarVacio();
        modal.show();
    }));
    document.getElementById('agregarFranjaPrecio').addEventListener('click', () => agregar());
    document.getElementById('formPreciosHorarios').addEventListener('submit', async event => {
        event.preventDefault();
        const reglas = Array.from(filas.children, tr => ({dia_semana: Number(tr.querySelector('.dia').value), inicio: Number(tr.querySelector('.inicio').value), fin: Number(tr.querySelector('.fin').value), precio: tr.querySelector('.precio').value}));
        if (reglas.some((r,i) => r.inicio >= r.fin || reglas.some((o,j) => j < i && o.dia_semana === r.dia_semana && r.inicio < o.fin && r.fin > o.inicio))) {
            Swal.fire({icon: 'error', title: 'Revisá las franjas', text: 'Desde debe ser anterior a Hasta y las franjas del mismo día no pueden superponerse.'});
            return;
        }
        const boton = event.target.querySelector('[type="submit"]');
        boton.disabled = true;
        try {
            const body = new FormData();
            body.set('cancha_id', canchaId); body.set('csrf', csrf); body.set('reglas', JSON.stringify(reglas));
            const respuesta = await fetch('guardar_precios_horarios.php', {method: 'POST', body});
            const datos = await respuesta.json();
            if (!respuesta.ok || !datos.success) throw new Error(datos.message || 'No se pudieron guardar los precios.');
            precios[canchaId] = reglas;
            modal.hide();
            Swal.fire({icon: 'success', title: 'Precios actualizados', text: datos.message});
        } catch (error) {
            Swal.fire({icon: 'error', title: 'Error', text: error.message});
        } finally { boton.disabled = false; }
    });
});
</script>
