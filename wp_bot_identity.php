<?php
// Identity is supplied by the authenticated bot, never inferred from a LID's digits.
function bot_identity_schema()
{
    global $con;
    static $ready = false;
    if ($ready) return;
    $con->query("CREATE TABLE IF NOT EXISTS wp_bot_identities (scope_hash CHAR(64) NOT NULL, jid VARCHAR(90) NOT NULL, cliente_id INT NOT NULL, PRIMARY KEY(scope_hash,jid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $con->query("CREATE TABLE IF NOT EXISTS wp_bot_challenges (scope_hash CHAR(64) NOT NULL, jid VARCHAR(90) NOT NULL, cliente_id INT NOT NULL, code_hash VARCHAR(255) NOT NULL, expires_at DATETIME NOT NULL, attempts INT NOT NULL DEFAULT 0, sent_at DATETIME NOT NULL, PRIMARY KEY(scope_hash,jid)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $con->query("CREATE TABLE IF NOT EXISTS wp_bot_booking_chats (ticket_id INT NOT NULL PRIMARY KEY, jid VARCHAR(90) NOT NULL, session_id VARCHAR(100) NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $ready = true;
}

function bot_identity_context()
{
    global $api_key;
    $input = $_SERVER['REQUEST_METHOD'] === 'GET' ? $_GET : api_input();
    $jid = (string) ($input['bot_jid'] ?? '');
    $session = (string) ($input['bot_session'] ?? '');
    if (!preg_match('/^\d{9,20}@(lid|s\.whatsapp\.net)$/D', $jid) || !preg_match('/^[a-zA-Z0-9_-]{1,100}$/D', $session)) return null;
    $phone = (string) ($input['bot_phone'] ?? '');
    if (!preg_match('/^\d{9,15}$/D', $phone)) $phone = '';
    if (substr($jid, -15) === '@s.whatsapp.net') $phone = explode('@', $jid)[0];
    return ['jid' => $jid, 'phone' => $phone, 'session' => $session, 'scope' => hash('sha256', (string) $api_key . ':' . $session)];
}

function bot_identity_customer($ctx)
{
    global $con;
    bot_identity_schema();
    $stmt = $con->prepare('SELECT c._id, c.NOMBRE, c.MAIL, c.TELEFONO FROM wp_bot_identities b JOIN clientes c ON c._id = b.cliente_id WHERE b.scope_hash = ? AND b.jid = ? AND c.VISIBLE = 1');
    $stmt->bind_param('ss', $ctx['scope'], $ctx['jid']);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    // A real phone delivered by WhatsApp may recognize an existing customer.
    if (!$customer && $ctx['phone'] !== '') {
        $customer = api_buscar_cliente($ctx['phone']);
    }
    if ($customer && $ctx['phone'] !== '') bot_identity_bind($ctx, (int) $customer['_id']);
    return $customer ?: null;
}

function bot_identity_bind($ctx, $id)
{
    global $con;
    $stmt = $con->prepare('INSERT INTO wp_bot_identities(scope_hash,jid,cliente_id) VALUES(?,?,?) ON DUPLICATE KEY UPDATE cliente_id = VALUES(cliente_id)');
    $stmt->bind_param('ssi', $ctx['scope'], $ctx['jid'], $id);
    if (!$stmt->execute()) throw new RuntimeException('No se pudo vincular el chat.');
    $stmt->close();
    if (($ctx['phone'] ?? '') !== '' && $ctx['jid'] !== $ctx['phone'] . '@s.whatsapp.net') {
        $phone_ctx = $ctx;
        $phone_ctx['jid'] = $ctx['phone'] . '@s.whatsapp.net';
        bot_identity_bind($phone_ctx, $id);
    }
}

function bot_identity_result($customer)
{
    if (!$customer) api_responder(409, ['success' => false, 'message' => 'No pude comprobar el registro vinculado. Contacta al negocio para revisarlo.']);
    return ['success' => true, 'status' => 'registered', 'cliente' => ['id' => (int) $customer['_id'], 'nombre' => $customer['NOMBRE'], 'email' => $customer['MAIL'] ?? '', 'telefono' => $customer['TELEFONO'] ?? '']];
}

function bot_register_identity()
{
    global $con;
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') api_responder(405, ['success' => false, 'message' => 'Usa POST.']);
    $ctx = bot_identity_context();
    if (!$ctx) api_responder(400, ['success' => false, 'message' => 'No se pudo reconocer el chat.']);
    bot_identity_schema();
    $existing = bot_identity_customer($ctx);
    if ($existing) api_responder(200, bot_identity_result($existing));
    $input = api_input();
    $name = preg_replace('/\s+/u', ' ', trim((string) ($input['nombre'] ?? '')));
    if (mb_strlen($name) < 5 || mb_strlen($name) > 150 || count(explode(' ', $name)) < 2 || preg_match('/[0-9<>@]/u', $name)) {
        api_responder(400, ['success' => false, 'message' => 'Escribi tu nombre y apellido completos.']);
    }
    // Explicit accent-insensitive comparison; collapse repeated spaces in stored names.
    $stmt = $con->prepare("SELECT _id,NOMBRE,MAIL,TELEFONO FROM clientes WHERE VISIBLE = 1 AND CONVERT(REPLACE(REPLACE(REPLACE(TRIM(NOMBRE),'  ',' '),'  ',' '),'  ',' ') USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci ORDER BY _id DESC LIMIT 1");
    $stmt->bind_param('s', $name); $stmt->execute(); $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    if ($matches) {
        $candidate = $matches[0];
        $id = (int) $candidate['_id'];
        bot_identity_bind($ctx, $id);
        api_responder(200, bot_identity_result(bot_identity_customer($ctx)));
    }
    $phone = $ctx['phone'] !== '' ? api_telefono_local($ctx['phone']) : '';
    $con->begin_transaction();
    try {
        $stmt = $con->prepare("INSERT INTO clientes(NOMBRE,MAIL,TELEFONO) VALUES(?,'',?)");
        $stmt->bind_param('ss', $name, $phone);
        if (!$stmt->execute()) throw new RuntimeException('No se pudo crear el cliente.');
        $id = $stmt->insert_id; $stmt->close();
        bot_identity_bind($ctx, $id);
        $con->commit();
    } catch (Throwable $error) { $con->rollback(); api_responder(500, ['success' => false, 'message' => 'No pude completar el registro. Volve a consultar antes de intentarlo nuevamente.']); }
    api_responder(201, bot_identity_result(bot_identity_customer($ctx)));
}

function bot_booking_chat_save($ticket)
{
    global $con;
    $ctx = bot_identity_context();
    if (!$ctx) return;
    bot_identity_schema();
    $stmt = $con->prepare('INSERT INTO wp_bot_booking_chats(ticket_id,jid,session_id) VALUES(?,?,?)');
    $stmt->bind_param('iss', $ticket, $ctx['jid'], $ctx['session']); $stmt->execute(); $stmt->close();
}

function bot_booking_chat($ticket)
{
    global $con;
    bot_identity_schema();
    $stmt = $con->prepare('SELECT jid,session_id FROM wp_bot_booking_chats WHERE ticket_id = ?');
    $stmt->bind_param('i', $ticket); $stmt->execute(); $chat = $stmt->get_result()->fetch_assoc(); $stmt->close();
    return $chat ?: null;
}
