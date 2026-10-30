<?php
declare(strict_types=1);

// Shared JSON API bootstrap. No output before headers.
ini_set('display_errors', '0');
require_once dirname(__DIR__, 2) . '/includes/auth_session.php';
app_session_start();
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
    $user = app_current_user();
    if ($user === null) {
        api_response(401, ['success' => false, 'error' => ['code' => 'UNAUTHENTICATED', 'message' => 'Authentication required']]);
    }
    if (!in_array($user['role'], $allowed, true)) {
        api_response(403, ['success' => false, 'error' => ['code' => 'FORBIDDEN', 'message' => 'Insufficient permissions']]);
    }
    return $user['id'];
}
