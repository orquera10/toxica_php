<?php
// No database or real customer data is changed by this test.
$scenario = $argv[1] ?? 'success';
$source = file_get_contents(__DIR__ . '/../wp_reservas_api.php');
$start = strpos($source, 'function api_desvincular_cliente(');
$end = strpos($source, '$action =', $start);
eval(substr($source, $start, $end - $start));
$_SERVER['REQUEST_METHOD'] = $scenario === 'method' ? 'GET' : 'POST';
$updated = false;
$committed = false;
$rolledBack = false;
function api_input() { global $scenario; return ['telefono' => '5493881234567', 'confirmar' => $scenario !== 'invalid']; }
function bot_identity_context() { return null; }
function api_buscar_cliente($phone) { global $scenario; return $scenario === 'notfound' ? null : ['_id' => 42, 'TELEFONO' => '3881234567']; }
function api_responder($status, $data) {
    global $scenario, $updated, $committed, $rolledBack;
    $expected = ['success' => 200, 'pending' => 409, 'notfound' => 200, 'invalid' => 400, 'method' => 405][$scenario];
    $ok = $status === $expected && $updated === ($scenario === 'success');
    if ($scenario === 'success') $ok = $ok && $committed && !$rolledBack && $data['desvinculado'];
    if ($scenario === 'pending') $ok = $ok && $rolledBack && !$committed;
    echo ($ok ? 'OK: ' : 'FAIL: ') . $scenario . "\n";
    exit($ok ? 0 : 1);
}
class UnlinkFakeConnection {
    private $sql;
    function begin_transaction() {}
    function prepare($sql) {
        if (stripos($sql, 'DELETE') !== false) throw new RuntimeException('Must not delete customers or bookings');
        $this->sql = $sql;
        return $this;
    }
    function bind_param($type, &$id) { if ($id !== 42) throw new RuntimeException('Wrong customer'); }
    function execute() {
        global $updated;
        if (str_starts_with($this->sql, 'UPDATE')) {
            if ($this->sql !== "UPDATE clientes SET TELEFONO = '' WHERE _id = ?") throw new RuntimeException('Only the phone may change');
            $updated = true;
        }
    }
    function get_result() { return $this; }
    function fetch_assoc() { global $scenario; return str_contains($this->sql, 'FROM ticket') ? ($scenario === 'pending' ? ['_id' => 7] : null) : ['TELEFONO' => '3881234567']; }
    function close() {}
    function commit() { global $committed; $committed = true; }
    function rollback() { global $rolledBack; $rolledBack = true; }
}
$con = new UnlinkFakeConnection();
api_desvincular_cliente();
