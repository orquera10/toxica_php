<?php

function asegurar_tabla_precios_canchas($con)
{
    static $listas = [];
    $id = spl_object_id($con);
    if (isset($listas[$id])) return;
    if (!$con->query("CREATE TABLE IF NOT EXISTS cancha_precios_horarios (
        id_cancha INT NOT NULL, dia_semana TINYINT NOT NULL,
        inicio SMALLINT NOT NULL, fin SMALLINT NOT NULL,
        precio DECIMAL(12,2) NOT NULL,
        PRIMARY KEY (id_cancha, dia_semana, inicio)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")) {
        throw new RuntimeException('No se pudo preparar la configuración de precios.');
    }
    $listas[$id] = true;
}

function precios_horarios_cancha($con, $id)
{
    asegurar_tabla_precios_canchas($con);
    $stmt = $con->prepare('SELECT dia_semana, inicio, fin, precio FROM cancha_precios_horarios WHERE id_cancha = ? ORDER BY dia_semana, inicio');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $filas;
}

// Las 00:00–02:00 pertenecen a la jornada del día anterior, como los horarios.
function calcular_precio_turno($reglas, $base, $fecha, $hora, $duracion, $paquete = false)
{
    $inicio = new DateTimeImmutable($fecha . ' ' . $hora);
    $total = 0;
    $minutos = (int) round($duracion * 60);
    for ($i = 0; $i < ($paquete ? 1 : $minutos); $i++) {
        $instante = $inicio->modify('+' . $i . ' minutes');
        $minuto = (int) $instante->format('H') * 60 + (int) $instante->format('i');
        $dia = (int) $instante->format('N');
        if ($minuto < 120) {
            $minuto += 1440;
            $dia = $dia === 1 ? 7 : $dia - 1;
        }
        $precio = (float) $base;
        foreach ($reglas as $regla) {
            if ((int) $regla['dia_semana'] === $dia && $minuto >= $regla['inicio'] && $minuto < $regla['fin']) {
                $precio = (float) $regla['precio'];
                break;
            }
        }
        $total += $paquete ? $precio : $precio / 60;
    }
    return round($total, 2);
}

function precio_total_reserva($con, $id, $base, $fecha, $hora, $duracion)
{
    return calcular_precio_turno(precios_horarios_cancha($con, $id), $base, $fecha, $hora, $duracion, (int) $id === 8);
}
