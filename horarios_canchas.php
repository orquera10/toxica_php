<?php

const FRANJAS_HORARIAS_CANCHA = ['manana', 'tarde', 'noche'];

function asegurar_columna_horarios_cancha($con, $columna, $definicion)
{
    $resultado = mysqli_query($con, "SHOW COLUMNS FROM cancha_horarios LIKE '" . mysqli_real_escape_string($con, $columna) . "'");
    if (!$resultado) {
        return false;
    }

    if (mysqli_num_rows($resultado) > 0) {
        return true;
    }

    return (bool) mysqli_query($con, "ALTER TABLE cancha_horarios ADD COLUMN $columna $definicion");
}

function asegurar_tabla_horarios_canchas($con)
{
    static $conexiones_verificadas = [];
    $conexion_id = spl_object_id($con);
    if (isset($conexiones_verificadas[$conexion_id])) {
        return true;
    }

    $sql = "CREATE TABLE IF NOT EXISTS cancha_horarios (
        id_cancha INT NOT NULL,
        dia_semana TINYINT NOT NULL,
        habilitado TINYINT(1) NOT NULL DEFAULT 1,
        hora_apertura TIME NOT NULL DEFAULT '13:00:00',
        hora_cierre TIME NOT NULL DEFAULT '02:00:00',
        porcentaje_senia DECIMAL(5,2) NULL DEFAULT NULL,
        manana_habilitada TINYINT(1) NOT NULL DEFAULT 0,
        hora_apertura_manana TIME NOT NULL DEFAULT '07:00:00',
        hora_cierre_manana TIME NOT NULL DEFAULT '12:00:00',
        tarde_habilitada TINYINT(1) NOT NULL DEFAULT 1,
        hora_apertura_tarde TIME NOT NULL DEFAULT '12:00:00',
        hora_cierre_tarde TIME NOT NULL DEFAULT '20:00:00',
        noche_habilitada TINYINT(1) NOT NULL DEFAULT 1,
        hora_apertura_noche TIME NOT NULL DEFAULT '20:00:00',
        hora_cierre_noche TIME NOT NULL DEFAULT '02:00:00',
        franjas_version TINYINT NOT NULL DEFAULT 2,
        PRIMARY KEY (id_cancha, dia_semana)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

    if (!mysqli_query($con, $sql)) {
        return false;
    }

    $columna_franjas = mysqli_query($con, "SHOW COLUMNS FROM cancha_horarios LIKE 'manana_habilitada'");
    if (!$columna_franjas) {
        return false;
    }
    $migrar_horario_existente = mysqli_num_rows($columna_franjas) === 0;

    $columna_version = mysqli_query($con, "SHOW COLUMNS FROM cancha_horarios LIKE 'franjas_version'");
    if (!$columna_version) {
        return false;
    }
    $normalizar_horarios_heredados = mysqli_num_rows($columna_version) === 0;

    $columnas = [
        'porcentaje_senia' => "DECIMAL(5,2) NULL DEFAULT NULL AFTER hora_cierre",
        'manana_habilitada' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER porcentaje_senia",
        'hora_apertura_manana' => "TIME NOT NULL DEFAULT '07:00:00' AFTER manana_habilitada",
        'hora_cierre_manana' => "TIME NOT NULL DEFAULT '12:00:00' AFTER hora_apertura_manana",
        'tarde_habilitada' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER hora_cierre_manana",
        'hora_apertura_tarde' => "TIME NOT NULL DEFAULT '12:00:00' AFTER tarde_habilitada",
        'hora_cierre_tarde' => "TIME NOT NULL DEFAULT '20:00:00' AFTER hora_apertura_tarde",
        'noche_habilitada' => "TINYINT(1) NOT NULL DEFAULT 0 AFTER hora_cierre_tarde",
        'hora_apertura_noche' => "TIME NOT NULL DEFAULT '20:00:00' AFTER noche_habilitada",
        'hora_cierre_noche' => "TIME NOT NULL DEFAULT '02:00:00' AFTER hora_apertura_noche",
        'franjas_version' => "TINYINT NOT NULL DEFAULT 0 AFTER hora_cierre_noche",
    ];

    foreach ($columnas as $columna => $definicion) {
        if (!asegurar_columna_horarios_cancha($con, $columna, $definicion)) {
            return false;
        }
    }

    if ($migrar_horario_existente) {
        $sql_migracion = "UPDATE cancha_horarios
            SET tarde_habilitada = habilitado,
                hora_apertura_tarde = hora_apertura,
                hora_cierre_tarde = hora_cierre,
                manana_habilitada = 0,
                noche_habilitada = 0";
        if (!mysqli_query($con, $sql_migracion)) {
            return false;
        }
    }

    if ($normalizar_horarios_heredados) {
        $sql_normalizacion = "UPDATE cancha_horarios
            SET hora_cierre_tarde = '18:00:00',
                noche_habilitada = habilitado,
                hora_apertura_noche = '18:00:00',
                hora_cierre_noche = '02:00:00'
            WHERE franjas_version = 0
              AND tarde_habilitada = 1
              AND hora_apertura_tarde = '13:00:00'
              AND hora_cierre_tarde = '02:00:00'";
        if (!mysqli_query($con, $sql_normalizacion)) {
            return false;
        }

        if (
            !mysqli_query($con, "UPDATE cancha_horarios SET franjas_version = 1 WHERE franjas_version = 0")
            || !mysqli_query($con, "ALTER TABLE cancha_horarios MODIFY franjas_version TINYINT NOT NULL DEFAULT 1")
        ) {
            return false;
        }
    }

    $versiones_pendientes = mysqli_query($con, "SELECT 1 FROM cancha_horarios WHERE franjas_version < 2 LIMIT 1");
    if (!$versiones_pendientes) {
        return false;
    }
    if (mysqli_num_rows($versiones_pendientes) > 0) {
        $sql_predeterminados = "UPDATE cancha_horarios
            SET hora_apertura_tarde = '12:00:00',
                hora_cierre_tarde = '20:00:00',
                hora_apertura_noche = '20:00:00',
                hora_cierre_noche = '02:00:00'
            WHERE franjas_version < 2
              AND tarde_habilitada = 1
              AND hora_apertura_tarde = '13:00:00'
              AND hora_cierre_tarde = '18:00:00'
              AND noche_habilitada = 1
              AND hora_apertura_noche = '18:00:00'
              AND hora_cierre_noche = '02:00:00'";
        if (!mysqli_query($con, $sql_predeterminados)) {
            return false;
        }

        $sql_limites = "UPDATE cancha_horarios
            SET hora_apertura_manana = CASE
                    WHEN manana_habilitada = 0 AND hora_apertura_manana = '08:00:00' THEN '07:00:00'
                    WHEN hora_apertura_manana < '07:00:00' OR hora_apertura_manana >= '12:00:00' THEN '07:00:00'
                    ELSE hora_apertura_manana
                END,
                hora_cierre_manana = CASE
                    WHEN hora_cierre_manana <= '07:00:00' OR hora_cierre_manana > '12:00:00' THEN '12:00:00'
                    ELSE hora_cierre_manana
                END,
                hora_apertura_tarde = CASE
                    WHEN hora_apertura_tarde < '12:00:00' OR hora_apertura_tarde >= '20:00:00' THEN '12:00:00'
                    ELSE hora_apertura_tarde
                END,
                hora_cierre_tarde = CASE
                    WHEN hora_cierre_tarde <= '12:00:00' OR hora_cierre_tarde > '20:00:00' THEN '20:00:00'
                    ELSE hora_cierre_tarde
                END,
                hora_apertura_noche = CASE
                    WHEN hora_apertura_noche >= '02:00:00' AND hora_apertura_noche < '20:00:00' THEN '20:00:00'
                    ELSE hora_apertura_noche
                END,
                hora_cierre_noche = CASE
                    WHEN hora_cierre_noche > '02:00:00' AND hora_cierre_noche <= '20:00:00' THEN '02:00:00'
                    ELSE hora_cierre_noche
                END,
                franjas_version = 2
            WHERE franjas_version < 2";
        if (!mysqli_query($con, $sql_limites)) {
            return false;
        }

        if (!mysqli_query($con, "ALTER TABLE cancha_horarios MODIFY franjas_version TINYINT NOT NULL DEFAULT 2")) {
            return false;
        }
    }

    $conexiones_verificadas[$conexion_id] = true;
    return true;
}

