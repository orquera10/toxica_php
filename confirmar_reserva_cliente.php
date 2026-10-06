<?php
$pageTitle = "Confirmar reserva";
require_once 'cliente_auth.php';
require_once 'reglas_reservas.php';
require_once 'bloqueos_horarios.php';

$cliente = requerir_cliente();
cancelar_reservas_pendientes_vencidas($con);

function volver_reservas_cliente($mensaje)
{
    header("Location: cliente_reservas.php?error=" . urlencode($mensaje));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cliente_reservas.php");
    exit;
}

$fecha_input = $_POST['fecha_reserva'] ?? '';
if ($fecha_input === '') {
    $fecha_input = $_POST['fecha'] ?? '';
    $minutos_inicio = minutos_desde_apertura($_POST['hora_inicio'] ?? '');
    if ($fecha_input !== '' && $minutos_inicio !== null && $minutos_inicio >= 24 * 60) {
        $fecha_input = date('Y-m-d', strtotime($fecha_input . ' +1 day'));
    }
}

$hora_inicio_input = $_POST['hora_inicio'] ?? '';
$id_cancha = (int) ($_POST['cancha'] ?? 0);
$duracion = duracion_reserva($id_cancha, (int) ($_POST['duracion'] ?? 1));

if (!$fecha_input || !$hora_inicio_input || $duracion < 1 || $duracion > 4 || $id_cancha <= 0) {
    volver_reservas_cliente("Completa todos los datos de la reserva.");
}

if (!reserva_dentro_del_plazo($fecha_input, $id_cancha, $hora_inicio_input)) {
    volver_reservas_cliente(mensaje_plazo_reserva($id_cancha));
}

if (!horario_en_hora_punto($hora_inicio_input)) {
    volver_reservas_cliente("Los turnos deben comenzar en horas en punto.");
}

$reserva_horario_valida = $id_cancha === CANCHA_CUMPLE_ID
    ? reserva_cumpleanios_cliente_permitida($fecha_input, $hora_inicio_input, $duracion) && reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio_input, $duracion, $fecha_input)
    : reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio_input, $duracion, $fecha_input);

if (!$reserva_horario_valida) {
    $mensaje_horario = $id_cancha === CANCHA_CUMPLE_ID
        ? mensaje_horario_cumpleanios_cliente()
        : ($id_cancha === CANCHA_PROMO_ID
        ? "La cancha promo solo se puede reservar entre las 13:00 y las 17:00."
        : "Los turnos pueden reservarse desde las 13:00 y finalizar como maximo a las 02:00.");
    volver_reservas_cliente($mensaje_horario);
}

$inicio_ts = strtotime($fecha_input . ' ' . $hora_inicio_input);
if ($inicio_ts === false || $inicio_ts < time()) {
    volver_reservas_cliente("Elige una fecha y horario futuro.");
}

$fin_ts = strtotime('+' . $duracion . ' hours', $inicio_ts);
$fecha = date('d-m-Y', $inicio_ts);
$fecha_reserva = date('Y-m-d', $inicio_ts);
$hora_inicio = date('H:i', $inicio_ts);
$hora_fin = date('H:i', $fin_ts);
$fecha_fin = date('d-m-Y', $fin_ts);
$datetime_inicio = $fecha . ' ' . $hora_inicio;
$datetime_fin = $fecha_fin . ' ' . $hora_fin;

$stmt = $con->prepare("SELECT PRECIO, NOMBRE FROM canchas WHERE _id = ? AND _id != 9");
$stmt->bind_param("i", $id_cancha);
$stmt->execute();
$cancha = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$cancha) {
    volver_reservas_cliente("La cancha seleccionada no esta disponible.");
}

$sql_solapados = "SELECT t._id
FROM turnos t
INNER JOIN canchas c ON t.id_CANCHA = c._id
WHERE VENTA = 0
AND (
    STR_TO_DATE(CONCAT(t.FECHA, ' ', t.HORA_INICIO), '%d-%m-%Y %H:%i') < STR_TO_DATE(?, '%d-%m-%Y %H:%i')
    AND
    STR_TO_DATE(
        CONCAT(
            CASE
                WHEN t.HORA_FIN IN ('00:00', '00:30', '01:00', '01:30', '02:00') AND t.HORA_INICIO NOT IN ('00:00', '00:30', '01:00', '01:30')
                    THEN DATE_FORMAT(DATE_ADD(STR_TO_DATE(t.FECHA, '%d-%m-%Y'), INTERVAL 1 DAY), '%d-%m-%Y')
                WHEN t.HORA_FIN = '24:00'
                    THEN DATE_FORMAT(DATE_ADD(STR_TO_DATE(t.FECHA, '%d-%m-%Y'), INTERVAL 1 DAY), '%d-%m-%Y')
                ELSE t.FECHA
            END,
            ' ',
            CASE WHEN t.HORA_FIN = '24:00' THEN '00:00' ELSE t.HORA_FIN END
        ),
        '%d-%m-%Y %H:%i'
    ) > STR_TO_DATE(?, '%d-%m-%Y %H:%i')
)
AND " . condicion_solapamiento_canchas_sql();

