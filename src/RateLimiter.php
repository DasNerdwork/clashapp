<?php
/**
 * Rate-Limiter für API-Endpunkte
 * Verhindert DoS-Angriffe durch limitieren von Anfragen pro IP/Session
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

namespace Security;

use Predis\PredisException;

class RateLimiter {
    private static $instance = null;
    private static $inMemoryRequests = []; // Fallback wenn Redis nicht verfügbar
    private static $inMemoryMaxSize = 1000; // Begrenzung für In-Memory Storage
    private static $inMemoryTTL = 60; // TTL in Sekunden für In-Memory Einträge
    
    private $redis;
    private $defaultLimit = 100;
    private $defaultWindow = 60; // 60 Sekunden
    
    private function __construct() {
        $redisHost = getenv('REDIS_HOST') ?: getenv('MDB_HOST') ?: 'localhost';
        $this->redis = new \Predis\Client(["host" => $redisHost, "timeout" => 2.0]);
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prüft ob eine Anfrage erlaubt ist
     * 
     * @param string $key Identifikator (z.B. IP-Adresse)
     * @param int $limit Maximale Anfragen im Fenster
     * @param int $window Zeitfenster in Sekunden
     * @return bool
     */
    public function isAllowed($key, $limit = null, $window = null) {
        $limit = $limit ?? $this->defaultLimit;
        $window = $window ?? $this->defaultWindow;
        
        try {
            return $this->checkRedis($key, $limit, $window);
        } catch (\Exception $e) {
            // Fallback auf In-Memory-Limitierung
            return $this->checkInMemory($key, $limit, $window);
        }
    }
    
    /**
     * Redis-basierte Rate-Limitierung
     */
    private function checkRedis($key, $limit, $window) {
        $now = time();
        $identifier = "{$key}:{$now}";
        
        // Schlüsselsuffix für Redis
        $redisKey = "ratelimit:{$key}";
        
        try {
            // Ältere Einträge löschen (älter als Zeitfenster)
            $this->redis->zremrangebyscore($redisKey, 0, $now - $window);
            
            // Neuen Eintrag hinzufügen (Score = Zeitstempel)
            $this->redis->zadd($redisKey, $now, $identifier);
            
            // Anzahl der Einträge im Fenster prüfen
            $count = $this->redis->zcard($redisKey);
            
            if ($count > $limit) {
                // Limit überschritten
                $this->redis->zremrangebyscore($redisKey, 0, $now - $window);
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Redis Rate Limit check failed: ' . $e->getMessage());
        }
    }
    
    /**
     * In-Memory Rate-Limitierung (Fallback)
     */
    private function checkInMemory($key, $limit, $window) {
        $now = time();
        $windowStart = $now - $window;
        
        // Sicherstellen, dass Key existiert
        if (!isset(self::$inMemoryRequests[$key])) {
            self::$inMemoryRequests[$key] = [];
        }
        
        // Einträge älter als Fenster entfernen
        self::$inMemoryRequests[$key] = array_values(
            array_filter(self::$inMemoryRequests[$key], fn($t) => $t > $windowStart)
        );
        
        // Max-Größe des In-Memory Storage begrenzen
        if (count(self::$inMemoryRequests) > self::$inMemoryMaxSize) {
            // Älteste Einträge löschen
            ksort(self::$inMemoryRequests);
            $keysToRemove = array_keys(self::$inMemoryRequests, null, true);
            $keysToRemove = array_slice($keysToRemove, 0, count($keysToRemove) - self::$inMemoryMaxSize);
            foreach ($keysToRemove as $k) {
                unset(self::$inMemoryRequests[$k]);
            }
        }
        
        // Limit prüfen
        if (count(self::$inMemoryRequests[$key]) >= $limit) {
            return false;
        }
        
        // Neuen Eintrag hinzufügen
        self::$inMemoryRequests[$key][] = $now;
        
        // Bereinige alte Einträge nach TTL
        self::cleanupInMemory();
        
        return true;
    }
    
    /**
     * Bereinigt abgelaufene Einträge im In-Memory Storage
     */
    private static function cleanupInMemory() {
        $now = time();
        $ttl = self::$inMemoryTTL;
        
        foreach (self::$inMemoryRequests as $key => $timestamps) {
            // Alle Einträge älter als TTL entfernen
            self::$inMemoryRequests[$key] = array_values(
                array_filter(self::$inMemoryRequests[$key], fn($t) => $t > $now - $ttl)
            );
            
            // Leere Keys entfernen
            if (empty(self::$inMemoryRequests[$key])) {
                unset(self::$inMemoryRequests[$key]);
            }
        }
    }
    
    /**
     * Erhält die verbleibenden Anfragen für eine Key/Zeitkombination
     * 
     * @param string $key Identifikator
     * @param int $limit Maximale Anfragen
     * @return int Verbleibende Anfragen
     */
    public function getRemaining($key, $limit = null) {
        $limit = $limit ?? $this->defaultLimit;
        
        try {
            $redisKey = "ratelimit:{$key}";
            $now = time();
            
            $this->redis->zremrangebyscore($redisKey, 0, $now - $this->defaultWindow);
            $count = $this->redis->zcard($redisKey);
            
            return max(0, $limit - $count);
        } catch (\Exception $e) {
            // Fallback
            if (!isset(self::$inMemoryRequests[$key])) {
                self::$inMemoryRequests[$key] = [];
            }
            $count = count(array_filter(self::$inMemoryRequests[$key], fn($t) => $t > time() - $this->defaultWindow));
            self::cleanupInMemory(); // Bereinige nach TTL
            return max(0, $limit - $count);
        }
    }
    
    /**
     * Resetet das Rate-Limit für einen Key (nur für Admins)
     * 
     * @param string $key Identifikator
     * @return bool
     */
    public function reset($key) {
        try {
            $redisKey = "ratelimit:{$key}";
            return $this->redis->del($redisKey) > 0;
        } catch (\Exception $e) {
            if (isset(self::$inMemoryRequests[$key])) {
                unset(self::$inMemoryRequests[$key]);
                return true;
            }
            return false;
        }
    }
    
    /**
     * Erhält den Retry-After Header Wert (Sekunden bis Limit reset)
     * 
     * @param string $key Identifikator
     * @param int $limit Maximale Anfragen
     * @return int Sekunden bis Reset
     */
    public function getRetryAfter($key, $limit = null) {
        $limit = $limit ?? $this->defaultLimit;
        
        try {
            $redisKey = "ratelimit:{$key}";
            $now = time();
            
            $this->redis->zremrangebyscore($redisKey, 0, $now - $this->defaultWindow);
            $count = $this->redis->zcard($redisKey);
            
            if ($count < $limit) {
                return 0; // Noch nicht limitiert
            }
            
            // Nächster Eintrag im Fenster
            $nextEntry = $this->redis->zrange($redisKey, $count - 1, $count - 1, true);
            if (!empty($nextEntry)) {
                return (int)($nextEntry[time()] - $now) + 1;
            }
            
            return $this->defaultWindow;
        } catch (\Exception $e) {
            self::cleanupInMemory(); // Bereinige nach TTL
            return $this->defaultWindow;
        }
    }
}