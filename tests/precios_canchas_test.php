<?php
require_once __DIR__ . '/../precios_canchas.php';
$reglas = [
    ['dia_semana' => 1, 'inicio' => 780, 'fin' => 1020, 'precio' => 6000],
    ['dia_semana' => 1, 'inicio' => 1380, 'fin' => 1560, 'precio' => 8000],
];
$casos = [
    ['lunes siesta', '2026-10-05', '14:00', 1, false, 6000],
    ['otro día', '2026-10-06', '14:00', 1, false, 10000],
    ['cruce de tarifa', '2026-10-05', '16:30', 1, false, 8000],
    ['fin excluido', '2026-10-05', '17:00', 1, false, 10000],
    ['medianoche', '2026-10-05', '23:30', 2, false, 16000],
    ['madrugada del lunes', '2026-10-06', '00:30', 1, false, 8000],
    ['domingo anterior', '2026-10-05', '00:30', 1, false, 10000],
    ['media hora', '2026-10-05', '14:00', 1.5, false, 9000],
    ['paquete cumpleaños', '2026-10-05', '16:00', 3, true, 6000],
];
foreach ($casos as [$nombre, $fecha, $hora, $duracion, $paquete, $esperado]) {
    $actual = calcular_precio_turno($reglas, 10000, $fecha, $hora, $duracion, $paquete);
    if ($actual != $esperado) throw new RuntimeException("$nombre: $actual != $esperado");
}
if (calcular_precio_turno([], 10000, '2026-10-05', '14:00', 2) != 20000) {
    throw new RuntimeException('Precio base sin reglas');
}
echo "OK: 10 casos de precios.\n";
