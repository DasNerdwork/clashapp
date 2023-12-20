<?php
include_once('/hdd1/clashapp/functions.php');

if(isset($_POST['matches']) && isset($_POST['playerName'])){
    downloadMatchesByID(json_decode($_POST['matches']), $_POST['playerName']); // asynchronously downloads all matches by matchid after page has loaded
    $logPath = '/var/www/html/clash/clashapp/data/logs/matchDownloader.log'; // The log patch where any additional info about this process can be found
    clearstatcache(true, $logPath); // Used for proper filesize calculation
    $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
    $endofup = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: End of update for \"" . $_POST['playerName'] . "\" - (Final Matchcount: ".count(json_decode($_POST['matches'])).", Approximate Logsize: ".number_format((filesize($logPath)/1048576), 3)." MB)";
    file_put_contents($logPath, $endofup.PHP_EOL , FILE_APPEND | LOCK_EX);
}
?>