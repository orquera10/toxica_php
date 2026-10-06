<section id="calendarioPrecios" class="cliente-calendario-precios mb-4" aria-label="Calendario de precios" hidden>
    <div class="d-flex align-items-center justify-content-between mb-2">
        <button type="button" class="btn btn-sm btn-outline-light" id="mesPrecioAnterior" aria-label="Mes anterior">‹</button>
        <strong id="mesPrecios" aria-live="polite"></strong>
        <button type="button" class="btn btn-sm btn-outline-light" id="mesPrecioSiguiente" aria-label="Mes siguiente">›</button>
    </div>
    <div class="cliente-calendario-semana" aria-hidden="true"><span>L</span><span>M</span><span>X</span><span>J</span><span>V</span><span>S</span><span>D</span></div>
    <div id="diasPrecios" class="cliente-calendario-dias"></div>
    <p class="cliente-calendario-leyenda"><span class="precio-promo">Promo</span> Precio menor al base · <span class="precio-especial">Especial</span> Otra tarifa</p>
    <div id="detallePreciosDia" aria-live="polite"></div>
    <small>Las tarifas corresponden a la cancha elegida. Consultá los horarios libres debajo.</small>
</section>
<style>
    .cliente-calendario-precios { padding: 14px; border: 1px solid #617177; border-radius: 14px; background: #162426; color: #fff; }
    .cliente-calendario-semana, .cliente-calendario-dias { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 4px; }
    .cliente-calendario-semana { text-align: center; margin-bottom: 5px; color: #cad6d9; font-size: .8rem; }
    .cliente-calendario-dia { display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 49px; padding: 3px 0; border: 1px solid #607176; border-radius: 8px; background: #243a3e; color: #fff; font-weight: 700; }
    .cliente-calendario-dia small { font-size: .58rem; line-height: 1.1; }
    .cliente-calendario-dia.es-promo { background: #d3f9dd; color: #104923; border-color: #77df98; }
    .cliente-calendario-dia.es-especial { background: #ffebbc; color: #583c00; border-color: #f5c963; }
    .cliente-calendario-dia[aria-pressed="true"] { outline: 3px solid #fcc30c; outline-offset: 1px; position: relative; z-index: 1; }
    .cliente-calendario-dia:disabled { opacity: .3; }
    .cliente-calendario-dia:focus-visible { outline: 3px solid #fff; outline-offset: 1px; }
    .cliente-calendario-leyenda { margin: 14px 0 8px; font-size: .75rem; }
    .precio-promo { color: #9aefb3; font-weight: 800; }
    .precio-especial { color: #ffdc8a; font-weight: 800; }
    #detallePreciosDia { margin-bottom: 8px; font-size: .85rem; }
    #detallePreciosDia p { margin: 4px 0; }
    .cliente-calendario-precios > small { color: #cad6d9; font-size: .72rem; }
    .cliente-slot-btn .cliente-slot-precio, .cliente-slot-btn small { display: block; }
    .cliente-slot-btn.cliente-slot-promo { border: 2px solid #77df98; }
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tarifas = <?= json_encode($tarifas_cliente, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const fecha = document.getElementById('fecha');
    const cancha = document.getElementById('cancha');
    const calendario = document.getElementById('calendarioPrecios');
    const dias = document.getElementById('diasPrecios');
    const detalle = document.getElementById('detallePreciosDia');
    const anterior = document.getElementById('mesPrecioAnterior');
    const siguiente = document.getElementById('mesPrecioSiguiente');
    let mes;
    const moneda = valor => new Intl.NumberFormat('es-AR', {style: 'currency', currency: 'ARS', maximumFractionDigits: 2}).format(valor);
    const fechaLocal = valor => new Date(valor + 'T12:00:00');
    const iso = valor => `${valor.getFullYear()}-${String(valor.getMonth()+1).padStart(2,'0')}-${String(valor.getDate()).padStart(2,'0')}`;
    const hora = minutos => `${String(Math.floor(minutos/60)%24).padStart(2,'0')}:${String(minutos%60).padStart(2,'0')}`;
    function reglasDia(valor) {
        const config = tarifas[cancha.value];
        const numero = valor.getDay() || 7;
        const horario = config?.horarios[numero];
        if (!horario?.habilitado) return [];
        // Mostrar solo las partes de una tarifa que coinciden con horarios abiertos.
        return config.reglas.filter(r => Number(r.dia_semana) === numero && Number(r.precio) !== Number(config.base)).flatMap(r => {
            return Object.values(horario.franjas).filter(f => f.habilitada).flatMap(f => {
                const minutos = h => Number(h.slice(0,2))*60 + Number(h.slice(3,5));
                let inicio = minutos(f.apertura), fin = minutos(f.cierre);
                if (inicio < 120) inicio += 1440;
                if (fin <= inicio) fin += 1440;
                inicio = Math.max(inicio, Number(r.inicio));
                fin = Math.min(fin, Number(r.fin));
                if (cancha.value === '8' && !(config.inicios_cumple[numero] || []).some(h => {
                    const comienzo = minutos(h);
                    return comienzo >= inicio && comienzo < fin;
                })) return [];
                return inicio < fin ? [{...r, inicio, fin}] : [];
            });
        });
    }
    function pintar() {
        const config = tarifas[cancha.value];
        calendario.hidden = !config;
        if (!config || !mes || !fecha.value) return;
        document.getElementById('mesPrecios').textContent = mes.toLocaleDateString('es-AR', {month:'long', year:'numeric'});
        anterior.disabled = iso(mes).slice(0,7) <= fecha.min.slice(0,7);
        siguiente.disabled = iso(mes).slice(0,7) >= fecha.max.slice(0,7);
        dias.replaceChildren();
        for (let i=0; i<((mes.getDay()+6)%7); i++) dias.appendChild(document.createElement('span'));
        const cantidad = new Date(mes.getFullYear(), mes.getMonth()+1, 0).getDate();
        for (let d=1; d<=cantidad; d++) {
            const dia = new Date(mes.getFullYear(), mes.getMonth(), d, 12);
            const valor = iso(dia), reglas = reglasDia(dia);
            const promo = reglas.some(r => Number(r.precio) < Number(config.base));
            const boton = document.createElement('button');
            boton.type = 'button';
            boton.className = 'cliente-calendario-dia' + (promo ? ' es-promo' : reglas.length ? ' es-especial' : '');
            boton.textContent = d;
            boton.disabled = valor < fecha.min || valor > fecha.max;
            boton.setAttribute('aria-pressed', String(valor === fecha.value));
            boton.setAttribute('aria-label', dia.toLocaleDateString('es-AR', {weekday:'long', day:'numeric', month:'long'}) + (promo ? ', con promoción' : reglas.length ? ', precio especial' : ''));
            if (reglas.length) {
                const etiqueta = document.createElement('small');
                etiqueta.textContent = promo ? 'Promo' : 'Especial';
                boton.appendChild(etiqueta);
            }
            boton.addEventListener('click', () => { fecha.value = valor; fecha.dispatchEvent(new Event('change', {bubbles:true})); });
            dias.appendChild(boton);
        }
        detalle.replaceChildren();
        const reglas = reglasDia(fechaLocal(fecha.value));
        const titulo = document.createElement('strong');
        const abierto = config.horarios[fechaLocal(fecha.value).getDay() || 7]?.habilitado;
        titulo.textContent = reglas.length ? 'Tarifas del ' + fechaLocal(fecha.value).toLocaleDateString('es-AR') : 'Este día se aplica el precio base.';
        detalle.appendChild(titulo);
        reglas.forEach(r => {
            const linea = document.createElement('p');
            const promo = Number(r.precio) < Number(config.base);
            linea.className = promo ? 'precio-promo' : 'precio-especial';
            linea.textContent = `${promo ? 'Promo' : 'Especial'} · ${hora(r.inicio)} a ${hora(r.fin)}${r.fin >= 1440 ? ' (madrugada)' : ''} · ${moneda(r.precio)}${cancha.value === '8' ? ' / paquete de 3 h, según inicio' : ' / hora'}`;
            detalle.appendChild(linea);
        });
        if (reglas.length) {
            const base = document.createElement('p');
            base.textContent = 'Fuera de estas franjas: ' + moneda(config.base) + (cancha.value === '8' ? ' / paquete de 3 h.' : ' / hora.');
            detalle.appendChild(base);
        }
    }
    function sincronizar() {
        const seleccion = fechaLocal(fecha.value || fecha.min);
        mes = new Date(seleccion.getFullYear(), seleccion.getMonth(), 1, 12);
        pintar();
    }
    anterior.addEventListener('click', () => {mes.setMonth(mes.getMonth()-1); pintar();});
    siguiente.addEventListener('click', () => {mes.setMonth(mes.getMonth()+1); pintar();});
    fecha.addEventListener('change', sincronizar);
    cancha.addEventListener('change', sincronizar);
    sincronizar();
});
</script>
