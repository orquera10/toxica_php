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
    $code = trim((string) ($input['codigo'] ?? ''));
    if ($code !== '') {
        $stmt = $con->prepare('SELECT cliente_id,code_hash,expires_at,attempts FROM wp_bot_challenges WHERE scope_hash = ? AND jid = ?');
        $stmt->bind_param('ss', $ctx['scope'], $ctx['jid']); $stmt->execute(); $challenge = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$challenge || strtotime($challenge['expires_at']) <= time() || $challenge['attempts'] >= 5) api_responder(400, ['success' => false, 'message' => 'El codigo vencio o se agotaron los intentos. Escribi cancelar para comenzar de nuevo.']);
        $stmt = $con->prepare('UPDATE wp_bot_challenges SET attempts = attempts + 1 WHERE scope_hash = ? AND jid = ?');
        $stmt->bind_param('ss', $ctx['scope'], $ctx['jid']); $stmt->execute(); $stmt->close();
        if (!password_verify($code, $challenge['code_hash'])) api_responder(400, ['success' => false, 'message' => 'El codigo no es correcto. Revisalo y volve a enviarlo.']);
        $con->begin_transaction();
        try {
            // Consume once, including two concurrent correct submissions.
            $stmt = $con->prepare('DELETE FROM wp_bot_challenges WHERE scope_hash = ? AND jid = ? AND code_hash = ? AND expires_at > NOW() AND attempts <= 5');
            $stmt->bind_param('sss', $ctx['scope'], $ctx['jid'], $challenge['code_hash']); $stmt->execute(); $consumed = $stmt->affected_rows; $stmt->close();
            if ($consumed !== 1) throw new RuntimeException('Codigo ya utilizado.');
            bot_identity_bind($ctx, (int) $challenge['cliente_id']);
            $con->commit();
        } catch (Throwable $error) { $con->rollback(); api_responder(409, ['success' => false, 'message' => 'El codigo ya se utilizo o vencio.']); }
        api_responder(200, bot_identity_result(bot_identity_customer($ctx)));
    }
    // Explicit accent-insensitive comparison; collapse repeated spaces in stored names.
    $stmt = $con->prepare("SELECT _id,NOMBRE,MAIL,TELEFONO FROM clientes WHERE VISIBLE = 1 AND CONVERT(REPLACE(REPLACE(REPLACE(TRIM(NOMBRE),'  ',' '),'  ',' '),'  ',' ') USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 3");
    $stmt->bind_param('s', $name); $stmt->execute(); $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();
    if (count($matches) > 1) {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $matches = array_values(array_filter($matches, fn($c) => $email !== '' && strtolower(trim((string) $c['MAIL'])) === $email));
        if (count($matches) !== 1) api_responder(200, ['success' => true, 'status' => 'ambiguous', 'message' => 'Hay varios registros con ese nombre. Indica el correo que ya tenes registrado para verificar el tuyo, o contacta al negocio.']);
    }
    if ($matches) {
        $candidate = $matches[0];
        $id = (int) $candidate['_id'];
        $stmt = $con->prepare('SELECT sent_at FROM wp_bot_challenges WHERE scope_hash = ? AND jid = ?');
        $stmt->bind_param('ss', $ctx['scope'], $ctx['jid']); $stmt->execute(); $last = $stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($last && time() - strtotime($last['sent_at']) < 60) api_responder(429, ['success' => false, 'message' => 'Espera un minuto antes de pedir otro codigo.']);
        $code = (string) random_int(100000, 999999);
        if (filter_var($candidate['MAIL'], FILTER_VALIDATE_EMAIL)) {
            [$ok] = enviar_mail_smtp($candidate['MAIL'], $candidate['NOMBRE'], 'Vincular WhatsApp - La Toxica', '<p>Tu codigo para vincular WhatsApp es <strong>' . $code . '</strong>. Vence en 10 minutos. Si no lo pediste, ignora este mensaje.</p>');
            $channel = 'correo';
        } elseif (api_telefono_local($candidate['TELEFONO']) !== '') {
            [$ok] = wp_bot_enviar_mensaje($candidate['TELEFONO'], 'Tu codigo para vincular el chat a La Toxica es ' . $code . '. Vence en 10 minutos. Si no lo pediste, ignoralo.', $ctx['session']);
            $channel = 'WhatsApp registrado';
        } else { api_responder(200, ['success' => true, 'status' => 'needs_staff', 'message' => 'Encontre un registro con ese nombre, pero necesitamos que el negocio verifique la vinculacion.']); }
        if (!$ok) api_responder(503, ['success' => false, 'message' => 'No pude enviar el codigo. Contacta al negocio para vincular tu registro.']);
        $hash = password_hash($code, PASSWORD_DEFAULT);
        $stmt = $con->prepare('INSERT INTO wp_bot_challenges(scope_hash,jid,cliente_id,code_hash,expires_at,attempts,sent_at) VALUES(?,?,?,?,DATE_ADD(NOW(),INTERVAL 10 MINUTE),0,NOW()) ON DUPLICATE KEY UPDATE cliente_id=VALUES(cliente_id),code_hash=VALUES(code_hash),expires_at=VALUES(expires_at),attempts=0,sent_at=NOW()');
        $stmt->bind_param('ssis', $ctx['scope'], $ctx['jid'], $id, $hash); $stmt->execute(); $stmt->close();
        api_responder(200, ['success' => true, 'status' => 'verification_required', 'message' => 'Te envie un codigo al ' . $channel . ' que ya tenes registrado. Escribilo aca para vincular este chat.']);
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
