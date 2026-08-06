<?php
declare(strict_types=1);

const KEVSTORE_DIAGNOSTIC_KEY = 'kvs-20260806-b7f4e29c';

if (!isset($_GET['key']) || !hash_equals(KEVSTORE_DIAGNOSTIC_KEY, (string) $_GET['key'])) {
    http_response_code(404);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

$result = [
    'diagnostic_version' => '2026-08-06.3',
    'php_version' => PHP_VERSION,
    'mysqli_loaded' => extension_loaded('mysqli'),
    'session' => [],
    'database' => [],
];

$temporary_directory = sys_get_temp_dir();
$temporary_test_file = rtrim($temporary_directory, '/\\') . DIRECTORY_SEPARATOR . 'kevstore-session-test-' . bin2hex(random_bytes(6));
$temporary_write_ok = @file_put_contents($temporary_test_file, 'ok') !== false;

if ($temporary_write_ok) {
    @unlink($temporary_test_file);
}

$result['session'] = [
    'temporary_directory_exists' => is_dir($temporary_directory),
    'temporary_directory_writable' => is_writable($temporary_directory),
    'temporary_write_test' => $temporary_write_ok,
];

if (!$result['mysqli_loaded']) {
    $result['database'] = [
        'status' => 'ERROR',
        'code' => 'MYSQLI_EXTENSION_MISSING',
    ];
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

define('BASEPATH', __DIR__ . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR);
define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'production');

$active_group = 'default';
$query_builder = true;
$db = [];
require APPPATH . 'config/database.php';

$config = $db[$active_group] ?? null;

if (!is_array($config)) {
    $result['database'] = [
        'status' => 'ERROR',
        'code' => 'DATABASE_CONFIG_MISSING',
    ];
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = mysqli_init();
$connected = @$mysqli->real_connect(
    (string) ($config['hostname'] ?? 'localhost'),
    (string) ($config['username'] ?? ''),
    (string) ($config['password'] ?? ''),
    (string) ($config['database'] ?? ''),
    (int) ($config['port'] ?? 3306)
);

if (!$connected) {
    $result['database'] = [
        'status' => 'ERROR',
        'code' => 'DATABASE_CONNECTION_FAILED',
        'mysql_errno' => mysqli_connect_errno(),
    ];
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

$result['database']['connection'] = 'OK';
$result['database']['selected_database'] = $mysqli->query('SELECT DATABASE() AS db') !== false;

$table_query = $mysqli->query("SHOW TABLES LIKE 'users'");
$users_table_exists = $table_query !== false && $table_query->num_rows === 1;
$result['database']['users_table'] = $users_table_exists ? 'OK' : 'MISSING';

if ($users_table_exists) {
    $required_columns = ['id_user', 'nama_user', 'username', 'password', 'no_wa', 'tipe_user'];
    $available_columns = [];
    $column_query = $mysqli->query('SHOW COLUMNS FROM `users`');

    if ($column_query !== false) {
        while ($column = $column_query->fetch_assoc()) {
            $available_columns[] = (string) $column['Field'];
        }
    }

    $missing_columns = array_values(array_diff($required_columns, $available_columns));
    $result['database']['required_user_columns'] = empty($missing_columns) ? 'OK' : 'MISSING';
    $result['database']['missing_user_columns'] = $missing_columns;

    $count_query = $mysqli->query('SELECT COUNT(*) AS total FROM `users`');
    $count_row = $count_query !== false ? $count_query->fetch_assoc() : null;
    $result['database']['users_count'] = isset($count_row['total']) ? (int) $count_row['total'] : null;

    $hash_query = $mysqli->query("SELECT `password` FROM `users` WHERE `username` = 'admin' LIMIT 1");
    $hash_row = $hash_query !== false ? $hash_query->fetch_assoc() : null;
    $hash = (string) ($hash_row['password'] ?? '');
    $hash_info = $hash !== '' ? password_get_info($hash) : ['algoName' => 'unknown'];
    $result['database']['admin_user'] = $hash_row ? 'FOUND' : 'MISSING';
    $result['database']['admin_password_hash'] = ($hash_info['algoName'] ?? 'unknown') !== 'unknown' ? 'VALID' : 'INVALID';
}

$all_ok = $result['session']['temporary_write_test']
    && ($result['database']['connection'] ?? null) === 'OK'
    && ($result['database']['users_table'] ?? null) === 'OK'
    && ($result['database']['required_user_columns'] ?? null) === 'OK'
    && ($result['database']['admin_user'] ?? null) === 'FOUND'
    && ($result['database']['admin_password_hash'] ?? null) === 'VALID';

$result['status'] = $all_ok ? 'LOGIN_BACKEND_READY' : 'LOGIN_BACKEND_NOT_READY';

$mysqli->close();
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
