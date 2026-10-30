<?php
declare(strict_types=1);
/** Shared session and role helpers; include before output. */
function app_session_start(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    if (headers_sent()) { throw new RuntimeException('Session must start before output'); }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    $cookie = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => $cookie['path'] ?: '/',
        'domain' => $cookie['domain'],
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
function app_login(int $userId, string $role, string $name): void
{
    app_session_start();
    if ($userId <= 0 || !in_array($role, ['farmer', 'store', 'transporter', 'admin'], true)) {
        throw new InvalidArgumentException('Invalid user identity');
    }
    session_regenerate_id(true);
    // Retain language selection only; discard any previous authenticated identity.
    $lang = $_SESSION['lang'] ?? 'en';
    $_SESSION = [
        'lang' => $lang === 'ar' ? 'ar' : 'en',
        'user_id' => $userId,
        'role' => $role,
        'name' => $name,
        'login_at' => time(),
        'csrf_token' => bin2hex(random_bytes(32)),
    ];
}
function app_current_user(array $allowedRoles = []): ?array
{
    app_session_start();
    $id = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? null;
    if (!is_int($id) || $id <= 0 || !is_string($role) ||
        !in_array($role, ['farmer','store','transporter','admin'], true)) return null;
    if ($allowedRoles && !in_array($role, $allowedRoles, true)) return null;
    return ['id' => $id, 'role' => $role, 'name' => (string)($_SESSION['name'] ?? '')];
}
function app_logout(): void
{
    app_session_start();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $p['path'],
            'domain' => $p['domain'],
            'secure' => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?? 'Lax',
        ]);
    }
    session_destroy();
}
