<?php
require '/hdd1/clashapp/vendor/autoload.php';

// Verbindung zu Redis herstellen (Standardkonfiguration: localhost, Port 6379)
$redis = new Predis\Client();

// Funktion zum Hinzufügen von Anfragen zur Warteschlange
function addToQueue($queueName, $type, $data) {
    global $redis;
    $timestamp = microtime();
    $item = [
        'type' => $type,
        'timestamp' => $timestamp,
        'data' => $data,
    ];
    $redis->rpush($queueName, json_encode($item));
    return $timestamp;
}