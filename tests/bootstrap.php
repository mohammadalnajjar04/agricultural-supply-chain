<?php
/**
 * PHPUnit Bootstrap
 * Loads security and utility functions without requiring database connection.
 */

// Start a test session
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Prevent security.php from sending headers during tests
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load security functions (skip the bootstrap call at the bottom)
require_once __DIR__ . '/../includes/security.php';
