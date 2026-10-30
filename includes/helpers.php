<?php
/**
 * Shared helper functions.
 * Include via: require_once __DIR__ . '/helpers.php';
 */

/**
 * Require the current user to have a specific role. Redirects to login if not.
 */
function require_role(string ...$allowedRoles): void {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit;
    }
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        header('Location: ../auth/login.php');
        exit;
    }
}

/**
 * Save an uploaded verification document. Returns relative path or null.
 * $prefix: 'farmer', 'store', or 'transporter'
 */
function save_verification_file(string $fieldName, string $prefix): ?string {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if (!verify_upload_size($_FILES[$fieldName], 5242880)) {
        throw new RuntimeException('File too large');
    }
    if (!verify_upload_mime($_FILES[$fieldName], ['application/pdf', 'image/png', 'image/jpeg'])) {
        throw new RuntimeException('Unsupported file');
    }
    $allowed = ['pdf','png','jpg','jpeg'];
    $name = $_FILES[$fieldName]['name'] ?? '';
    $tmp  = $_FILES[$fieldName]['tmp_name'] ?? '';
    $ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Unsupported file');
    }
    $dir = __DIR__ . '/../uploads/verification';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $safe = $prefix . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $dir . '/' . $safe;
    if (!move_uploaded_file($tmp, $dest)) {
        return null;
    }
    return 'uploads/verification/' . $safe;
}
