<?php
date_default_timezone_set('Asia/Jakarta');
header('Content-Type: application/json; charset=utf-8');

$db_host = 'localhost';
$db_user = 'kevsmyid_igni722';
$db_pass = 'viryofabrellinokudjinona123456789';
$db_name = 'kevsmyid_igni722';

$fonnte_token = 'J6WgtFwxavJ312gRdiVp';
$country_code = '62';
$allowed_senders = ['6283871821218'];
$blocked_recipients = ['0813122445798', '083871821218'];

$json = file_get_contents('php://input');
$payload = json_decode($json, true);

if (!is_array($payload)) {
    $payload = $_POST ?: $_GET;
}

$sender = normalize_phone($payload['sender'] ?? $payload['pengirim'] ?? '');
$message = trim((string)($payload['message'] ?? $payload['pesan'] ?? ''));
$inboxid = $payload['inboxid'] ?? null;

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_errno) {
    echo json_encode(['status' => false, 'message' => 'DB error']);
    exit;
}

ensure_tables($mysqli);
log_payload($mysqli, $sender, $message, $payload);

if ($sender === '' || $message === '') {
    echo json_encode(['status' => true, 'message' => 'Payload kosong']);
    exit;
}

if (!in_array($sender, array_map('normalize_phone', $allowed_senders), true)) {
    echo json_encode(['status' => true, 'message' => 'Sender tidak diizinkan']);
    exit;
}

$command = preg_replace('/[^a-z0-9]/', '', strtolower($message));

if (in_array($command, ['batal', 'cancel'], true)) {
    clear_session($mysqli, $sender);
    send_fonnte($fonnte_token, $country_code, $sender, 'Baik, proses tambah akun dibatalkan.', $inboxid);
    echo json_encode(['status' => true]);
    exit;
}

if (in_array($command, ['tambahakun', 'tambahakungrok'], true)) {
    clear_session($mysqli, $sender);
    $stmt = $mysqli->prepare("INSERT INTO whatsapp_command_sessions (sender, state, created_at, updated_at) VALUES (?, 'awaiting_bulk_add', NOW(), NOW())");
    $stmt->bind_param('s', $sender);
    $stmt->execute();
    $stmt->close();

    send_fonnte($fonnte_token, $country_code, $sender, bulk_instruction_message(), $inboxid);
    echo json_encode(['status' => true]);
    exit;
}

if (has_active_session($mysqli, $sender)) {
    $result = create_accounts_from_bulk_text($mysqli, $message, $sender);
    clear_session($mysqli, $sender);
    send_fonnte($fonnte_token, $country_code, $sender, bulk_result_message($result), $inboxid);
    echo json_encode(['status' => true, 'created' => $result['created'], 'skipped' => $result['skipped']]);
    exit;
}

echo json_encode(['status' => true, 'message' => 'Tidak ada perintah aktif']);

function normalize_phone($phone)
{
    $phone = preg_replace('/[^0-9]/', '', (string)$phone);

    if (strpos($phone, '0') === 0) {
        $phone = '62' . substr($phone, 1);
    }

    return $phone;
}

