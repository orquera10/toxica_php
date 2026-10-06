<?php
/**
 * API administrativa de solo lectura.
 *
 * Autenticacion:
 *   Authorization: Bearer <ADMIN_API_KEY>
 *   X-Admin-API-Key: <ADMIN_API_KEY>
 *
 * Endpoints:
 *   GET ?action=turnos&fecha=YYYY-MM-DD
 *   GET ?action=informe_diario&fecha=YYYY-MM-DD
 *   GET ?action=informe_mensual&mes=YYYY-MM
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');

function admin_api_responder($status, $data)
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function admin_api_header($nombre)
{
    $clave = 'HTTP_' . strtoupper(str_replace('-', '_', $nombre));
    return trim((string) ($_SERVER[$clave] ?? ''));
}

function admin_api_autorizar()
{
    global $admin_api_key;

    if ($admin_api_key === '') {
        admin_api_responder(503, [
            'success' => false,
            'error' => 'admin_api_not_configured',
            'message' => 'La API administrativa no esta configurada.',
        ]);
    }

    $clave = admin_api_header('X-Admin-API-Key');
    $authorization = admin_api_header('Authorization');

    if ($clave === '' && stripos($authorization, 'Bearer ') === 0) {
        $clave = trim(substr($authorization, 7));
    }

    if ($clave === '' || !hash_equals((string) $admin_api_key, $clave)) {
        admin_api_responder(401, [
            'success' => false,
            'error' => 'unauthorized',
            'message' => 'Credencial administrativa invalida.',
        ]);
    }
}

function admin_api_validar_fecha($fecha)
{
    $fecha = trim((string) $fecha);
    $objeto = DateTime::createFromFormat('!Y-m-d', $fecha);
    $errores = DateTime::getLastErrors();

    if (!$objeto || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
        return null;
    }

    return $objeto->format('Y-m-d') === $fecha ? $objeto : null;
}

function admin_api_validar_mes($mes)
{
    $mes = trim((string) $mes);
    $objeto = DateTime::createFromFormat('!Y-m', $mes);
    $errores = DateTime::getLastErrors();

    if (!$objeto || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))) {
        return null;
    }

    return $objeto->format('Y-m') === $mes ? $objeto : null;
}

function admin_api_preparar($sql)
{
    global $con;
    $stmt = mysqli_prepare($con, $sql);
    if (!$stmt) {
        throw new RuntimeException('No se pudo preparar la consulta administrativa.');
    }
    return $stmt;
}

function admin_api_filas($stmt)
{
    if (!mysqli_stmt_execute($stmt)) {
        throw new RuntimeException('No se pudo ejecutar la consulta administrativa.');
    }

    $resultado = mysqli_stmt_get_result($stmt);
    if (!$resultado) {
        throw new RuntimeException('No se pudo leer el resultado administrativo.');
    }

    return mysqli_fetch_all($resultado, MYSQLI_ASSOC);
}

function admin_api_numero($valor)
{
    return round((float) ($valor ?? 0), 2);
}

function admin_api_turnos($fecha)
{
    $fecha_objeto = admin_api_validar_fecha($fecha);
    if (!$fecha_objeto) {
        admin_api_responder(400, [
            'success' => false,
            'error' => 'invalid_date',
            'message' => 'La fecha debe tener el formato YYYY-MM-DD.',
        ]);
    }

    $fecha_db = $fecha_objeto->format('d-m-Y');
    $stmt = admin_api_preparar("SELECT
            t._id AS turno_id,
            t.FECHA AS fecha,
            t.HORA_INICIO AS hora_inicio,
            t.HORA_FIN AS hora_fin,
            t.FINALIZADO AS finalizado,
            cn._id AS cancha_id,
            cn.NOMBRE AS cancha,
            cl._id AS cliente_id,
            cl.NOMBRE AS cliente,
            cl.TELEFONO AS telefono,
            tk._id AS ticket_id,
            tk.ESTADO_RESERVA AS estado_reserva,
            tk.SENIA AS senia,
            tk.MP_SENIA AS mp_senia,
            tk.TOTAL_CANCHA AS total_cancha,
            tk.EXTRA AS extra,
            tk.TOTAL_DETALLE AS total_productos,
            tk.TOTAL AS saldo_pendiente,
            COALESCE(u.NOMBRE, u.USUARIO) AS creado_por
        FROM turnos t
        INNER JOIN canchas cn ON cn._id = t.id_CANCHA
        LEFT JOIN ticket tk ON tk.id_TURNO = t._id
        LEFT JOIN clientes cl ON cl._id = tk.id_CLIENTE
        LEFT JOIN usuarios u ON u._id = t.id_USUARIO
        WHERE t.FECHA = ? AND t.VENTA = 0
        ORDER BY STR_TO_DATE(t.HORA_INICIO, '%H:%i'), cn.NOMBRE");
    mysqli_stmt_bind_param($stmt, 's', $fecha_db);
    $filas = admin_api_filas($stmt);

    $turnos = [];
    foreach ($filas as $fila) {
        $senia = admin_api_numero($fila['senia']);
        if ($senia <= 0) {
            $senia = admin_api_numero($fila['mp_senia']);
        }

        $turnos[] = [
            'id' => (int) $fila['turno_id'],
            'ticket_id' => $fila['ticket_id'] !== null ? (int) $fila['ticket_id'] : null,
            'fecha' => $fila['fecha'],
            'hora_inicio' => $fila['hora_inicio'],
            'hora_fin' => $fila['hora_fin'],
            'cancha' => [
                'id' => (int) $fila['cancha_id'],
                'nombre' => $fila['cancha'],
            ],
            'cliente' => $fila['cliente_id'] !== null ? [
                'id' => (int) $fila['cliente_id'],
                'nombre' => $fila['cliente'],
                'telefono' => $fila['telefono'],
            ] : null,
            'estado' => $fila['estado_reserva'] ?: ($fila['finalizado'] ? 'finalizado' : 'registrado'),
            'finalizado' => (bool) $fila['finalizado'],
            'senia' => $senia,
            'total_cancha' => admin_api_numero($fila['total_cancha']),
            'extra' => admin_api_numero($fila['extra']),
            'total_productos' => admin_api_numero($fila['total_productos']),
            'saldo_pendiente' => admin_api_numero($fila['saldo_pendiente']),
            'creado_por' => $fila['creado_por'],
        ];
    }

    admin_api_responder(200, [
        'success' => true,
        'fecha' => $fecha_objeto->format('Y-m-d'),
        'cantidad' => count($turnos),
        'turnos' => $turnos,
    ]);
}

function admin_api_informe_diario($fecha)
{
    $fecha_objeto = admin_api_validar_fecha($fecha);
    if (!$fecha_objeto) {
        admin_api_responder(400, [
            'success' => false,
            'error' => 'invalid_date',
            'message' => 'La fecha debe tener el formato YYYY-MM-DD.',
        ]);
    }

    $desde_objeto = clone $fecha_objeto;
    $desde_objeto->setTime(4, 1, 0);
    $hasta_objeto = clone $fecha_objeto;
    $hasta_objeto->modify('+1 day')->setTime(4, 0, 0);
    $desde = $desde_objeto->format('d-m-Y H:i');
    $hasta = $hasta_objeto->format('d-m-Y H:i');

    $stmt = admin_api_preparar("SELECT
            tk._id AS ticket_id,
            t._id AS turno_id,
            cl.NOMBRE AS cliente,
            cn.NOMBRE AS cancha,
            t.FECHA AS fecha_turno,
            t.HORA_INICIO AS hora_inicio,
            t.HORA_FIN AS hora_fin,
            tk.FECHA AS fecha_pago,
            tk.SENIA AS senia,
            tk.TOTAL_CANCHA AS total_cancha,
            tk.EXTRA AS extra,
            tk.TOTAL_DETALLE AS total_productos,
            tk.TOTAL AS saldo_cobrado,
            tk.PAGO_EFECTIVO AS efectivo,
            tk.PAGO_TRANSFERENCIA AS transferencia
        FROM turnos t
        INNER JOIN ticket tk ON tk.id_TURNO = t._id
        INNER JOIN clientes cl ON cl._id = tk.id_CLIENTE
        INNER JOIN canchas cn ON cn._id = t.id_CANCHA
        WHERE STR_TO_DATE(tk.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE(?, '%d-%m-%Y %H:%i') AND STR_TO_DATE(?, '%d-%m-%Y %H:%i')
          AND t.FINALIZADO = 1
        ORDER BY STR_TO_DATE(tk.FECHA, '%d-%m-%Y %H:%i')");
    mysqli_stmt_bind_param($stmt, 'ss', $desde, $hasta);
    $ventas_db = admin_api_filas($stmt);

    $ventas = [];
    $totales_ventas = [
        'senias_aplicadas' => 0.0,
        'cancha' => 0.0,
        'extras' => 0.0,
        'productos' => 0.0,
        'facturado' => 0.0,
        'efectivo' => 0.0,
        'transferencia' => 0.0,
        'cobrado_al_finalizar' => 0.0,
    ];
    foreach ($ventas_db as $fila) {
        $senia = admin_api_numero($fila['senia']);
        $saldo = admin_api_numero($fila['saldo_cobrado']);
        $efectivo = admin_api_numero($fila['efectivo']);
        $transferencia = admin_api_numero($fila['transferencia']);
        $venta = [
            'ticket_id' => (int) $fila['ticket_id'],
            'turno_id' => (int) $fila['turno_id'],
            'cliente' => $fila['cliente'],
            'cancha' => $fila['cancha'],
            'fecha_turno' => $fila['fecha_turno'],
            'hora_inicio' => $fila['hora_inicio'],
            'hora_fin' => $fila['hora_fin'],
            'fecha_pago' => $fila['fecha_pago'],
            'senia_aplicada' => $senia,
            'total_cancha' => admin_api_numero($fila['total_cancha']),
            'extra' => admin_api_numero($fila['extra']),
            'total_productos' => admin_api_numero($fila['total_productos']),
            'saldo_cobrado' => $saldo,
            'total_con_senia' => admin_api_numero($saldo + $senia),
            'efectivo' => $efectivo,
            'transferencia' => $transferencia,
        ];
        $ventas[] = $venta;
        $totales_ventas['senias_aplicadas'] += $senia;
        $totales_ventas['cancha'] += $venta['total_cancha'];
        $totales_ventas['extras'] += $venta['extra'];
        $totales_ventas['productos'] += $venta['total_productos'];
        $totales_ventas['facturado'] += $venta['total_con_senia'];
        $totales_ventas['efectivo'] += $efectivo;
        $totales_ventas['transferencia'] += $transferencia;
        $totales_ventas['cobrado_al_finalizar'] += $efectivo + $transferencia;
    }

    $stmt = admin_api_preparar("SELECT
            s._id AS id,
            s.FECHA AS fecha,
            s.MONTO AS monto,
            s.TRANSFERENCIA AS transferencia,
            s.EFECTIVO AS efectivo,
            cl.NOMBRE AS cliente,
            t.FECHA AS fecha_turno,
            cn.NOMBRE AS cancha
        FROM senias s
        INNER JOIN clientes cl ON cl._id = s.id_CLIENTE
        INNER JOIN turnos t ON t._id = s.id_TURNO
        INNER JOIN canchas cn ON cn._id = t.id_CANCHA
        WHERE STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE(?, '%d-%m-%Y %H:%i') AND STR_TO_DATE(?, '%d-%m-%Y %H:%i')
        ORDER BY STR_TO_DATE(s.FECHA, '%d-%m-%Y %H:%i')");
    mysqli_stmt_bind_param($stmt, 'ss', $desde, $hasta);
    $senias_db = admin_api_filas($stmt);
    $senias = [];
    $totales_senias = ['total' => 0.0, 'efectivo' => 0.0, 'transferencia' => 0.0];
    foreach ($senias_db as $fila) {
        $item = [
            'id' => (int) $fila['id'],
            'fecha' => $fila['fecha'],
            'cliente' => $fila['cliente'],
            'cancha' => $fila['cancha'],
            'fecha_turno' => $fila['fecha_turno'],
            'monto' => admin_api_numero($fila['monto']),
            'efectivo' => admin_api_numero($fila['efectivo']),
            'transferencia' => admin_api_numero($fila['transferencia']),
        ];
        $senias[] = $item;
        $totales_senias['total'] += $item['monto'];
        $totales_senias['efectivo'] += $item['efectivo'];
        $totales_senias['transferencia'] += $item['transferencia'];
    }

    $stmt = admin_api_preparar("SELECT
            g._id AS id,
            g.NOMBRE AS nombre,
            g.FECHA AS fecha,
            g.MONTO AS monto,
            g.EFECTIVO AS efectivo,
            g.TRANSFERENCIA AS transferencia,
            u.USUARIO AS usuario
        FROM gastos g
        INNER JOIN usuarios u ON u._id = g.id_USUARIO
        WHERE STR_TO_DATE(g.FECHA, '%d-%m-%Y %H:%i') BETWEEN STR_TO_DATE(?, '%d-%m-%Y %H:%i') AND STR_TO_DATE(?, '%d-%m-%Y %H:%i')
        ORDER BY STR_TO_DATE(g.FECHA, '%d-%m-%Y %H:%i')");
    mysqli_stmt_bind_param($stmt, 'ss', $desde, $hasta);
    $gastos_db = admin_api_filas($stmt);
    $gastos = [];
    $totales_gastos = ['total' => 0.0, 'efectivo' => 0.0, 'transferencia' => 0.0];
    foreach ($gastos_db as $fila) {
        $item = [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'fecha' => $fila['fecha'],
            'usuario' => $fila['usuario'],
            'monto' => admin_api_numero($fila['monto']),
            'efectivo' => admin_api_numero($fila['efectivo']),
            'transferencia' => admin_api_numero($fila['transferencia']),
        ];
        $gastos[] = $item;
        $totales_gastos['total'] += $item['monto'];
        $totales_gastos['efectivo'] += $item['efectivo'];
        $totales_gastos['transferencia'] += $item['transferencia'];
    }

    $ingresos = $totales_ventas['cobrado_al_finalizar'] + $totales_senias['total'];
    $egresos = $totales_gastos['total'];

    admin_api_responder(200, [
        'success' => true,
        'fecha' => $fecha_objeto->format('Y-m-d'),
        'periodo' => [
            'desde' => $desde_objeto->format('Y-m-d H:i:s'),
            'hasta' => $hasta_objeto->format('Y-m-d H:i:s'),
        ],
        'resumen' => [
            'ingresos_cobrados' => admin_api_numero($ingresos),
            'egresos' => admin_api_numero($egresos),
            'resultado_neto' => admin_api_numero($ingresos - $egresos),
        ],
        'ventas' => ['cantidad' => count($ventas), 'totales' => $totales_ventas, 'detalle' => $ventas],
        'senias' => ['cantidad' => count($senias), 'totales' => $totales_senias, 'detalle' => $senias],
        'gastos' => ['cantidad' => count($gastos), 'totales' => $totales_gastos, 'detalle' => $gastos],
    ]);
}

function admin_api_informe_mensual($mes)
{
    global $admin_api_etapa;
    $mes_objeto = admin_api_validar_mes($mes);
    if (!$mes_objeto) {
        admin_api_responder(400, [
            'success' => false,
            'error' => 'invalid_month',
            'message' => 'El mes debe tener el formato YYYY-MM.',
        ]);
    }
    $mes = $mes_objeto->format('Y-m');

    $admin_api_etapa = 'informe_mensual_ventas';
    $stmt = admin_api_preparar("SELECT
            cn._id AS cancha_id,
            cn.NOMBRE AS cancha,
            COUNT(*) AS cantidad,
            COALESCE(SUM(tk.SENIA), 0) AS senias_aplicadas,
            COALESCE(SUM(tk.TOTAL_CANCHA), 0) AS total_cancha,
            COALESCE(SUM(tk.EXTRA), 0) AS extras,
            COALESCE(SUM(tk.TOTAL_DETALLE), 0) AS productos,
            COALESCE(SUM(COALESCE(tk.TOTAL, 0) + COALESCE(tk.SENIA, 0)), 0) AS facturado,
            COALESCE(SUM(tk.PAGO_EFECTIVO), 0) AS efectivo,
            COALESCE(SUM(tk.PAGO_TRANSFERENCIA), 0) AS transferencia
        FROM turnos t
        INNER JOIN ticket tk ON tk.id_TURNO = t._id
        INNER JOIN canchas cn ON cn._id = t.id_CANCHA
        WHERE CONCAT(SUBSTRING(tk.FECHA, 7, 4), '-', SUBSTRING(tk.FECHA, 4, 2)) = ?
          AND t.FINALIZADO = 1
        GROUP BY cn._id, cn.NOMBRE
        ORDER BY cn.NOMBRE");
    mysqli_stmt_bind_param($stmt, 's', $mes);
    $ventas_db = admin_api_filas($stmt);
    $ventas = [];
    $totales_ventas = ['cantidad' => 0, 'senias_aplicadas' => 0.0, 'cancha' => 0.0, 'extras' => 0.0, 'productos' => 0.0, 'facturado' => 0.0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'cobrado_al_finalizar' => 0.0];
    foreach ($ventas_db as $fila) {
        $item = [
            'cancha_id' => (int) $fila['cancha_id'],
            'cancha' => $fila['cancha'],
            'cantidad' => (int) $fila['cantidad'],
            'senias_aplicadas' => admin_api_numero($fila['senias_aplicadas']),
            'total_cancha' => admin_api_numero($fila['total_cancha']),
            'extras' => admin_api_numero($fila['extras']),
            'productos' => admin_api_numero($fila['productos']),
            'facturado' => admin_api_numero($fila['facturado']),
            'efectivo' => admin_api_numero($fila['efectivo']),
            'transferencia' => admin_api_numero($fila['transferencia']),
        ];
        $ventas[] = $item;
        $totales_ventas['cantidad'] += $item['cantidad'];
        $totales_ventas['senias_aplicadas'] += $item['senias_aplicadas'];
        $totales_ventas['cancha'] += $item['total_cancha'];
        $totales_ventas['extras'] += $item['extras'];
        $totales_ventas['productos'] += $item['productos'];
        $totales_ventas['facturado'] += $item['facturado'];
        $totales_ventas['efectivo'] += $item['efectivo'];
        $totales_ventas['transferencia'] += $item['transferencia'];
        $totales_ventas['cobrado_al_finalizar'] += $item['efectivo'] + $item['transferencia'];
    }

    $admin_api_etapa = 'informe_mensual_senias';
    $stmt = admin_api_preparar("SELECT
            cn._id AS cancha_id,
            cn.NOMBRE AS cancha,
            COUNT(*) AS cantidad,
            COALESCE(SUM(s.MONTO), 0) AS total,
            COALESCE(SUM(s.EFECTIVO), 0) AS efectivo,
            COALESCE(SUM(s.TRANSFERENCIA), 0) AS transferencia
        FROM senias s
        INNER JOIN turnos t ON t._id = s.id_TURNO
        INNER JOIN canchas cn ON cn._id = t.id_CANCHA
        WHERE CONCAT(SUBSTRING(s.FECHA, 7, 4), '-', SUBSTRING(s.FECHA, 4, 2)) = ?
        GROUP BY cn._id, cn.NOMBRE
        ORDER BY cn.NOMBRE");
    mysqli_stmt_bind_param($stmt, 's', $mes);
    $senias_db = admin_api_filas($stmt);
    $senias = [];
    $totales_senias = ['cantidad' => 0, 'total' => 0.0, 'efectivo' => 0.0, 'transferencia' => 0.0];
    foreach ($senias_db as $fila) {
        $item = ['cancha_id' => (int) $fila['cancha_id'], 'cancha' => $fila['cancha'], 'cantidad' => (int) $fila['cantidad'], 'total' => admin_api_numero($fila['total']), 'efectivo' => admin_api_numero($fila['efectivo']), 'transferencia' => admin_api_numero($fila['transferencia'])];
        $senias[] = $item;
        foreach (['cantidad', 'total', 'efectivo', 'transferencia'] as $campo) {
            $totales_senias[$campo] += $item[$campo];
        }
    }

    $admin_api_etapa = 'informe_mensual_gastos';
    $stmt = admin_api_preparar("SELECT
            g._id AS id, g.NOMBRE AS nombre, g.FECHA AS fecha,
            g.EFECTIVO AS efectivo, g.TRANSFERENCIA AS transferencia,
            g.MONTO AS total, u.USUARIO AS usuario
        FROM gastos g
        INNER JOIN usuarios u ON u._id = g.id_USUARIO
        WHERE CONCAT(SUBSTRING(g.FECHA, 7, 4), '-', SUBSTRING(g.FECHA, 4, 2)) = ?
        ORDER BY STR_TO_DATE(g.FECHA, '%d-%m-%Y %H:%i')");
    mysqli_stmt_bind_param($stmt, 's', $mes);
    $gastos = admin_api_normalizar_egresos(admin_api_filas($stmt));

    $admin_api_etapa = 'informe_mensual_servicios';
    $stmt = admin_api_preparar("SELECT
            gs._id AS id, gs.NOMBRE AS nombre, gs.FECHA AS fecha,
            gs.EFECTIVO AS efectivo, gs.TRANSFERENCIA AS transferencia,
            gs.MONTO AS total, u.USUARIO AS usuario
        FROM gastos_servicios gs
        INNER JOIN usuarios u ON u._id = gs.id_USUARIO
        WHERE CONCAT(SUBSTRING(gs.FECHA, 7, 4), '-', SUBSTRING(gs.FECHA, 4, 2)) = ?
        ORDER BY STR_TO_DATE(gs.FECHA, '%d-%m-%Y %H:%i')");
    mysqli_stmt_bind_param($stmt, 's', $mes);
    $servicios = admin_api_normalizar_egresos(admin_api_filas($stmt));

    $admin_api_etapa = 'informe_mensual_pagos';
    $stmt = admin_api_preparar("SELECT
            u.NOMBRE AS nombre, u.USUARIO AS usuario,
            pe.FECHA AS fecha, pe.MONTO AS total
        FROM pago_empleado pe
        INNER JOIN usuarios u ON u._id = pe.id_USER
        WHERE CONCAT(SUBSTRING(pe.FECHA, 7, 4), '-', SUBSTRING(pe.FECHA, 4, 2)) = ?
        ORDER BY STR_TO_DATE(pe.FECHA, '%d-%m-%Y %H:%i')");
    mysqli_stmt_bind_param($stmt, 's', $mes);
    $pagos_db = admin_api_filas($stmt);
    $pagos = ['cantidad' => 0, 'total' => 0.0, 'detalle' => []];
    foreach ($pagos_db as $fila) {
        $item = ['nombre' => $fila['nombre'], 'usuario' => $fila['usuario'], 'fecha' => $fila['fecha'], 'total' => admin_api_numero($fila['total'])];
        $pagos['detalle'][] = $item;
        $pagos['cantidad']++;
        $pagos['total'] += $item['total'];
    }

    $ingresos = $totales_ventas['cobrado_al_finalizar'] + $totales_senias['total'];
    $egresos = $gastos['total'] + $servicios['total'] + $pagos['total'];

    admin_api_responder(200, [
        'success' => true,
        'mes' => $mes,
        'resumen' => [
            'ingresos_cobrados' => admin_api_numero($ingresos),
            'egresos' => admin_api_numero($egresos),
            'resultado_neto' => admin_api_numero($ingresos - $egresos),
        ],
        'ventas' => ['por_cancha' => $ventas, 'totales' => $totales_ventas],
        'senias' => ['por_cancha' => $senias, 'totales' => $totales_senias],
        'gastos' => $gastos,
        'gastos_servicios' => $servicios,
        'pagos_empleados' => $pagos,
    ]);
}

function admin_api_normalizar_egresos($filas)
{
    $salida = ['cantidad' => 0, 'total' => 0.0, 'efectivo' => 0.0, 'transferencia' => 0.0, 'detalle' => []];
    foreach ($filas as $fila) {
        $item = [
            'id' => (int) $fila['id'],
            'nombre' => $fila['nombre'],
            'fecha' => $fila['fecha'],
            'usuario' => $fila['usuario'],
            'efectivo' => admin_api_numero($fila['efectivo']),
            'transferencia' => admin_api_numero($fila['transferencia']),
            'total' => admin_api_numero($fila['total']),
        ];
        $salida['detalle'][] = $item;
        $salida['cantidad']++;
        $salida['total'] += $item['total'];
        $salida['efectivo'] += $item['efectivo'];
        $salida['transferencia'] += $item['transferencia'];
    }
    return $salida;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    admin_api_responder(405, [
        'success' => false,
        'error' => 'method_not_allowed',
        'message' => 'Esta API administrativa es de solo lectura.',
    ]);
}

admin_api_autorizar();

$action = trim((string) ($_GET['action'] ?? ''));
$admin_api_etapa = 'routing';

try {
    switch ($action) {
        case 'turnos':
            admin_api_turnos($_GET['fecha'] ?? '');
            break;
        case 'informe_diario':
            admin_api_informe_diario($_GET['fecha'] ?? '');
            break;
        case 'informe_mensual':
            admin_api_informe_mensual($_GET['mes'] ?? '');
            break;
        default:
            admin_api_responder(404, [
                'success' => false,
                'error' => 'endpoint_not_found',
                'message' => 'Endpoint administrativo inexistente.',
                'endpoints' => [
                    'GET action=turnos&fecha=YYYY-MM-DD',
                    'GET action=informe_diario&fecha=YYYY-MM-DD',
                    'GET action=informe_mensual&mes=YYYY-MM',
                ],
            ]);
    }
} catch (Throwable $error) {
    error_log('admin_api: ' . $error->getMessage());
    admin_api_responder(500, [
        'success' => false,
        'error' => 'internal_error',
        'stage' => $admin_api_etapa,
        'message' => 'No se pudo completar la consulta administrativa.',
    ]);
}
