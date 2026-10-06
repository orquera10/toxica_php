<?php
date_default_timezone_set("America/Bogota");
setlocale(LC_ALL, "es_ES");

require("config.php");
require_once("reglas_reservas.php");
require_once("bloqueos_horarios.php");

// Obtener ID de usuario desde la cookie firmada (ID_USUARIO)
$id_usuario = null;
if (isset($_COOKIE['ID_USUARIO'])) {
    $cookieUsuario = $_COOKIE['ID_USUARIO'];
    // Separar valor y firma, tomar el ID (primera parte)
    $partes = explode('|', urldecode($cookieUsuario));
    if (!empty($partes[0])) {
        $id_usuario = intval($partes[0]);
    }
}

// Verificar si se ha enviado el cliente_id y si no está vacío
if (!isset($_REQUEST['cliente_id']) || empty($_REQUEST['cliente_id'])) {
    // Si no se ha proporcionado un cliente_id válido, redirigir con un mensaje de error
    header("Location:page_turnos.php?error=El nombre del cliente no se ha proporcionado correctamente");
    exit; // Terminar la ejecución del script
}
$cliente_id = intval($_REQUEST['cliente_id']);
$id_cancha = intval($_REQUEST["canchas"]);

$datetime_inicio = $_POST['datetime_inicio'];
$datetime_fin = $_POST['datetime_fin'];

// Convertir a formato datetime
$start = date('d-m-Y H:i:s', strtotime($datetime_inicio));
$end = date('d-m-Y H:i:s', strtotime($datetime_fin));

// Solo la fecha de $start
$fecha = date('d-m-Y', strtotime($start));


$hora_inicio = date('H:i', strtotime($start));
$hora_fin = date('H:i', strtotime($end));

if (!horario_en_intervalo_30($hora_inicio) || !horario_en_intervalo_30($hora_fin)) {
    header("Location: page_turnos.php?error=" . urlencode("Los turnos deben comenzar y terminar en horarios cada 30 minutos."));
    exit;
}

$extra = isset($_POST['extra_money']) ? $_POST['extra_money'] : 0;

// Definir el color del evento basado en el ID de la cancha
$color_evento = match ($id_cancha) {
    7 => "#FCC30C", // Púrpura para cancha 1 y 2
    5 => "#4ED8E1", // Verde para Cancha 1
    6 => "#8FE14E", // Azul para Cancha 2
    8 => "#EA7BF3", // Naranja para Cumpleaños
    10 => "#FFFFFF", // Blanco para Escuelita
    11 => "#98A2FA", // Azul claro para Promoción
    default => "#4E9BE1", // Azul predeterminado
};


// Ajustar la hora final si id_cancha es 8 y sumar 3 horas a la hora_inicio
if ($id_cancha == 8) {
    // Verificar si la hora de inicio es mayor a las 22:00
    if (!reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, 3, $fecha)) {
        header("Location: page_turnos.php?error=" . urlencode(mensaje_horario_cumpleanios()));
        exit;
    }

    // Sumar 3 horas a la hora de inicio para obtener la hora de fin
    $end = date('d-m-Y H:i:s', strtotime($start . ' +3 hours'));

    // Extraer solo las horas para visualización
    $hora_inicio = date('H:i', strtotime($start));
    $hora_fin = date('H:i', strtotime($end));


} else {
    // Validar que la hora de inicio sea menor que la de fin
    if (strtotime($start) >= strtotime($end)) {
        $mensaje_error = "La hora de inicio ($hora_inicio) es mayor o igual que la de finalización ($hora_fin)";
        header("Location: page_turnos.php?error=" . urlencode($mensaje_error));
        exit;
    }

    if ((strtotime($end) - strtotime($start)) < 3600) {
        header("Location: page_turnos.php?error=" . urlencode("La reserva minima es de una hora."));
        exit;
    }

    $duracion_horas = (strtotime($end) - strtotime($start)) / 3600;
    if (!reserva_dentro_del_horario_cancha($id_cancha, $hora_inicio, $duracion_horas, $fecha)) {
        $mensaje_horario = "La cancha está cerrada o el turno queda fuera del horario hábil configurado para ese día.";
        header("Location: page_turnos.php?error=" . urlencode($mensaje_horario));
        exit;
    }
}