function ensure_tables($mysqli)
{
    $mysqli->query("
        CREATE TABLE IF NOT EXISTS whatsapp_webhook_log (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            sender VARCHAR(30) NULL,
            message TEXT NULL,
            payload TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_whatsapp_webhook_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");

    $mysqli->query("
        CREATE TABLE IF NOT EXISTS whatsapp_command_sessions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            sender VARCHAR(30) NOT NULL,
            state VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_whatsapp_command_sender (sender, updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
}

function log_payload($mysqli, $sender, $message, $payload)
{
    $payload_json = json_encode($payload);
    $stmt = $mysqli->prepare('INSERT INTO whatsapp_webhook_log (sender, message, payload, created_at) VALUES (?, ?, ?, NOW())');
    $stmt->bind_param('sss', $sender, $message, $payload_json);
    $stmt->execute();
    $stmt->close();
}

function clear_session($mysqli, $sender)
{
    $stmt = $mysqli->prepare('DELETE FROM whatsapp_command_sessions WHERE sender = ?');
    $stmt->bind_param('s', $sender);
    $stmt->execute();
    $stmt->close();
}

function has_active_session($mysqli, $sender)
{
    $stmt = $mysqli->prepare("SELECT id FROM whatsapp_command_sessions WHERE sender = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE) ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $sender);
    $stmt->execute();
    $result = $stmt->get_result();
    $has_session = $result && $result->num_rows > 0;
    $stmt->close();

    return $has_session;
}

function create_accounts_from_bulk_text($mysqli, $bulk_text, $sender)
{
    $lines = preg_split('/\r\n|\r|\n/', (string)$bulk_text);
    $created = 0;
    $skipped = 0;
    $seen = [];
    $changed_by = 'WhatsApp ' . $sender;

    foreach ($lines as $line) {
        $line = trim((string)$line);

        if ($line === '') {
            continue;
        }

        $parts = explode('|', $line, 3);
        $username = trim($parts[0] ?? '');
        $password = trim($parts[1] ?? '');
        $note = trim($parts[2] ?? '');

        if ($username === '' || $password === '') {
            $skipped++;
            continue;
        }

        $key = strtolower($username);

        if (isset($seen[$key]) || username_exists($mysqli, $username)) {
            $skipped++;
            continue;
        }

        $seen[$key] = true;

        $stmt = $mysqli->prepare("
            INSERT INTO akun
            (nama_akun, kategori, status, username, password, website, max_user, expired_password, note, created_by, last_edited_by, last_edited_at)
            VALUES ('Grok', 'belum_terjual', 'aktif', ?, ?, '', 0, NULL, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('sssss', $username, $password, $note, $changed_by, $changed_by);
        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        $action = 'Bulk tambah akun via WhatsApp';
        $stmt = $mysqli->prepare('INSERT INTO activity_log (akun_id, action, changed_by, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->bind_param('iss', $id, $action, $changed_by);
        $stmt->execute();
        $stmt->close();

        $created++;
    }

    return ['created' => $created, 'skipped' => $skipped];
}

function username_exists($mysqli, $username)
{
    $stmt = $mysqli->prepare('SELECT id_akun FROM akun WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function bulk_instruction_message()
{
    return "Baik, fitur tambah akun dimulai.\n\n"
        . "Kirim daftar akun dengan format bulk:\n"
        . "username|password|catatan\n\n"
        . "Bisa banyak baris sekaligus, contoh:\n"
        . "user1@gmail.com|password123|akun utama\n"
        . "user2@gmail.com|pass456\n\n"
        . "Ketik BATAL untuk membatalkan.";
}

function bulk_result_message($result)
{
    if ((int)$result['created'] < 1) {
        return "Tidak ada akun yang berhasil ditambahkan.\nPastikan formatnya: username|password|catatan";
    }

    $message = $result['created'] . ' akun berhasil ditambahkan lewat WhatsApp.';

    if ((int)$result['skipped'] > 0) {
        $message .= "\n" . $result['skipped'] . ' baris dilewati karena format salah atau username sudah ada.';
    }

    return $message;
}

function send_fonnte($token, $country_code, $target, $message, $inboxid = null)
{
    if (is_blocked_recipient($target)) {
        return;
    }

    $fields = [
        'target' => $target,
        'message' => $message,
        'countryCode' => $country_code
    ];

    if (!empty($inboxid)) {
        $fields['inboxid'] = $inboxid;
    }

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => [
            'Authorization: ' . $token
        ],
    ]);

    curl_exec($curl);
    curl_close($curl);
}

function is_blocked_recipient($target)
{
    global $blocked_recipients;

    return in_array(normalize_phone($target), array_map('normalize_phone', $blocked_recipients), true);
}
