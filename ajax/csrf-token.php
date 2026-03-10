<?php
/**
 * AJAX-Endpoint zum Abrufen eines CSRF-Token
 * Wird vom Frontend aufgerufen, um ein gültiges CSRF-Token zu erhalten
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/csrf.php';

use Security\CSRF;

// Token generieren und zurückgeben
$token = CSRF::generateToken();

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => 'success',
    'token' => $token,
    'expiry' => CSRF::getExpiry()
]);