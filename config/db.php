<?php
// Set AGRI_DB_HOST, AGRI_DB_USER, AGRI_DB_PASSWORD, AGRI_DB_NAME in the server environment.
$host = getenv('AGRI_DB_HOST') ?: '127.0.0.1';
$user = getenv('AGRI_DB_USER') ?: 'root';
$pass = getenv('AGRI_DB_PASSWORD');
$dbname = getenv('AGRI_DB_NAME') ?: 'agri_supply_chain';
if ($pass === false) { http_response_code(503); exit('Database configuration missing.'); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(503);
    exit('Database temporarily unavailable.');
}