$datetime_inicio = "$fecha $hora_inicio";

// Si la hora_fin está entre 00:00 y 02:00 y la hora_inicio no lo está, sumamos un día
if (
    in_array($hora_fin, ['00:00', '00:30', '01:00', '01:30', '02:00']) &&
    !in_array($hora_inicio, ['00:00', '00:30', '01:00', '01:30'])
) {
    $fecha_fin = date('d-m-Y', strtotime($fecha . ' +1 day'));
} else {
    $fecha_fin = $fecha;
}

$datetime_fin = "$fecha_fin $hora_fin";

if (horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)) {
    header("Location: page_turnos.php?error=" . urlencode("El horario seleccionado esta bloqueado para reservas."));
    exit;
}



$sql = "SELECT t.* 
FROM turnos t
INNER JOIN canchas c ON t.id_CANCHA = c._id
WHERE (
    STR_TO_DATE(CONCAT(t.FECHA, ' ', t.HORA_INICIO), '%d-%m-%Y %H:%i') < STR_TO_DATE('$datetime_fin', '%d-%m-%Y %H:%i')
    AND 
    STR_TO_DATE(
        CONCAT(
            CASE 
                -- Si HORA_FIN es madrugada y HORA_INICIO no lo es, sumamos un día
                WHEN t.HORA_FIN IN ('00:00', '00:30', '01:00', '01:30', '02:00') AND t.HORA_INICIO NOT IN ('00:00', '00:30', '01:00', '01:30')
                    THEN DATE_FORMAT(DATE_ADD(STR_TO_DATE(t.FECHA, '%d-%m-%Y'), INTERVAL 1 DAY), '%d-%m-%Y')
                -- Si es 24:00, también sumamos un día
                WHEN t.HORA_FIN = '24:00' 
                    THEN DATE_FORMAT(DATE_ADD(STR_TO_DATE(t.FECHA, '%d-%m-%Y'), INTERVAL 1 DAY), '%d-%m-%Y')
                -- En cualquier otro caso, usamos la fecha original
                ELSE t.FECHA
            END,
            ' ',
            CASE 
                WHEN t.HORA_FIN = '24:00' THEN '00:00'  -- Cambiar 24:00 a 00:00
                ELSE t.HORA_FIN
            END
        ), 
        '%d-%m-%Y %H:%i'
    ) > STR_TO_DATE('$datetime_inicio', '%d-%m-%Y %H:%i')
)
AND (
    t.id_CANCHA = $id_cancha 
    OR 
    (c._id IN (5, 6, 8, 10, 11) AND $id_cancha = 7)
    OR 
    (c._id IN (5, 6, 7, 10, 11) AND $id_cancha = 8)
    OR 
    (c._id IN (5, 6, 7, 8, 11) AND $id_cancha = 10)
    OR 
    (c._id IN (5, 6, 7, 8, 10) AND $id_cancha = 11)
    OR 
    (c._id IN (7, 8, 10, 11) AND $id_cancha NOT IN (7, 8, 10, 11))
)";








// Ejecutar consulta
$resultado = mysqli_query($con, $sql);
$cantidad_solapados = mysqli_num_rows($resultado);
if ($cantidad_solapados > 0) {
    // Formatear mensaje de error incluyendo fecha y hora
    $mensaje_error = "El nuevo turno ($hora_inicio → $hora_fin) se solapa con $cantidad_solapados turno(s) existente(s) en ese horario.";

    // Redirigir con mensaje codificado
    header("Location: page_turnos.php?error=" . urlencode($mensaje_error));
    exit; // Terminar la ejecución del script
}


// Preparar la consulta SQL para obtener el precio de la cancha con el ID dado
$sql = "SELECT PRECIO FROM canchas WHERE _id = $id_cancha";
// Ejecutar la consulta SQL
$resultado = mysqli_query($con, $sql);
$fila = mysqli_fetch_assoc($resultado);
$precio_cancha = $fila['PRECIO'];

