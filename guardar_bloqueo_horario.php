<?php
require_once 'config.php';
require_once 'bloqueos_horarios.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: page_turnos.php");
    exit;
}

if (!usuario_interno_autenticado()) {
    header("Location: admin/");
    exit;
}

asegurar_tabla_bloqueos_horarios($con);

$inicio_input = $_POST['bloqueo_inicio'] ?? '';
$fin_input = $_POST['bloqueo_fin'] ?? '';
$id_cancha = ($_POST['bloqueo_cancha'] ?? '') === '' ? null : (int) $_POST['bloqueo_cancha'];
$motivo = trim($_POST['bloqueo_motivo'] ?? '');
$id_usuario = obtener_id_usuario_cookie();

$inicio_ts = strtotime($inicio_input);
$fin_ts = strtotime($fin_input);

if ($inicio_ts === false || $fin_ts === false || $inicio_ts >= $fin_ts) {
    header("Location: page_turnos.php?error=" . urlencode("Completa una franja horaria valida para bloquear."));
    exit;
}

$fecha_inicio = date('Y-m-d H:i:s', $inicio_ts);
$fecha_fin = date('Y-m-d H:i:s', $fin_ts);

$sql = "INSERT INTO bloqueos_horarios (FECHA_INICIO, FECHA_FIN, id_CANCHA, MOTIVO, id_USUARIO)
        VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($con, $sql);
mysqli_stmt_bind_param($stmt, "ssisi", $fecha_inicio, $fecha_fin, $id_cancha, $motivo, $id_usuario);

if (!mysqli_stmt_execute($stmt)) {
    header("Location: page_turnos.php?error=" . urlencode("No se pudo guardar el bloqueo: " . mysqli_error($con)));
    exit;
}

mysqli_stmt_close($stmt);
header("Location: page_turnos.php?success=" . urlencode("Horario bloqueado correctamente."));
exit;
?>