function horario_predeterminado_cancha($id, $dia = null)
{
    $porcentaje = (int) $id === 8 ? 30 : (in_array((int) $dia, [2, 3, 4], true) ? 50 : 30);
    $es_promo = (int) $id === 11;

    return [
        'habilitado' => true,
        'apertura' => '13:00',
        'cierre' => $es_promo ? '17:00' : '02:00',
        'porcentaje_senia' => $porcentaje,
        'franjas' => [
            'manana' => ['habilitada' => false, 'apertura' => '07:00', 'cierre' => '12:00'],
            'tarde' => ['habilitada' => true, 'apertura' => $es_promo ? '13:00' : '12:00', 'cierre' => $es_promo ? '17:00' : '20:00'],
            'noche' => ['habilitada' => !$es_promo, 'apertura' => '20:00', 'cierre' => '02:00'],
        ],
    ];
}

function horarios_semanales_cancha($con, $id)
{
    $horarios = [];
    for ($dia = 1; $dia <= 7; $dia++) {
        $horarios[$dia] = horario_predeterminado_cancha($id, $dia);
    }

    if (!asegurar_tabla_horarios_canchas($con)) {
        return $horarios;
    }

    $sql = "SELECT
            dia_semana,
            habilitado,
            TIME_FORMAT(hora_apertura, '%H:%i') apertura,
            TIME_FORMAT(hora_cierre, '%H:%i') cierre,
            porcentaje_senia,
            manana_habilitada,
            TIME_FORMAT(hora_apertura_manana, '%H:%i') apertura_manana,
            TIME_FORMAT(hora_cierre_manana, '%H:%i') cierre_manana,
            tarde_habilitada,
            TIME_FORMAT(hora_apertura_tarde, '%H:%i') apertura_tarde,
            TIME_FORMAT(hora_cierre_tarde, '%H:%i') cierre_tarde,
            noche_habilitada,
            TIME_FORMAT(hora_apertura_noche, '%H:%i') apertura_noche,
            TIME_FORMAT(hora_cierre_noche, '%H:%i') cierre_noche
        FROM cancha_horarios
        WHERE id_cancha = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $resultado = $stmt->get_result();

    while ($fila = $resultado->fetch_assoc()) {
        $dia = (int) $fila['dia_semana'];
        $predeterminado = horario_predeterminado_cancha($id, $dia);
        $horarios[$dia] = [
            'habilitado' => (bool) $fila['habilitado'],
            'apertura' => $fila['apertura'],
            'cierre' => $fila['cierre'],
            'porcentaje_senia' => $fila['porcentaje_senia'] === null
                ? $predeterminado['porcentaje_senia']
                : (float) $fila['porcentaje_senia'],
            'franjas' => [
                'manana' => [
                    'habilitada' => (bool) $fila['manana_habilitada'],
                    'apertura' => $fila['apertura_manana'],
                    'cierre' => $fila['cierre_manana'],
                ],
                'tarde' => [
                    'habilitada' => (bool) $fila['tarde_habilitada'],
                    'apertura' => $fila['apertura_tarde'],
                    'cierre' => $fila['cierre_tarde'],
                ],
                'noche' => [
                    'habilitada' => (bool) $fila['noche_habilitada'],
                    'apertura' => $fila['apertura_noche'],
                    'cierre' => $fila['cierre_noche'],
                ],
            ],
        ];
    }

    $stmt->close();
    return $horarios;
}