$stmt = $con->prepare($sql_solapados);
bind_parametros_solapamiento($stmt, $datetime_fin, $datetime_inicio, $id_cancha);
$stmt->execute();
$ocupado = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($ocupado) {
    volver_reservas_cliente("Ese horario ya esta ocupado. Prueba con otro turno.");
}

if (horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)) {
    volver_reservas_cliente("Ese horario esta bloqueado. Prueba con otro turno.");
}

$total_cancha = precio_total_reserva($con, $id_cancha, $cancha['PRECIO'], $fecha_reserva, $hora_inicio, $duracion);
$porcentaje_minimo_senia = porcentaje_minimo_senia($con, $id_cancha, $fecha_reserva, $hora_inicio);
$minimo_senia = minimo_senia_reserva($con, $total_cancha, $id_cancha, $fecha_reserva, $hora_inicio);
$terminos_titulo = $id_cancha === CANCHA_CUMPLE_ID
    ? 'Condiciones de reservas para cumplea&ntilde;os'
    : 'Condiciones de la reserva para turnos';
$terminos_reserva = terminos_reserva($id_cancha);

include 'header.php';
include 'cliente_navbar.php';
?>
<main class="container cliente-page py-4">
    <section class="cliente-section cliente-confirmacion">
        <div class="cliente-confirmacion-head">
            <div>
                <h1>Confirmar reserva</h1>
                <p>Revisa los datos antes de continuar con Mercado Pago.</p>
            </div>
            <a class="btn cliente-btn-secundario" href="cliente_reservas.php">Volver</a>
        </div>

        <div class="cliente-confirmacion-grid mt-4">
            <div class="cliente-confirmacion-dato">
                <span>Fecha</span>
                <strong><?php echo htmlspecialchars($fecha); ?></strong>
            </div>
            <div class="cliente-confirmacion-dato">
                <span>Horario</span>
                <strong><?php echo htmlspecialchars($hora_inicio . ' a ' . $hora_fin); ?></strong>
            </div>
            <div class="cliente-confirmacion-dato">
                <span>Cancha</span>
                <strong><?php echo htmlspecialchars(nombre_cancha_cliente($id_cancha, $cancha['NOMBRE'])); ?></strong>
            </div>
            <div class="cliente-confirmacion-dato">
                <span>Duracion</span>
                <strong><?php echo (int) $duracion; ?> hora<?php echo (int) $duracion === 1 ? '' : 's'; ?></strong>
            </div>
        </div>

        <form action="guardar_reserva_cliente.php" method="POST" class="cliente-confirmacion-pago mt-4" data-loading-form="true">
            <input type="hidden" name="fecha" value="<?php echo htmlspecialchars($fecha_reserva); ?>">
            <input type="hidden" name="fecha_reserva" value="<?php echo htmlspecialchars($fecha_reserva); ?>">
            <input type="hidden" name="hora_inicio" value="<?php echo htmlspecialchars($hora_inicio); ?>">
            <input type="hidden" name="duracion" value="<?php echo (int) $duracion; ?>">
            <input type="hidden" name="cancha" value="<?php echo (int) $id_cancha; ?>">

            <div class="cliente-confirmacion-total">
                <span>Total del turno</span>
                <strong>$<?php echo number_format((float) $total_cancha, 0, ',', '.'); ?></strong>
            </div>

            <div class="cliente-senia-control">
                <span class="cliente-senia-etiqueta">Se&ntilde;a a pagar ahora</span>
                <strong class="cliente-senia-monto">$<?php echo number_format((float) $minimo_senia, 0, ',', '.'); ?></strong>
                <div class="cliente-senia-limites">
                    <span>Importe fijo: <?php echo (int) round($porcentaje_minimo_senia * 100); ?>% del total</span>
                </div>
            </div>

            <div class="cliente-terminos-reserva">
                <div class="cliente-terminos-texto">
                    <strong><?php echo $terminos_titulo; ?></strong>
                    <ul>
                        <?php foreach ($terminos_reserva as $termino): ?>
                            <li><?php echo htmlspecialchars($termino, ENT_QUOTES, 'UTF-8'); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <label class="cliente-terminos-check mt-3" for="acepta_terminos_reserva">
                    <input
                        type="checkbox"
                        id="acepta_terminos_reserva"
                        name="acepta_terminos_reserva"
                        value="1"
                        required>
                    <span>Acepto los t&eacute;rminos y condiciones de la reserva.</span>
                </label>
            </div>

            <div class="cliente-confirmacion-acciones">
                <a class="btn cliente-btn-secundario" href="cliente_reservas.php">Cancelar</a>
                <button type="submit" class="btn btn-primary">Continuar a Mercado Pago</button>
            </div>
        </form>
    </section>
</main>
<?php include 'common_scripts.php'; ?>
<?php include 'cliente_form_utils.php'; ?>
</body>
</html>

