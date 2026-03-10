<?php
/**
 * Sicherheitsklassen für das ClashApp Projekt
 * Enthält Funktionen für Input-Validierung, Output-Encoding und CSRF-Schutz
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

namespace Security;

class Security {
    private static $championHashes = [];
    private static $loaded = false;
    
    /**
     * Lädt Champion-Hashes aus der Champion-JSON
     * Verwendet Hash-basierte Validierung anstelle von String-Vergleich
     */
    public static function loadChampionHashes($championJsonPath) {
        if (self::$loaded) return;
        
        try {
            $championData = json_decode(file_get_contents($championJsonPath), true);
            if (!$championData || !isset($championData['data'])) {
                throw new \Exception('Invalid champion data');
            }
            
            foreach ($championData['data'] as $id => $champion) {
                // Erstelle einen HMAC-Hash des Champion-Namens
                $hash = hash_hmac('sha256', $champion['name'], self::getSalt());
                // Normalisiere den Namen (kleinbuchstaben, keine Leerzeichen)
                $normalized = strtolower(preg_replace('/\s+/', '', $champion['name']));
                
                self::$championHashes[$id] = [
                    'id' => $id,
                    'name' => $champion['name'],
                    'hash' => $hash,
                    'normalized' => $normalized
                ];
            }
            
            self::$loaded = true;
            return true;
        } catch (\Exception $e) {
            error_log("Security::loadChampionHashes - Failed to load champion hashes: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lädt den Secret Salt aus der .env Datei
     * 
     * @return string Der Secret Salt
     */
    private static function getSalt(): string {
        $envPath = '/hdd1/clashapp/.env';
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath);
            $lines = explode("\n", $content);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'SECURITY_SALT=') === 0) {
                    return str_replace('SECURITY_SALT=', '', $line);
                }
            }
        }
        return 'CLASHAPP_SECURITY_SALT_2026';
    }
    
    /**
     * Validiert einen Champion-Request mit Hash-Vergleich
     * Verhindert Code Injection durch Unicode-Zeichen und andere Tricks
     * 
     * @param string $champId Champion ID
     * @param string $champName Champion Name
     * @return array ['valid' => bool, 'reason' => string|null, 'champion' => array|null]
     */
    public static function validateChampion($champId, $champName) {
        if (!self::$loaded) {
            $championPath = '/hdd1/clashapp/data/patch/version.txt';
            if (!file_exists("/hdd1/clashapp/data/patch/{$championPath}/data/de_DE/champion.json")) {
                $championPath = '/hdd1/clashapp/data/patch/version.txt';
                if (!file_exists("/hdd1/clashapp/data/patch/{$championPath}/data/en_US/champion.json")) {
                    return ['valid' => false, 'reason' => 'Champion data not available'];
                }
            }
            self::loadChampionHashes("/hdd1/clashapp/data/patch/{$championPath}/data/en_US/champion.json");
        }
        
        if (!isset(self::$championHashes[$champId])) {
            return ['valid' => false, 'reason' => 'Unknown champion ID'];
        }
        
        $champion = self::$championHashes[$champId];
        $salt = self::getSalt();
        
        // Normalisierten Vergleich (ignoriert Leerzeichen und Unicode-Tricks)
        $normalizedInput = strtolower(preg_replace('/\s+/', '', (string)$champName));
        $normalizedExpected = $champion['normalized'];
        
        if ($normalizedInput !== $normalizedExpected) {
            return ['valid' => false, 'reason' => 'Champion name mismatch'];
        }
        
        // Hash-Verifikation (zusätzliche Sicherheit)
        $inputHash = hash_hmac('sha256', (string)$champName, $salt);
        if ($inputHash !== $champion['hash']) {
            return ['valid' => false, 'reason' => 'Hash verification failed'];
        }
        
        return ['valid' => true, 'champion' => $champion];
    }
    
    /**
     * Bereinigt Eingabedaten (XSS Schutz)
     * Entfernt HTML-Tags und escapediert special characters
     * 
     * @param string $input Eingabe
     * @param int $flags htmlspecialchars Flags (default: ENT_QUOTES | ENT_HTML5)
     * @return string Ge Cleansed Input
     */
    public static function sanitize($input, $flags = ENT_QUOTES | ENT_HTML5, $encoding = 'UTF-8') {
        if (empty($input)) return '';
        
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, $flags, $encoding);
        
        return $input;
    }
    
    /**
     * Validiert einen Spieler-Namen
     * Nur erlaubte Zeichen (Buchstaben, Zahlen, #, Leerzeichen)
     * 
     * @param string $name Spielername
     * @return bool
     */
    public static function isValidPlayerName($name) {
        if (empty($name)) return false;
        
        // Erlaubt: Buchstaben (englisch/deutsch), Zahlen, #, Leerzeichen
        return preg_match('/^[a-zA-ZäöüÄÖÜß0-9\s#-]+$/', $name) === 1;
    }
    
    /**
     * Escaptiert Output für sicherer AJAX-Output
     * Verhindert XSS durch JSON-Escape
     * 
     * @param string $data Daten zum Escaptieren
     * @return string Escapteter String
     */
    public static function escapeOutput($data) {
        return addslashes($data);
    }
}