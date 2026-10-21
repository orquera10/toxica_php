<?php
require_once __DIR__ . '/../wp_bot_identity.php';
if (!function_exists('str_contains')) { function str_contains($haystack, $needle) { return strpos($haystack, $needle) !== false; } }
if (!function_exists('str_starts_with')) { function str_starts_with($haystack, $needle) { return strpos($haystack, $needle) === 0; } }
$scenario = $argv[1] ?? 'new';
$input = ['bot_jid' => '123456789012345@lid', 'bot_session' => 'toxica', 'nombre' => 'Maria Perez'];
if (in_array($scenario, ['code-good', 'code-bad', 'code-expired', 'code-limited'])) $input['codigo'] = $scenario === 'code-bad' ? '000000' : '123456';
$api_key = 'test-only';
$_SERVER['REQUEST_METHOD'] = 'POST';
$bound = $scenario === 'linked'; $created = false; $sent = false; $consumed = false;
$customer = ['_id' => 42, 'NOMBRE' => 'Maria Perez', 'MAIL' => '', 'TELEFONO' => ''];
function api_input() { global $input; return $input; }
function api_buscar_cliente($phone) { throw new RuntimeException('LID must never be searched as a phone'); }
function api_telefono_local($phone) { return $phone; }
function enviar_mail_smtp(...$args) { global $sent; $sent = true; return [true, '']; }
function wp_bot_enviar_mensaje(...$args) { throw new RuntimeException('This test has no real phone'); }
function api_responder($status, $data) {
    global $scenario, $bound, $created, $sent, $consumed;
    $expectedStatus = ['new'=>201,'linked'=>200,'existing'=>200][$scenario];
    $ok = $status === $expectedStatus;
    if ($scenario === 'new') $ok = $ok && $created && $bound && $data['cliente']['email'] === '';
    if ($scenario === 'linked') $ok = $ok && !$created && $data['cliente']['id'] === 42;
    if ($scenario === 'existing') $ok = $ok && $bound && $data['cliente']['id'] === 42;
    if ($scenario === 'code-good') $ok = $ok && $bound && $consumed;
    if (in_array($scenario,['code-bad','code-expired','code-limited'])) $ok = $ok && !$bound && !$consumed;
    if (isset($data['code_hash']) || isset($data['codigo'])) $ok = false;
    echo ($ok ? 'OK: ' : 'FAIL: ') . $scenario . "\n";
    exit($ok ? 0 : 1);
}
class IdentityFakeConnection {
    public $insert_id = 42;
    public $affected_rows = 1;
    public $sql = '';
    function query($sql) { return true; }
    function prepare($sql) { $this->sql = $sql; return $this; }
    function bind_param($types, &...$args) {
        if (str_contains($this->sql, 'scope_hash') && count($args) >= 2 && $args[0] !== hash('sha256', 'test-only:toxica')) throw new RuntimeException('Identity was not scoped to API key and session');
    }
    function execute() {
        global $bound, $created, $consumed;
        if (str_starts_with($this->sql,'INSERT INTO clientes')) $created = true;
        if (str_starts_with($this->sql,'INSERT INTO wp_bot_identities')) $bound = true;
        if (str_starts_with($this->sql,'DELETE FROM wp_bot_challenges')) $consumed = true;
        return true;
    }
    function get_result() { return $this; }
    function fetch_assoc() {
        global $bound, $customer, $scenario;
        if (str_contains($this->sql,'JOIN clientes')) return $bound ? $customer : null;
        if (str_contains($this->sql,'code_hash')) return ['cliente_id'=>42, 'code_hash'=>password_hash('123456',PASSWORD_DEFAULT), 'expires_at'=>date('Y-m-d H:i:s',time()+($scenario === 'code-expired' ? -60 : 600)), 'attempts'=>$scenario === 'code-limited' ? 5 : 0];
        return null;
    }
    function fetch_all($mode) {
        global $scenario, $customer;
        if ($scenario === 'ambiguous') return [$customer,$customer];
        if ($scenario === 'no-contact') return [$customer];
        if ($scenario === 'existing') return [array_merge($customer, ['MAIL'=>'test@example.com'])];
        return [];
    }
    function close() {}
    function begin_transaction() {}
    function commit() {}
    function rollback() {}
}
$con = new IdentityFakeConnection();
bot_register_identity();
