<?php
declare(strict_types=1);

// Shared JSON API bootstrap. No output before headers.
ini_set('display_errors', '0');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function api_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function api_require_method(string $expected): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== $expected) {
        header('Allow: ' . $expected);
        api_response(405, ['success' => false, 'error' => ['code' => 'METHOD_NOT_ALLOWED', 'message' => 'Method not allowed']]);
    }
}

function api_require_role(array $allowed): int
{
    $id = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    if (!is_numeric($id) || (int)$id <= 0 || !is_string($role)) {
        api_response(401, ['success' => false, 'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required']]);
    }
    if (!in_array($role, $allowed, true)) {
        api_response(403, ['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Insufficient permissions']]);
    }
    return (int)$id;
}
