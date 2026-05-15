<?php
require_once __DIR__ . '/classes/env.php';

Env::load(__DIR__ . '/.env');

if (!defined('APP_ENV')) {
    define('APP_ENV', Env::get('APP_ENV', 'local'));
}

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', Env::getBool('APP_DEBUG', false));
}

if (!defined('APP_NAME')) {
    define('APP_NAME', Env::get('APP_NAME', 'Questra'));
}

if (!defined('APP_URL')) {
    define('APP_URL', rtrim(Env::get('APP_URL', 'http://casestudy'), '/'));
}

if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', Env::get('APP_TIMEZONE', 'Asia/Manila'));
}

if (!defined('APP_UPLOAD_DIR')) {
    define('APP_UPLOAD_DIR', __DIR__ . '/uploads');
}

if (!defined('DB_HOST')) {
    define('DB_HOST', Env::get('DB_HOST'));
}

if (!defined('DB_USER')) {
    define('DB_USER', Env::get('DB_USER'));
}

if (!defined('DB_PASS')) {
    define('DB_PASS', Env::get('DB_PASS', ''));
}

if (!defined('DB_NAME')) {
    define('DB_NAME', Env::get('DB_NAME'));
}

if (!defined('DB_PORT')) {
    define('DB_PORT', Env::getInt('DB_PORT', 3306) ?? 3306);
}

if (!defined('MAIL_HOST')) {
    define('MAIL_HOST', Env::get('MAIL_HOST'));
}

if (!defined('MAIL_PORT')) {
    define('MAIL_PORT', Env::getInt('MAIL_PORT', 587) ?? 587);
}

if (!defined('MAIL_ENCRYPTION')) {
    define('MAIL_ENCRYPTION', Env::get('MAIL_ENCRYPTION', 'tls'));
}

if (!defined('MAIL_USERNAME')) {
    define('MAIL_USERNAME', Env::get('MAIL_USERNAME'));
}

if (!defined('MAIL_PASSWORD')) {
    define('MAIL_PASSWORD', Env::get('MAIL_PASSWORD'));
}

if (!defined('MAIL_FROM_ADDRESS')) {
    define('MAIL_FROM_ADDRESS', Env::get('MAIL_FROM_ADDRESS', MAIL_USERNAME));
}

if (!defined('MAIL_FROM_NAME')) {
    define('MAIL_FROM_NAME', Env::get('MAIL_FROM_NAME', APP_NAME));
}

if (!defined('PUSHER_APP_ID')) {
    define('PUSHER_APP_ID', Env::get('PUSHER_APP_ID'));
}

if (!defined('PUSHER_APP_KEY')) {
    define('PUSHER_APP_KEY', Env::get('PUSHER_APP_KEY'));
}

if (!defined('PUSHER_APP_SECRET')) {
    define('PUSHER_APP_SECRET', Env::get('PUSHER_APP_SECRET'));
}

if (!defined('PUSHER_APP_CLUSTER')) {
    define('PUSHER_APP_CLUSTER', Env::get('PUSHER_APP_CLUSTER'));
}

if (!defined('GEMINI_API_KEY')) {
    define('GEMINI_API_KEY', Env::get('GEMINI_API_KEY'));
}

if (!defined('GEMINI_MODEL')) {
    define('GEMINI_MODEL', Env::get('GEMINI_MODEL', 'gemini-flash-latest'));
}

if (!defined('GEMINI_API_URL')) {
    define('GEMINI_API_URL', rtrim(Env::get('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'), '/'));
}

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', Env::get('GOOGLE_CLIENT_ID'));
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', Env::get('GOOGLE_CLIENT_SECRET'));
}

if (!defined('GOOGLE_REDIRECT_URI')) {
    define('GOOGLE_REDIRECT_URI', Env::get('GOOGLE_REDIRECT_URI'));
}

ini_set('display_errors', APP_DEBUG ? '1' : '0');
error_reporting(APP_DEBUG ? E_ALL : 0);

$connection = null;
$con = null;
$pdo = null;

$hasAnyDatabaseConfig = DB_HOST !== null || DB_USER !== null || DB_NAME !== null;
$hasFullDatabaseConfig = DB_HOST !== null && DB_USER !== null && DB_NAME !== null;

if ($hasAnyDatabaseConfig && !$hasFullDatabaseConfig) {
    error_log('PHVN database configuration is incomplete. Set DB_HOST, DB_USER, and DB_NAME.');
    http_response_code(500);
    require __DIR__ . '/components/500.php';
    exit;
}

if (!$hasFullDatabaseConfig) {
    return;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $connection = $pdo;
    $con = $pdo;
} catch (Throwable $err) {
    error_log('PHVN database error: ' . $err->getMessage());
    http_response_code(500);
    require __DIR__ . '/components/500.php';
    exit;
}
