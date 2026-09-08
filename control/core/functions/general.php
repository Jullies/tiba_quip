<?php

$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = strtok($request_uri, '?'); // Remove query string
$trimmed_uri = rtrim($request_uri, '/');

// Normalize host: remove www
$host = preg_replace('/^www\./', '', $_SERVER['HTTP_HOST']);

// Split the path into segments
$segments = explode('/', trim($trimmed_uri, '/'));

// Detect which web section
$which_web = in_array('stores', $segments) ? 'stores' : 'home';

// ✅ Detect current page correctly
$current_page = basename($trimmed_uri);
if ($current_page === '' || strpos($current_page, '.') === false) {
    // No file specified → assume index.php
    $current_page = 'index.php';
}

// OS-specific python command
$os = PHP_OS_FAMILY;
$python = ($os === 'Windows') ? 'python' : 'python3';


?>