$total_cancha = precio_total_reserva($con, $id_cancha, $precio_cancha, $fecha, $hora_inicio, (strtotime($end) - strtotime($start)) / 3600);

// Obtener la opción seleccionada para repetir el evento
$repetir = isset($_POST['repetir']) ? $_POST['repetir'] : '';

// Si no se selecciona ninguna opción para repetir el evento, insertar el turno único
if (empty($repetir)) {
    // Insertar el turno único en la base de datos con la misma información que el turno original
    $sql_insert_turno = "INSERT INTO turnos (
        HORA_INICIO,
        HORA_FIN,
        COLOR,
        FECHA,
        id_CANCHA,
        FINALIZADO,
        VENTA,
        id_USUARIO
    ) VALUES (
        '" . $hora_inicio . "',
        '" . $hora_fin . "',
        '" . $color_evento . "',
        '" . $fecha . "',
        '" . $id_cancha . "',
        0,
        0,
        " . ($id_usuario !== null ? "'" . $id_usuario . "'" : "NULL") . "
    )";

    $resultado_insert_turno = mysqli_query($con, $sql_insert_turno);

    // Verificar si la inserción fue exitosa
    if (!$resultado_insert_turno) {
        // Si hubo un error al insertar el turno, redirigir con un mensaje de error
        header("Location:page_turnos.php?error=Error al insertar el turno: " . mysqli_error($con));
        exit; // Terminar la ejecución del script
    }

    // Obtener el ID del último turno insertado
    $id_turno = mysqli_insert_id($con);

    // Obtener la fecha del turno para el detalle
    $fecha_actual = date('d-m-Y', strtotime($fecha));

    $total = $total_cancha + intval($extra);

    // Insertar el ticket para el turno con el mismo total y fecha que el turno original
    $sql_insert_ticket = "INSERT INTO ticket (
        id_TURNO,
        id_CLIENTE,
        FECHA,
        TOTAL_CANCHA,
        EXTRA,
        TOTAL_DETALLE,
        TOTAL,
        PAGO_TRANSFERENCIA,
        PAGO_EFECTIVO
    ) VALUES (
        '$id_turno',
        '$cliente_id',
        '$fecha_actual',
        '$total_cancha',
        '$extra',
        0,
        '$total',
        0,
        0
    )";

    $resultado_insert_ticket = mysqli_query($con, $sql_insert_ticket);

    // Verificar si la inserción fue exitosa
    if (!$resultado_insert_ticket) {
        // Si hubo un error al insertar el ticket, redirigir con un mensaje de error
        header("Location:page_turnos.php?error=Error al insertar el ticket para el turno: " . mysqli_error($con));
        exit; // Terminar la ejecución del script
    }

    // Redirigir a la página de turnos con un mensaje de éxito
    header("Location:page_turnos.php?e");
    exit; // Terminar la ejecución del script
}

// Inicializar la fecha de inicio y la fecha de fin para la repetición
$fecha_inicio_repetir = '';
$fecha_fin_repetir = '';

// Calcular la fecha de inicio y la fecha de fin para la repetición según la opción seleccionada
switch ($repetir) {
    case 'unMes':
        // La fecha de inicio para repetir es el mismo día que el turno original
        $fecha_inicio_repetir = date('Y-m-d', strtotime($fecha));

        // La fecha de fin para repetir es 28 días después del turno original (4 semanas)
        $fecha_fin_repetir = date('Y-m-d', strtotime('+28 days', strtotime($fecha)));
        break;

    case 'tresMeses':
        // La fecha de inicio para repetir es el mismo día que el turno original
        $fecha_inicio_repetir = date('Y-m-d', strtotime($fecha));

        // La fecha de fin para repetir es 84 días después del turno original (12 semanas)
        $fecha_fin_repetir = date('Y-m-d', strtotime('+84 days', strtotime($fecha)));
        break;

    case 'seisMeses':
        // La fecha de inicio para repetir es el mismo día que el turno original
        $fecha_inicio_repetir = date('Y-m-d', strtotime($fecha));

        // La fecha de fin para repetir es 168 días después del turno original (24 semanas)
        $fecha_fin_repetir = date('Y-m-d', strtotime('+168 days', strtotime($fecha)));
        break;

    default:
        // Si no se selecciona ninguna opción válida, redirigir con un mensaje de error
        header("Location:page_turnos.php?error=La opción de repetición seleccionada no es válida");
        exit; // Terminar la ejecución del script
}

