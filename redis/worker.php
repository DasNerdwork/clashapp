<?php
require '/hdd1/clashapp/vendor/autoload.php';
include_once('/hdd1/clashapp/functions.php');
include_once('/hdd1/clashapp/redis/redis.php');

// Verbindung zu Redis herstellen (Standardkonfiguration: localhost, Port 6379)
$logPath = '/hdd1/clashapp/data/logs/queue.log';

// Define rate limits
$rateLimits = [
    'requests_1s' => 20,      // 20 requests every 1 second
    'requests_2m' => 100,     // 100 requests every 2 minutes
];

// Funktion zum Verarbeiten der Warteschlange
function processQueue() {
    global $redis;
    global $logPath;
    global $rateLimits;

    while (true) {
        // Check and reset counters for the last second
        $currentTime = time();

        // Check and reset counters for the last second
        $redis->multi();

        // Increment and get counters for the last second
        $currentSecondCount = $redis->incr('api_queue_counter_1s');
        $currentMinuteCount = $redis->incr('api_queue_counter_2m');

        // Expire the counters after 2 minutes to reset them
        $redis->expire('api_queue_counter_1s', 2);
        $redis->expire('api_queue_counter_2m', 120);

        $results = $redis->exec();

        $currentSecondCount = $results[0];
        $currentMinuteCount = $results[1];


        $sleepTime = max(0, min(1, $rateLimits['requests_1s'] - $currentSecondCount)) * 1000000; // Sleep in microseconds

        // Process the queue (this can be expanded based on your actual queue processing logic)
        if ($currentSecondCount <= $rateLimits['requests_1s'] && $currentMinuteCount <= $rateLimits['requests_2m']) {
            $queueData = $redis->lrange('api_queue', 0, 0);
            if (!empty($queueData)) {
                $item = json_decode($queueData[0], true);

                if (is_array($item) && array_key_exists('type', $item)) {
                    switch ($item['type']) {
                        case 'playerData':
                            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $logPrefix = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - INFO]: ";
                            file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                            if(handlePlayerData($item['data']['type'], $item['data']['id'])){
                                file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                                removeFromQueue($item);
                            }
                            break;
                        case 'masteryScores':
                            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $logPrefix = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - INFO]: ";
                            file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                            if(handleMasteryScores($item['data']['puuid'])){
                                file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                                removeFromQueue($item);
                            }
                            break;
                        case 'currentRank':
                            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $logPrefix = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - INFO]: ";
                            file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                            if(handleCurrentRank($item['data']['sumid'])){
                                file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                                removeFromQueue($item);
                            }
                            break;
                        case 'matchIds':
                            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $logPrefix = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - INFO]: ";
                            file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                            if(handleMatchIds($item['data']['puuid'], $item['data']['maxMatchIDs'])){
                                file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                                removeFromQueue($item);
                            }
                            break;
                        case 'downloadMatches':
                            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $logPrefix = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - INFO]: ";
                            file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                            if(handleDownloadMatches($item['data']['matchids'], $item['data']['username'] = null)){
                                file_put_contents($logPath, $logPrefix, FILE_APPEND | LOCK_EX);
                                removeFromQueue($item);
                            }
                            break;
                        default:
                            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                            $logString = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - WARNING]: Somehow got default type in switch case! Secure removed from queue.";
                            file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
                            removeFromQueue($item);
                            break;
                    }
                }
            }
        } else {
            // Calculate the time to sleep to stay within rate limits
            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $logString = "[" . $currentTime->format('d.m.Y H:i:s') . "] [apiQueue - WARNING]: Rate limit exceeded for at least one type.";
            // file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
            
            echo "\rRate Limit exceeded. Waiting for ".(($sleepTime/1000)/1000).'s                                          ';
            usleep($sleepTime);
        }
    }
}

// Funktion zum Entfernen eines Elements aus der Warteschlange
function removeFromQueue($queueElement) {
    global $logPath;
    $logString = "Successfully handled and finished request";
    file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
    global $redis;
    echo "\r Request with timestamp ".$queueElement['timestamp']." handled.                                              ";

    $redis->rpush('finished_queue', $queueElement['timestamp']);
    $redis->lrem('api_queue', 1, json_encode($queueElement));
}

function handlePlayerData($type, $id){
    global $logPath;
    $logString = "Called handlePlayerData with type {$type} and id {$id}";
    file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
    return true;
}

function handleMasteryScores($puuid){
    global $logPath;
    $logString = "Called handleMasteryScores with puuid {$puuid}";
    file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
    return true;
}

function handleCurrentRank($sumid){
    global $logPath;
    $logString = "Called handleCurrentRank with sumid {$sumid}";
    file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
    return true;
}

function handleMatchIds($puuid, $maxMatchIDs){
    global $logPath;
    $logString = "Called handleMatchIds with puuid {$puuid} and maxMatchIDs {$maxMatchIDs}";
    file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
    return true;
}

function handleDownloadMatches($matchids, $username = null){
    global $logPath;
    $matchids = json_encode($matchids);
    $logString = "Called handleDownloadMatches with matchids {$matchids}";
    if($username != null){ $logString .= " and username {$username}"; } else { $logString .= ""; }
    file_put_contents($logPath, $logString.PHP_EOL , FILE_APPEND | LOCK_EX);
    return true;
}

// Führe den Worker-Prozess aus
processQueue();
