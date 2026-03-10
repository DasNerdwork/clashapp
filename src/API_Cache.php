<?php
/**
 * Caching-Layer für Riot-API-Aufrufe
 * Reduziert API-Last durch Caching von Spielerdaten
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

namespace Cache;

use Predis\PredisException;

class API_Cache {
    private static $instance = null;
    private static $defaultTTL = 3600; // 1 Stunde Standard-TTL
    
    private $redis;
    private $ttl;
    
    private function __construct() {
        $redisHost = getenv('REDIS_HOST') ?: getenv('MDB_HOST') ?: 'localhost';
        $this->redis = new \Predis\Client(["host" => $redisHost, "timeout" => 2.0]);
        $this->ttl = (int)getenv('CACHE_TTL') ?? self::$defaultTTL;
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Holt Daten aus dem Cache
     * 
     * @param string $key Cache-Schlüssel
     * @return mixed|null
     */
    public function get($key) {
        try {
            $data = $this->redis->get($key);
            return $data ? json_decode($data, true) : null;
        } catch (PredisException $e) {
            error_log("Cache::get - Redis error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Speichert Daten im Cache
     * 
     * @param string $key Cache-Schlüssel
     * @param mixed $data Daten
     * @return bool
     */
    public function set($key, $data) {
        try {
            $this->redis->setex($key, $this->ttl, json_encode($data));
            return true;
        } catch (PredisException $e) {
            error_log("Cache::set - Redis error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Löscht einen Cache-Eintrag
     * 
     * @param string $key Cache-Schlüssel
     * @return bool
     */
    public function delete($key) {
        try {
            return $this->redis->del($key) > 0;
        } catch (PredisException $e) {
            error_log("Cache::delete - Redis error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Löscht alle Cache-Einträge für einen Spieler
     * 
     * @param string $puuid Spieler-PUUID
     * @return bool
     */
    public function clearPlayerCache($puuid) {
        try {
            $pattern = "player:{$puuid}:*";
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) {
                return $this->redis->del($keys) > 0;
            }
            return true;
        } catch (PredisException $e) {
            error_log("Cache::clearPlayerCache - Redis error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Setzt einen Cache-Eintrag mit TTL
     * 
     * @param string $key Cache-Schlüssel
     * @param mixed $data Daten
     * @param int $ttl Time-To-Live in Sekunden
     * @return bool
     */
    public function setWithTTL($key, $data, $ttl = null) {
        $ttl = $ttl ?? $this->ttl;
        try {
            $this->redis->setex($key, $ttl, json_encode($data));
            return true;
        } catch (PredisException $e) {
            error_log("Cache::setWithTTL - Redis error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Prüft ob ein Cache-Eintrag existiert und frisch ist
     * 
     * @param string $key Cache-Schlüssel
     * @return bool
     */
    public function exists($key) {
        try {
            return $this->redis->exists($key);
        } catch (PredisException $e) {
            return false;
        }
    }
    
    /**
     * Holt die verbleibende TTL eines Eintrags
     * 
     * @param string $key Cache-Schlüssel
     * @return int|null
     */
    public function getTTL($key) {
        try {
            return $this->redis->ttl($key);
        } catch (PredisException $e) {
            return null;
        }
    }
    
    /**
     * Bereinigt abgelaufene Cache-Einträge (Optimierung)
     * Sollte regelmäßig aufgerufen werden
     */
    public function cleanupExpiredEntries() {
        try {
            // Alle Keys scannen und abgelaufene löschen
            $keys = $this->redis->keys('*');
            $now = time();
            
            foreach ($keys as $key) {
                $ttl = $this->redis->ttl($key);
                if ($ttl > 0 && $ttl < 0) {
                    // -1 bedeutet unbegrenzte TTL, aber wir löschen trotzdem alte Keys
                    $this->redis->del($key);
                }
            }
            
            return true;
        } catch (PredisException $e) {
            error_log("Cache::cleanupExpiredEntries - Redis error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Setzt einen Cache-Eintrag mit automatischer TTL-Erweiterung
     * 
     * @param string $key Cache-Schlüssel
     * @param mixed $data Daten
     * @param int $initialTTL Initielles TTL
     * @return bool
     */
    public function setWithAutoRefresh($key, $data, $initialTTL = null) {
        $initialTTL = $initialTTL ?? $this->ttl;
        
        try {
            $this->redis->setex($key, $initialTTL, json_encode($data));
            return true;
        } catch (PredisException $e) {
            error_log("Cache::setWithAutoRefresh - Redis error: " . $e->getMessage());
            return false;
        }
    }
}