// Insertar los turnos repetidos en la base de datos
// Iterar sobre las fechas de inicio y fin y crear los turnos repetidos para cada fecha
for ($fecha_actual = $fecha_inicio_repetir; $fecha_actual <= $fecha_fin_repetir; $fecha_actual = date('Y-m-d', strtotime('+1 week', strtotime($fecha_actual)))) {
    // Insertar el turno repetido en la base de datos con la misma información que el turno original
    $fecha_actual_formateada = date('d-m-Y', strtotime($fecha_actual));
    $fecha_fin_repetido = $fecha_actual_formateada;

    if (
        in_array($hora_fin, ['00:00', '00:30', '01:00', '01:30', '02:00']) &&
        !in_array($hora_inicio, ['00:00', '00:30', '01:00', '01:30'])
    ) {
        $fecha_fin_repetido = date('d-m-Y', strtotime($fecha_actual . ' +1 day'));
    }

    if (horario_bloqueado($con, $fecha_actual_formateada . ' ' . $hora_inicio, $fecha_fin_repetido . ' ' . $hora_fin, $id_cancha)) {
        header("Location:page_turnos.php?error=" . urlencode("No se insertaron los turnos repetidos porque una fecha cae en un horario bloqueado."));
        exit;
    }

    $sql_insert_turno_repetido = "INSERT INTO turnos (
        HORA_INICIO,
        HORA_FIN,
        COLOR,
        FECHA,
        id_CANCHA,
        FINALIZADO,
        VENTA,
        id_USUARIO
    ) VALUES (
        '" . $hora_inicio . "',
        '" . $hora_fin . "',
        '" . $color_evento . "',
        '" . $fecha_actual_formateada . "',
        '" . $id_cancha . "',
        0,
        0,
        " . ($id_usuario !== null ? "'" . $id_usuario . "'" : "NULL") . "
    )";

    $resultado_insert_turno_repetido = mysqli_query($con, $sql_insert_turno_repetido);

    // Verificar si la inserción fue exitosa
    if (!$resultado_insert_turno_repetido) {
        // Si hubo un error al insertar el turno repetido, redirigir con un mensaje de error
        header("Location:page_turnos.php?error=Error al insertar el turno repetido: " . mysqli_error($con));
        exit; // Terminar la ejecución del script
    }

    // Obtener el ID del turno repetido insertado
    $id_turno_repetido = mysqli_insert_id($con);

    // Insertar el ticket para el turno repetido con el mismo total y fecha que el turno original
    $total_cancha = precio_total_reserva($con, $id_cancha, $precio_cancha, $fecha_actual_formateada, $hora_inicio, (strtotime($end) - strtotime($start)) / 3600);
    $sql_insert_ticket_repetido = "INSERT INTO ticket (
        id_TURNO,
        id_CLIENTE,
        FECHA,
        TOTAL_CANCHA,
        EXTRA,
        TOTAL_DETALLE,
        TOTAL,
        PAGO_TRANSFERENCIA,
        PAGO_EFECTIVO
    ) VALUES (
        '$id_turno_repetido',
        '$cliente_id',
        '" . $fecha_actual_formateada . "',
        '$total_cancha',
        '$extra',
        0,
        '$total_cancha',
        0,
        0
    )";

    $resultado_insert_ticket_repetido = mysqli_query($con, $sql_insert_ticket_repetido);

    // Verificar si la inserción fue exitosa
    if (!$resultado_insert_ticket_repetido) {
        // Si hubo un error al insertar el ticket para el turno repetido, redirigir con un mensaje de error
        header("Location:page_turnos.php?error=Error al insertar el ticket para el turno repetido: " . mysqli_error($con));
        exit; // Terminar la ejecución del script
    }
}

// Redirigir a la página de turnos con un mensaje de éxito
header("Location:page_turnos.php?success=Los eventos repetidos se insertaron correctamente.");
exit; // Terminar la ejecución del script

?>
