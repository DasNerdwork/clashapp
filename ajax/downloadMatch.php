<?php
include_once('/hdd1/clashapp/src/functions.php');
if(isset($_POST['matches'], $_POST['playerName'])){
    $logPath = '/var/www/html/clash/clashapp/data/logs/matchDownloader.log'; // The log patch where any additional info about this process can be found
    $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
    $matchesArray = json_decode($_POST['matches']);
    foreach ($matchesArray as $matchID) {
        if (!isValidMatchID($matchID)) {
            $errMsg = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - ERROR]: Unable to verify that ".$matchID." has a valid format.";
            file_put_contents($logPath, $errMsg.PHP_EOL , FILE_APPEND | LOCK_EX);
            die("Invalid match ID: " . $matchID);
        }
    }
    if(!isValidPlayerName($_POST['playerName'])){
        $errMsg = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - ERROR]: Unable to verify that ".$_POST['playerName']." is a valid playerName.";
        file_put_contents($logPath, $errMsg.PHP_EOL , FILE_APPEND | LOCK_EX);
        die("Invalid playerName: " . $_POST['playerName']);
    }
    downloadMatchesByID(json_decode($_POST['matches']), $_POST['playerName']); // asynchronously downloads all matches by matchid after page has loaded
    // addToQueue('api_queue', 'downloadMatches', ['matchids' => $_POST['matches'], 'username' => $_POST['playerName']]); // DEPRECATED
    clearstatcache(true, $logPath); // Used for proper filesize calculation
    $endofup = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: End of update for \"" . $_POST['playerName'] . "\" - (Final Matchcount: ".count(json_decode($_POST['matches'])).", Approximate Logsize: ".number_format((filesize($logPath)/1048576), 3)." MB)";
    file_put_contents($logPath, $endofup.PHP_EOL , FILE_APPEND | LOCK_EX);
}
?>