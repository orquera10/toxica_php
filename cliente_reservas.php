<?php
$pageTitle = "Reservar turno";
require_once __DIR__ . '/cliente_auth.php';
require_once __DIR__ . '/reglas_reservas.php';
$cliente = requerir_cliente();
cancelar_reservas_pendientes_vencidas($con);
include __DIR__ . '/header.php';

$canchas = mysqli_query($con, "SELECT _id, NOMBRE, PRECIO FROM canchas WHERE _id NOT IN (9, 10) ORDER BY NOMBRE");

$cliente_id = (int) $cliente['_id'];
$turnos_por_pagina = 5;
$pagina_actual = max(1, (int) ($_GET['pagina'] ?? 1));
$offset = ($pagina_actual - 1) * $turnos_por_pagina;

$stmt_total = $con->prepare("SELECT COUNT(*) AS total
    FROM turnos t
    INNER JOIN ticket tk ON tk.id_TURNO = t._id
    WHERE tk.id_CLIENTE = ? AND t.VENTA = 0");
$stmt_total->bind_param("i", $cliente_id);
$stmt_total->execute();
$total_turnos = (int) ($stmt_total->get_result()->fetch_assoc()['total'] ?? 0);
$total_paginas = max(1, (int) ceil($total_turnos / $turnos_por_pagina));

if ($pagina_actual > $total_paginas) {
    $pagina_actual = $total_paginas;
    $offset = ($pagina_actual - 1) * $turnos_por_pagina;
}

$stmt = $con->prepare("SELECT t._id, t.FECHA, t.HORA_INICIO, t.HORA_FIN, t.id_CANCHA AS cancha_id, c.NOMBRE AS cancha, tk._id AS ticket_id, tk.TOTAL, tk.TOTAL_CANCHA, tk.SENIA, tk.MP_SENIA, tk.ESTADO_RESERVA
    FROM turnos t
    INNER JOIN ticket tk ON tk.id_TURNO = t._id
    INNER JOIN canchas c ON c._id = t.id_CANCHA
    WHERE tk.id_CLIENTE = ? AND t.VENTA = 0
    ORDER BY STR_TO_DATE(CONCAT(t.FECHA, ' ', t.HORA_INICIO), '%d-%m-%Y %H:%i') DESC, t._id DESC
    LIMIT ? OFFSET ?");
$stmt->bind_param("iii", $cliente_id, $turnos_por_pagina, $offset);
$stmt->execute();
$mis_turnos = $stmt->get_result();
$turnos_cliente = $mis_turnos->fetch_all(MYSQLI_ASSOC);
include __DIR__ . '/cliente_navbar.php';

function fecha_cliente_turno($fecha, $hora_inicio)
{
    if (in_array($hora_inicio, ['00:00', '00:30', '01:00', '01:30'], true)) {
        return date('d-m-Y', strtotime($fecha . ' -1 day')) . ' (madrugada del ' . $fecha . ')';
    }

    return $fecha;
}
?>

<style>
    .cliente-slot-btn .cliente-slot-precio,
    .cliente-slot-btn small { display: block; }
    .cliente-slot-btn.cliente-slot-promo { border: 2px solid #77df98; }
</style>
<main class="container cliente-page py-4">
    <div class="row">
        <div class="col-12">
            <?php include __DIR__ . '/msjs.php'; ?>
        </div>
    </div>

    <div class="row g-4">
        <section class="col-12 col-lg-5 order-1">
            <div class="cliente-section">
                <div class="cliente-reserva-head">
                    <h1>Reservar turno</h1>
                    <button class="btn cliente-reserva-toggle d-lg-none" type="button" data-bs-toggle="collapse"
                        data-bs-target="#formReservaCliente" aria-expanded="false" aria-controls="formReservaCliente">
                        <i class="fas fa-calendar-plus"></i>
                        <span>Nueva reserva</span>
                        <i class="fas fa-chevron-down cliente-reserva-chevron"></i>
                    </button>
                </div>

                <form action="confirmar_reserva_cliente.php" method="POST" id="formReservaCliente"
                    class="collapse d-lg-block mt-4" data-loading-form="true">
                    <div class="mb-3">
                        <label for="fecha" class="form-label">Fecha</label>
                        <input type="date" class="form-control" id="fecha" name="fecha"
                            min="<?php echo date('Y-m-d'); ?>"
                            max="<?php echo date('Y-m-d', strtotime('+' . max_dias_anticipacion_reserva(CANCHA_1_ID) . ' days')); ?>"
                            data-max-turno="<?php echo date('Y-m-d', strtotime('+' . max_dias_anticipacion_reserva(CANCHA_1_ID) . ' days')); ?>"
                            data-max-cumple="<?php echo date('Y-m-d', strtotime('+' . max_dias_anticipacion_reserva(CANCHA_CUMPLE_ID) . ' days')); ?>"
                            value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <input type="hidden" id="fecha_reserva" name="fecha_reserva">
                    <input type="hidden" id="hora_inicio" name="hora_inicio" required>
                    <div class="mb-3">
                        <label for="duracion" class="form-label">Duracion</label>
                        <select class="form-select" id="duracion" name="duracion">
                            <option value="1">1 hora</option>
                            <option value="2">2 horas</option>
                            <option value="3">3 horas</option>
                            <option value="4">4 horas</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label for="cancha" class="form-label">Cancha</label>
                        <select class="form-select" id="cancha" name="cancha" required>
                            <option value="">Seleccionar</option>
                            <?php while ($cancha = mysqli_fetch_assoc($canchas)): ?>
                                <option value="<?php echo (int) $cancha['_id']; ?>">
                                    <?php echo htmlspecialchars(nombre_cancha_cliente($cancha['_id'], $cancha['NOMBRE'])); ?> -
                                    $<?php echo number_format((float) $cancha['PRECIO'], 0, ',', '.'); ?><?php echo (int) $cancha['_id'] === 8 ? '/3 hs' : '/h'; ?> (base)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="cliente-disponibilidad mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3>Horarios disponibles</h3>
                            <span id="estadoDisponibilidad">Elegí fecha y cancha</span>
                        </div>
                        <div id="slotsDisponibles" class="cliente-slots"></div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reservar</button>
                </form>
            </div>
        </section>

        <section class="col-12 col-lg-7 order-2">
            <div class="cliente-section">
                <h2>Mis turnos</h2>
                <p class="cliente-turnos-count"><?php echo $total_turnos; ?> reserva<?php echo $total_turnos === 1 ? '' : 's'; ?> registrada<?php echo $total_turnos === 1 ? '' : 's'; ?></p>

                <div class="cliente-turnos-list mt-4">
                    <?php if (count($turnos_cliente) === 0): ?>
                        <div class="cliente-empty-state">
                            <strong>Todavia no tenes turnos reservados.</strong>
                            <span>Cuando reserves, van a aparecer aca.</span>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($turnos_cliente as $turno): ?>
                        <?php
                        $senia = (float) ($turno['SENIA'] > 0 ? $turno['SENIA'] : $turno['MP_SENIA']);
                        $pendiente = $turno['ESTADO_RESERVA'] === 'pendiente_pago';
                        ?>
                        <article class="cliente-turno-card">
                            <div class="cliente-turno-main">
                                <div>
                                    <span class="cliente-turno-label">Fecha</span>
                                    <strong><?php echo htmlspecialchars(fecha_cliente_turno($turno['FECHA'], $turno['HORA_INICIO'])); ?></strong>
                                </div>
                                <span class="cliente-turno-status <?php echo $pendiente ? 'is-pending' : 'is-confirmed'; ?>">
                                    <?php echo $pendiente ? 'Pendiente se&ntilde;a' : 'Confirmada'; ?>
                                </span>
                            </div>

                            <div class="cliente-turno-info">
                                <div>
                                    <span>Horario</span>
                                    <strong><?php echo htmlspecialchars($turno['HORA_INICIO'] . ' a ' . $turno['HORA_FIN']); ?></strong>
                                </div>
                                <div>
                                    <span>Cancha</span>
                                    <strong><?php echo htmlspecialchars(nombre_cancha_cliente($turno['cancha_id'], $turno['cancha'])); ?></strong>
                                </div>
                            </div>

                            <div class="cliente-turno-money">
                                <div>
                                    <span>Se&ntilde;a</span>
                                    <strong>$<?php echo number_format($senia, 0, ',', '.'); ?></strong>
                                </div>
                                <div>
                                    <span>Total</span>
                                    <strong>$<?php echo number_format((float) $turno['TOTAL_CANCHA'], 0, ',', '.'); ?></strong>
                                </div>
                            </div>

                            <?php if ($pendiente): ?>
                                <form action="reintentar_pago_senia.php" method="POST" data-loading-form="true" class="cliente-turno-action">
                                    <input type="hidden" name="ticket_id" value="<?php echo (int) $turno['ticket_id']; ?>">
                                    <button type="submit" class="btn btn-warning text-dark fw-bold w-100">Pagar se&ntilde;a</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_paginas > 1): ?>
                    <nav class="cliente-pagination" aria-label="Paginacion de turnos">
                        <a class="cliente-page-link <?php echo $pagina_actual <= 1 ? 'disabled' : ''; ?>"
                            href="?pagina=<?php echo max(1, $pagina_actual - 1); ?>" aria-label="Pagina anterior">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <span>Pagina <?php echo $pagina_actual; ?> de <?php echo $total_paginas; ?></span>
                        <a class="cliente-page-link <?php echo $pagina_actual >= $total_paginas ? 'disabled' : ''; ?>"
                            href="?pagina=<?php echo min($total_paginas, $pagina_actual + 1); ?>" aria-label="Pagina siguiente">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </nav>
                <?php endif; ?>

                <?php $mis_turnos->data_seek(0); ?>
                <div class="table-responsive mt-4 cliente-turnos-table-backup">
                    <table class="table cliente-table align-middle">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Horario</th>
                                <th>Cancha</th>
                                <th>Estado</th>
                                <th>Seña</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($mis_turnos->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4">Todavia no tenes turnos reservados.</td>
                                </tr>
                            <?php endif; ?>
                            <?php while ($turno = $mis_turnos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(fecha_cliente_turno($turno['FECHA'], $turno['HORA_INICIO'])); ?></td>
                                    <td><?php echo htmlspecialchars($turno['HORA_INICIO'] . ' a ' . $turno['HORA_FIN']); ?></td>
                                    <td><?php echo htmlspecialchars(nombre_cancha_cliente($turno['cancha_id'], $turno['cancha'])); ?></td>
                                    <td>
                                        <?php if ($turno['ESTADO_RESERVA'] === 'pendiente_pago'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente seña</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Confirmada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?php echo number_format((float) ($turno['SENIA'] > 0 ? $turno['SENIA'] : $turno['MP_SENIA']), 0, ',', '.'); ?></td>
                                    <td>$<?php echo number_format((float) $turno['TOTAL_CANCHA'], 0, ',', '.'); ?></td>
                                    <td>
                                        <?php if ($turno['ESTADO_RESERVA'] === 'pendiente_pago'): ?>
                                            <form action="reintentar_pago_senia.php" method="POST" data-loading-form="true">
                                                <input type="hidden" name="ticket_id" value="<?php echo (int) $turno['ticket_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-warning text-dark fw-bold">Pagar seña</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>
<?php
$stmt->close();
$stmt_total->close();
include __DIR__ . '/common_scripts.php';
include __DIR__ . '/cliente_form_utils.php';
?>
<script>
    const fechaInput = document.getElementById('fecha');
    const horaInput = document.getElementById('hora_inicio');
    const duracionInput = document.getElementById('duracion');
    const canchaInput = document.getElementById('cancha');
    const slotsDisponibles = document.getElementById('slotsDisponibles');
    const estadoDisponibilidad = document.getElementById('estadoDisponibilidad');

    function limpiarSlots(mensaje) {
        slotsDisponibles.innerHTML = '';
        estadoDisponibilidad.textContent = mensaje;
        horaInput.value = '';
        document.getElementById('fecha_reserva').value = '';
    }

    let consultaDisponibilidad = 0;
    function cargarDisponibilidad() {
        const consulta = ++consultaDisponibilidad;
        const fecha = fechaInput.value;
        const cancha = canchaInput.value;
        const duracion = cancha === '8' ? '3' : duracionInput.value;

        if (!fecha || !cancha) {
            limpiarSlots('Elegí fecha y cancha');
            return;
        }

        limpiarSlots('Buscando...');

        fetch(`disponibilidad_cliente.php?fecha=${encodeURIComponent(fecha)}&cancha=${encodeURIComponent(cancha)}&duracion=${encodeURIComponent(duracion)}`)
            .then(response => response.json())
            .then(data => {
                if (consulta !== consultaDisponibilidad) return;
                slotsDisponibles.innerHTML = '';

                if (!data.success) {
                    estadoDisponibilidad.textContent = data.message || 'No se pudo consultar';
                    return;
                }

                if (data.slots.length === 0) {
                    estadoDisponibilidad.textContent = 'Sin horarios libres';
                    slotsDisponibles.innerHTML = '<p class="cliente-slots-empty">No hay turnos disponibles para esa selección.</p>';
                    return;
                }

                estadoDisponibilidad.textContent = `${data.slots.length} libres`;

                data.slots.forEach(slot => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className = 'cliente-slot-btn';
                    const horario = document.createElement('span');
                    horario.textContent = slot.label;
                    button.appendChild(horario);
                    const precio = document.createElement('strong');
                    precio.className = 'cliente-slot-precio';
                    precio.textContent = new Intl.NumberFormat('es-AR', {style: 'currency', currency: 'ARS', maximumFractionDigits: 2}).format(slot.total) + ' total';
                    button.appendChild(precio);
                    if (slot.total < slot.total_base) {
                        button.classList.add('cliente-slot-promo');
                        const promo = document.createElement('small');
                        promo.textContent = 'Promo \u00b7 ahorr\u00e1s ' + new Intl.NumberFormat('es-AR', {style: 'currency', currency: 'ARS', maximumFractionDigits: 2}).format(slot.total_base - slot.total);
                        button.appendChild(promo);
                    }
                    button.dataset.inicio = slot.inicio;
                    button.dataset.fecha = slot.fecha;

                    button.addEventListener('click', function () {
                        document.querySelectorAll('.cliente-slot-btn').forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        horaInput.value = slot.inicio;
                        document.getElementById('fecha_reserva').value = slot.fecha;
                    });

                    slotsDisponibles.appendChild(button);
                });
            })
            .catch(() => {
                if (consulta !== consultaDisponibilidad) return;
                limpiarSlots('No se pudo consultar');
            });
    }

    function actualizarFechaMaxima() {
        fechaInput.max = canchaInput.value === '8'
            ? fechaInput.dataset.maxCumple
            : fechaInput.dataset.maxTurno;
        if (fechaInput.value > fechaInput.max) fechaInput.value = fechaInput.max;
    }

    fechaInput.addEventListener('change', cargarDisponibilidad);
    duracionInput.addEventListener('change', cargarDisponibilidad);
    canchaInput.addEventListener('change', function () {
        actualizarFechaMaxima();

        if (canchaInput.value === '8') {
            duracionInput.value = '3';
            duracionInput.disabled = true;
        } else {
            duracionInput.disabled = false;
        }

        cargarDisponibilidad();
    });

    setTimeout(function () {
        $(".alert").slideUp(300);
    }, 3500);
</script>
</body>
</html>
