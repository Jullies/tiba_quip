<?php
$configFile = __DIR__ . '/../../../app/' . (PHP_SAPI === 'cli' && getenv('TIBA_DB_MIGRATION') === '1' && is_file(__DIR__ . '/../../../app/migration.local.php') ? 'migration.local.php' : 'config.local.php');
$config = require $configFile;
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli(getenv('TIBA_DB_HOST') ?: $config['host'], getenv('TIBA_DB_USER') ?: $config['user'], getenv('TIBA_DB_PASSWORD') !== false ? getenv('TIBA_DB_PASSWORD') : $config['password'], getenv('TIBA_DB_NAME') ?: $config['database']);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('Tiba database connection failed.');
    if (PHP_SAPI === 'cli') { throw $e; }
    http_response_code(503); exit('The store is temporarily unavailable. Please try again shortly.');
}
