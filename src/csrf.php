<?php
/**
 * CSRF-Token-Manager für AJAX-Endpunkte
 * Verhindert Cross-Site Request Forgery Angriffe
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

namespace Security;

class CSRF {
    private static $token = null;
    private static $tokenExpiry = 3600; // 1 Stunde Standard-TTL
    private static $tokenStore = []; // Speichert Token mit Expiry-Zeit
    
    /**
     * Generiert ein neues CSRF-Token
     * 
     * @return string CSRF-Token
     */
    public static function generateToken() {
        if (self::$token === null) {
            self::$token = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = self::$token;
            $_SESSION['csrf_token_expiry'] = time() + self::$tokenExpiry; // <-- neu
        }
        return self::$token;
    }
    
    /**
     * Verifiziert ein CSRF-Token
     * 
     * @param string $token Token zum Verifizieren
     * @return bool true wenn Token gültig
     */
    public static function verifyToken($token) {
        // Token muss existieren
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        if (!isset($_SESSION['csrf_token_expiry']) || time() > $_SESSION['csrf_token_expiry']) {
            unset($_SESSION['csrf_token'], $_SESSION['csrf_token_expiry']);
            return false;
        }
        
        // Token muss mit gespeichertem Token übereinstimmen (timing-attack safe)
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Erhält das aktuelle CSRF-Token
     * 
     * @return string CSRF-Token
     */
    public static function getToken() {
        if (!isset($_SESSION['csrf_token'])) {
            self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Regeneriert das CSRF-Token (neu generiert)
     * Sollte nach Login oder wichtigen Aktionen aufgerufen werden
     * 
     * @return string Neues CSRF-Token
     */
    public static function regenerateToken() {
        self::$token = null;
        return self::generateToken();
    }
    
    /**
     * Löscht ein Token aus dem Speicher
     * 
     * @param string $token Token zum löschen
     * @return bool true wenn erfolgreich
     */
    public static function invalidateToken($token) {
        if (isset(self::$tokenStore[$token])) {
            unset(self::$tokenStore[$token]);
            if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token) {
                unset($_SESSION['csrf_token']);
            }
            return true;
        }
        return false;
    }
    
    /**
     * Setzt die Token-Expiry-Dauer (Sekunden)
     * 
     * @param int $seconds Expiry in Sekunden
     */
    public static function setExpiry($seconds) {
        self::$tokenExpiry = $seconds;
    }
    
    /**
     * Erhält die aktuelle Token-Expiry-Dauer
     * 
     * @return int Expiry in Sekunden
     */
    public static function getExpiry() {
        return self::$tokenExpiry;
    }
    
    /**
     * Bereinigt abgelaufene Tokens (saubert up)
     */
    public static function cleanupExpiredTokens() {
        $now = time();
        $keysToRemove = array_filter(self::$tokenStore, function($expiry) use ($now) {
            return $expiry < $now;
        });
        
        foreach ($keysToRemove as $key) {
            unset(self::$tokenStore[$key]);
            if (isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $key) {
                unset($_SESSION['csrf_token']);
            }
        }
    }
    
    /**
     * Zentrale CSRF-Validierung für POST/PUT/DELETE Requests
     * 
     * @param string $method HTTP Methode
     * @param string $message Fehlermeldung wenn ungültig
     * @param int $statusCode HTTP Status Code
     * @return bool true wenn gültig, false wenn ungültig
     */
    public static function validateRequest($method = null, $message = 'CSRF token validation failed', $statusCode = 403) {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            return true; // Keine Anfrage
        }
        
        // Nur für schreibende Methoden validieren
        $writeMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];
        $method = $method ?? $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $writeMethods)) {
            return true; // Nur schreibende Methoden validieren
        }
        
        // Token aus Request holen
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? $_REQUEST['csrf_token'] ?? null;
        
        if (!$token) {
            // Wenn kein Token im Request, aber Session-Var existiert, nutze diese
            // (für Fälle wo Frontend Token im URL-Param sendet)
            if (isset($_SESSION['csrf_token'])) {
                return self::verifyToken($_SESSION['csrf_token']);
            }
            return false;
        }
        
        return self::verifyToken($token);
    }
    
    /**
     * Regeneriert das Token und gibt das neue zurück
     * Sollte nach Login, Logout oder anderen sensiblen Aktionen aufgerufen werden
     * 
     * @return string Neues CSRF-Token
     */
    public static function regenerate() {
        self::regenerateToken();
        // Alte Tokens aus Store entfernen
        self::$tokenStore = [];
        return self::getToken();
    }
}