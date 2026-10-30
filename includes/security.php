<?php
// CSRF helpers for state-changing HTML forms.
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}
function require_valid_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' ||
        !hash_equals(csrf_token(), (string)($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Invalid request token. Reload the page and retry.');
    }
}
