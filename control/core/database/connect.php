<?php
// Single database connection for the storefront, admin area and CLI tools.
// Update these values when deploying the application to another server.
$servername = 'localhost';
$user = 'tiba_quip_runtime';
$passcode = 'da48a0bfb856e2eb5851e9ed33b6df5f032b207348723a11625d2a714845ddfb';
$dbname = 'tiba_new';

// Application settings needed before the database settings table is available.
$config = [
    'environment' => 'local',
    // Used by first-party traffic analytics to resolve visitor IPs through IPAPI.
    'ipapi_access_key' => getenv('TIBA_IPAPI_ACCESS_KEY') ?: 'd18f6627d9f0e21b000c2cf87533df3a',
];

$connect_error = "Sorry, we're experiencing connection issues at the moment.";
if (!function_exists('mysqli_report')) {
    http_response_code(503);
    exit('The server PHP mysqli extension is not enabled. Enable mysqli for this domain in DirectAdmin.');
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($servername, $user, $passcode, $dbname);
    $conn->set_charset('utf8mb4');
} catch (Throwable $e) {
    error_log('Tiba database connection failed: '.$e->getMessage());
    if (PHP_SAPI === 'cli') {
        throw $e;
    }
    http_response_code(503);
    exit($connect_error);
}