function horario_cancha_para_fecha($con, $id, $fecha)
{
    $timestamp = strtotime($fecha);
    if ($timestamp === false) {
        return null;
    }

    $horarios = horarios_semanales_cancha($con, $id);
    return $horarios[(int) date('N', $timestamp)] ?? null;
}

function hora_a_minutos_operativos($hora, $apertura)
{
    if (!preg_match('/^(\d{2}):(\d{2})$/', substr((string) $hora, 0, 5), $partes)) {
        return null;
    }

    $minutos = (int) $partes[1] * 60 + (int) $partes[2];
    if ($minutos < $apertura) {
        $minutos += 1440;
    }

    return $minutos;
}

function limites_franjas_horario_cancha($con, $id, $fecha)
{
    $horario = horario_cancha_para_fecha($con, $id, $fecha);
    if (!$horario || !$horario['habilitado']) {
        return [];
    }

    $limites = [];
    foreach (FRANJAS_HORARIAS_CANCHA as $nombre) {
        $franja = $horario['franjas'][$nombre] ?? null;
        if (!$franja || !$franja['habilitada']) {
            continue;
        }

        $apertura = hora_a_minutos_operativos($franja['apertura'], 0);
        $cierre = hora_a_minutos_operativos($franja['cierre'], $apertura ?? 0);
        if ($apertura === null || $cierre === null || $cierre <= $apertura) {
            continue;
        }

        $limites[] = [
            'nombre' => $nombre,
            'apertura' => $apertura,
            'cierre' => $cierre,
        ];
    }

    usort($limites, function ($a, $b) {
        return $a['apertura'] <=> $b['apertura'];
    });

    $limites_combinados = [];
    foreach ($limites as $franja) {
        $ultimo_indice = count($limites_combinados) - 1;
        if (
            $ultimo_indice < 0
            || $franja['apertura'] > $limites_combinados[$ultimo_indice]['cierre']
        ) {
            $limites_combinados[] = $franja;
            continue;
        }

        $limites_combinados[$ultimo_indice]['cierre'] = max(
            $limites_combinados[$ultimo_indice]['cierre'],
            $franja['cierre']
        );
        $limites_combinados[$ultimo_indice]['nombre'] .= '+' . $franja['nombre'];
    }

    return $limites_combinados;
}

function limites_horario_cancha($con, $id, $fecha)
{
    $franjas = limites_franjas_horario_cancha($con, $id, $fecha);
    return $franjas[0] ?? null;
}

function reserva_en_horario_habil_cancha($con, $id, $fecha, $hora, $duracion)
{
    foreach (limites_franjas_horario_cancha($con, $id, $fecha) as $franja) {
        $inicio = hora_a_minutos_operativos($hora, $franja['apertura']);
        if (
            $inicio !== null
            && $inicio >= $franja['apertura']
            && $inicio + (int) $duracion * 60 <= $franja['cierre']
        ) {
            return true;
        }
    }

    return false;
}
