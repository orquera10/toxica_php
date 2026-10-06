<?php

function asegurar_tabla_bloqueos_horarios($con)
{
    $sql = "CREATE TABLE IF NOT EXISTS bloqueos_horarios (
        _id INT AUTO_INCREMENT PRIMARY KEY,
        FECHA_INICIO DATETIME NOT NULL,
        FECHA_FIN DATETIME NOT NULL,
        id_CANCHA INT NULL,
        MOTIVO VARCHAR(255) NULL,
        id_USUARIO INT NULL,
        FECHA_CREACION TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_bloqueos_fecha (FECHA_INICIO, FECHA_FIN),
        INDEX idx_bloqueos_cancha (id_CANCHA)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    mysqli_query($con, $sql);
}

function condicion_solapamiento_bloqueos_sql($alias = 'b')
{
    return "(
        {$alias}.id_CANCHA IS NULL
        OR {$alias}.id_CANCHA = ?
        OR (? IN (5, 6) AND {$alias}.id_CANCHA IN (7, 8, 10, 11))
        OR (? IN (7, 8, 10, 11) AND {$alias}.id_CANCHA IN (5, 6, 7, 8, 10, 11))
    )";
}

function horario_bloqueado($con, $datetime_inicio, $datetime_fin, $id_cancha)
{
    asegurar_tabla_bloqueos_horarios($con);

    $sql = "SELECT _id
            FROM bloqueos_horarios b
            WHERE b.FECHA_INICIO < STR_TO_DATE(?, '%d-%m-%Y %H:%i')
            AND b.FECHA_FIN > STR_TO_DATE(?, '%d-%m-%Y %H:%i')
            AND " . condicion_solapamiento_bloqueos_sql('b') . "
            LIMIT 1";

    $stmt = mysqli_prepare($con, $sql);
    mysqli_stmt_bind_param($stmt, "ssiii", $datetime_fin, $datetime_inicio, $id_cancha, $id_cancha, $id_cancha);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    $bloqueado = mysqli_num_rows($resultado) > 0;
    mysqli_stmt_close($stmt);

    return $bloqueado;
}

function obtener_id_usuario_cookie()
{
    if (!isset($_COOKIE['ID_USUARIO'])) {
        return null;
    }

    $partes = explode('|', urldecode($_COOKIE['ID_USUARIO']));
    return !empty($partes[0]) ? (int) $partes[0] : null;
}

function usuario_interno_autenticado()
{
    if (!isset($_COOKIE['TIPO'])) {
        return false;
    }

    $partes = explode('|', $_COOKIE['TIPO']);
    if (count($partes) !== 2) {
        return false;
    }

    $tipo = $partes[0];
    $firma = $partes[1];
    $firma_valida = hash_hmac('sha256', $tipo, 'clave_secreta');

    return hash_equals($firma, $firma_valida) && in_array($tipo, ['admin', 'user'], true);
}
?>
