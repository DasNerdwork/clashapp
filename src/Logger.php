<?php
/**
 * Syslog-basierter Logger mit Dateifallback und Rotation
 * Verwendet syslog() für systemweite Logging und begrenzte Dateispeicherung mit Rotation
 * 
 * @author DasNerdwork
 * @copyright 2026
 */

namespace Logging;

use Predis\PredisException;
use Exception;

class Logger {
    private static $instance = null;
    
    private $syslogTag;
    private $logLevels = ['DEBUG' => LOG_DEBUG, 'INFO' => LOG_INFO, 'WARNING' => LOG_WARNING, 'ERROR' => LOG_ERR, 'CRITICAL' => LOG_CRIT];
    private $maxFileSize = 10 * 1024 * 1024; // 10MB
    private $logDir = '/hdd1/clashapp/data/logs';
    private $rotationThreshold = 5 * 1024 * 1024; // 5MB pro Datei
    private $maxLogFiles = 5; // Maximale Anzahl an Rotationsdateien
    
    private function __construct($tag = 'clashapp') {
        $this->syslogTag = $tag;
        // Sicherstellen, dass Log-Verzeichnis existiert
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    public static function getInstance($tag = 'clashapp') {
        if (self::$instance === null) {
            self::$instance = new self($tag);
        }
        return self::$instance;
    }
    
    /**
     * Loggt eine Nachricht via syslog und optional in Datei
     */
    private function log($level, $message) {
        $priority = $this->logLevels[$level] ?? $this->logLevels['INFO'];
        
        // Syslog-Eintrag (systemweite Logging)
        @syslog($priority, "[{$this->syslogTag}] {$message}");
        
        // Optional: Auch in Datei (begrenzt auf 10MB mit Rotation)
        try {
            $this->logToFile($level, $message);
        } catch (Exception $e) {
            // Fehler beim Loggen nicht nochmal loggen (Vermeidung von Log-Fluss)
            error_log("Logger::logToFile - " . $e->getMessage(), 0, $this->logDir . '/error.log');
        }
    }
    
    /**
     * Schreibt Log-Eintrag in Datei mit Rotation
     */
    private function logToFile($level, $message) {
        $logFile = $this->logDir . '/' . strtolower($level) . '.log';
        $timestamp = date('Y-m-d H:i:s');
        
        $logEntry = "[{$timestamp}] [{$this->syslogTag}] [{$level}] {$message}\n";
        
        // Rotation durchführen wenn Datei zu groß ist
        $this->rotateLogFileIfNecessary($logFile);
        
        // Nur wenn Datei < 10MB
        if (filesize($logFile, true) < $this->maxFileSize) {
            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Rotiert Log-Datei wenn sie zu groß ist
     * Verschiebt die aktuelle Datei und erstellt eine neue
     */
    private function rotateLogFileIfNecessary($logFile) {
        $currentSize = filesize($logFile, true);
        
        if ($currentSize > $this->rotationThreshold) {
            $newLogFile = $this->rotateLogFile($logFile);
            
            if ($newLogFile !== null) {
                // Neue leere Log-Datei erstellen
                @file_put_contents($logFile, '', LOCK_EX);
                
                // Alte Datei umbenennen (mit Zeitstempel)
                $timestamp = date('Y-m-d-His');
                $rotatedFile = $this->logDir . '/rotated_' . basename($logFile) . '_' . $timestamp;
                @rename($newLogFile, $rotatedFile);
                
                // Alte Rotationsdateien bereinigen
                $this->cleanupOldLogFiles($this->logDir, basename($logFile));
            }
        }
    }
    
    /**
     * Rotiert eine Log-Datei
     * 
     * @param string $logFile Pfad zur Log-Datei
     * @return string|null Pfad zur neuen Log-Datei oder null bei Fehler
     */
    private function rotateLogFile($logFile) {
        try {
            if (!file_exists($logFile)) {
                return null;
            }
            
            // Datei schließen und umbenennen
            $newLogFile = $logFile . '.1';
            
            if (file_exists($newLogFile)) {
                // Verschiebe .1 auf .2, .2 auf .3, etc.
                for ($i = 1; $i < $this->maxLogFiles; $i++) {
                    $oldFile = $logFile . '.' . $i;
                    $newFile = $logFile . '.' . ($i + 1);
                    
                    if (file_exists($oldFile)) {
                        @rename($oldFile, $newFile);
                    }
                }
            }
            
            return $newLogFile;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Bereinigt alte Log-Dateien
     * 
     * @param string $logDir Log-Verzeichnis
     * @param string $baseName Basisname der Log-Datei
     */
    private function cleanupOldLogFiles($logDir, $baseName) {
        try {
            $pattern = $logDir . '/' . $baseName . '.[0-9]+';
            $files = glob($pattern);
            
            if (!empty($files)) {
                // Dateien sortieren (älteste zuerst) und überschüssige löschen
                usort($files, function($a, $b) {
                    return filesize($b) - filesize($a);
                });
                
                $filesToDelete = array_slice($files, count($files) - $this->maxLogFiles + 1);
                
                foreach ($filesToDelete as $file) {
                    if (file_exists($file)) {
                        @unlink($file);
                    }
                }
            }
        } catch (Exception $e) {
            // Fehler beim Bereinigen ignorieren
        }
    }
    
    /**
     * Bereinigt alle Log-Dateien im Verzeichnis
     */
    public function cleanupAllLogs() {
        try {
            $logFiles = glob($this->logDir . '/*.log');
            
            foreach ($logFiles as $logFile) {
                $this->rotateLogFileIfNecessary($logFile);
                $this->cleanupOldLogFiles($this->logDir, basename($logFile));
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Logger::cleanupAllLogs - " . $e->getMessage(), 0, $this->logDir . '/error.log');
            return false;
        }
    }
    
    /**
     * Loggt eine Debug-Nachricht
     */
    public function debug($message) { 
        $this->log('DEBUG', $message); 
    }
    
    /**
     * Loggt eine Info-Nachricht
     */
    public function info($message) { 
        $this->log('INFO', $message); 
    }
    
    /**
     * Loggt eine Warning-Nachricht
     */
    public function warning($message) { 
        $this->log('WARNING', $message); 
    }
    
    /**
     * Loggt eine Error-Nachricht
     */
    public function error($message) { 
        $this->log('ERROR', $message); 
    }
    
    /**
     * Loggt eine Critical-Nachricht
     */
    public function critical($message) { 
        $this->log('CRITICAL', $message); 
    }
    
    /**
     * Loggt eine Exception
     */
    public function exception(Exception $exception) { 
        $message = get_class($exception) . ': ' . $exception->getMessage();
        $this->log('ERROR', $message);
        $this->log('ERROR', 'Stack trace: ' . $exception->getTraceAsString());
    }
    
    /**
     * Setzt die maximale Dateigröße (Bytes)
     * 
     * @param int $size Maximale Dateigröße in Bytes
     */
    public function setMaxFileSize($size) {
        $this->maxFileSize = $size;
    }
    
    /**
     * Setzt die Rotationsschwelle (Bytes)
     * 
     * @param int $size Rotationsschwelle in Bytes
     */
    public function setRotationThreshold($size) {
        $this->rotationThreshold = $size;
    }
    
    /**
     * Setzt die maximale Anzahl an Rotationsdateien
     * 
     * @param int $count Maximale Anzahl an Dateien
     */
    public function setMaxLogFiles($count) {
        $this->maxLogFiles = $count;
    }
}