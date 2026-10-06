<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "Diagnostico La Toxica\n";
echo "====================\n\n";
echo "PHP: " . PHP_VERSION . "\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? '(no definido)') . "\n\n";

$archivos = [
    'config.php',
    'cliente_auth.php',
    'cliente_reservas.php',
    'header.php',
    'cliente_navbar.php',
    'msjs.php',
    'common_scripts.php',
    'cliente_form_utils.php',
    'css/bootstrap.min.css',
    'css/home.css',
    'vendor/autoload.php',
];

echo "Archivos requeridos:\n";
foreach ($archivos as $archivo) {
    $ruta = __DIR__ . '/' . $archivo;
    echo "- {$archivo}: " . (is_file($ruta) ? 'OK' : 'NO EXISTE') . "\n";
}

echo "\nExtensiones PHP:\n";
echo "- mysqli: " . (extension_loaded('mysqli') ? 'OK' : 'NO CARGADA') . "\n";
echo "- mysqlnd: " . (function_exists('mysqli_stmt_get_result') ? 'OK' : 'NO CARGADA') . "\n";

$config = __DIR__ . '/config.php';
if (!is_file($config)) {
    echo "\nNo se puede probar MySQL porque falta config.php.\n";
    exit;
}

echo "\nProbando config.php y MySQL:\n";
try {
    require $config;

    if (!isset($con) || !($con instanceof mysqli)) {
        echo "- Conexion: ERROR, config.php no dejo una conexion mysqli en \$con.\n";
        exit;
    }

    echo "- Conexion: OK\n";
    echo "- Base seleccionada: " . ($basededatos ?? '(no definida)') . "\n";

    $columnas_requeridas = [
        'clientes' => ['_id', 'NOMBRE', 'MAIL', 'TELEFONO', 'VISIBLE', 'CLAVE', 'EMAIL_VERIFICADO', 'CODIGO_VERIFICACION', 'CODIGO_EXPIRA', 'RECUPERACION_CODIGO', 'RECUPERACION_EXPIRA'],
        'canchas' => ['_id', 'NOMBRE', 'PRECIO'],
        'turnos' => ['_id', 'FECHA', 'HORA_INICIO', 'HORA_FIN', 'id_CANCHA', 'VENTA'],
        'ticket' => ['_id', 'id_TURNO', 'id_CLIENTE', 'FECHA', 'TOTAL_CANCHA', 'EXTRA', 'TOTAL_DETALLE', 'TOTAL', 'SENIA', 'MP_SENIA', 'ESTADO_RESERVA', 'PAGO_TRANSFERENCIA', 'PAGO_EFECTIVO'],
        'senias' => ['_id', 'MONTO', 'FECHA', 'id_CLIENTE', 'id_TURNO', 'EFECTIVO', 'TRANSFERENCIA', 'DEJA', 'RECIBE'],
    ];

    foreach ($columnas_requeridas as $tabla => $columnas) {
        $res = mysqli_query($con, "SHOW TABLES LIKE '" . mysqli_real_escape_string($con, $tabla) . "'");
        $existe = $res && mysqli_num_rows($res) > 0;
        echo "- Tabla {$tabla}: " . ($existe ? 'OK' : 'NO EXISTE') . "\n";

        if (!$existe) {
            continue;
        }

        $res_columnas = mysqli_query($con, "SHOW COLUMNS FROM `{$tabla}`");
        $existentes = [];
        while ($columna = mysqli_fetch_assoc($res_columnas)) {
            $existentes[] = $columna['Field'];
        }

        $faltantes = array_values(array_diff($columnas, $existentes));
        echo "  Columnas: " . (count($faltantes) === 0 ? 'OK' : 'FALTAN ' . implode(', ', $faltantes)) . "\n";
    }
} catch (Throwable $e) {
    echo "- ERROR: " . $e->getMessage() . "\n";
}
