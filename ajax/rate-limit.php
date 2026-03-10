<?php
/**
 * Rate-Limiting Wrapper für AJAX-Endpunkte
 * Verhindert DoS-Angriffe durch Begrenzung von Anfragen pro IP/Session
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

// Session starten
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../src/RateLimiter.php';

use Security\RateLimiter;

/**
 * Überprüft die Rate-Limitierung für eine Anfrage
 * 
 * @param string $identifier Identifikator (IP-Adresse oder Session-ID)
 * @param int $limit Maximale Anfragen im Fenster
 * @param int $window Zeitfenster in Sekunden
 * @param string $key Vorfix für den Rate-Limit-Key
 * @return array ['allowed' => bool, 'retryAfter' => int, 'remaining' => int]
 */
function checkRateLimit($identifier = null, $limit = null, $window = null, $key = null) {
    global $argv;
    
    // Identifikator aus Argumenten oder Session holen
    if ($identifier === null) {
        $identifier = $_SESSION['rate_limit_key'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    // Key generieren
    if ($key === null) {
        $key = 'ajax_' . $identifier;
    }
    
    $limiter = RateLimiter::getInstance();
    
    if (!$limiter->isAllowed($key, $limit, $window)) {
        // Rate Limit überschritten
        $retryAfter = $limiter->getRetryAfter($key, $limit);
        return [
            'allowed' => false,
            'retryAfter' => $retryAfter,
            'remaining' => 0
        ];
    }
    
    // Anfrage erlaubt, verbleibende Anfragen ermitteln
    $remaining = $limiter->getRemaining($key, $limit);
    
    // Session-Key speichern
    $_SESSION['rate_limit_key'] = $key;
    
    return [
        'allowed' => true,
        'retryAfter' => 0,
        'remaining' => $remaining
    ];
}

/**
 * AJAX-Response mit Rate Limit Info zurückgeben
 * 
 * @param int $retryAfter Sekunden bis Reset
 * @param int $remaining Verbleibende Anfragen
 */
function rateLimitErrorResponse($retryAfter, $remaining) {
    header('Content-Type: application/json; charset=utf-8');
    header('Retry-After: ' . $retryAfter);
    header('X-RateLimit-Remaining: ' . $remaining);
    header('X-RateLimit-Reset: ' . (time() + $retryAfter));
    
    http_response_code(429);
    echo json_encode([
        'status' => 'error',
        'message' => 'Too many requests. Please try again later.',
        'retryAfter' => $retryAfter
    ]);
    exit;
}

/**
 * CSRF-Token validieren und Rate-Limit prüfen
 * 
 * @param array $request Request-Daten
 * @param int $limit Maximale Anfragen
 * @param int $window Zeitfenster in Sekunden
 * @param string $key Vorfix für Rate-Limit-Key
 * @return bool true wenn Anfrage erlaubt
 */
function validateRequest($request, $limit = 100, $window = 60, $key = 'ajax') {
    // CSRF-Token prüfen
    if (!isset($request['csrf_token'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing CSRF token'
        ]);
        exit(false);
    }
    
    require_once __DIR__ . '/../src/csrf.php';
    
    if (!Security\CSRF::verifyToken($request['csrf_token'])) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid CSRF token'
        ]);
        exit(false);
    }
    
    // Rate-Limit prüfen
    $identifier = $_SESSION['rate_limit_key'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $rateCheck = checkRateLimit($identifier, $limit, $window, $key);
    
    if (!$rateCheck['allowed']) {
        rateLimitErrorResponse($rateCheck['retryAfter'], $rateCheck['remaining']);
    }
    
    return true;
}