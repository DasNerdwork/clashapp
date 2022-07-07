<?php
include_once('functions.php');

// print_r($_REQUEST);
// php -r "require 'functions.php'; testing();"

if(isset($_POST["username"])){
    updateProfile($_POST["username"], 150);
}

function updateProfile($id, $maxMatchIds, $type="name"){
    $playerData = getPlayerData($type,$id);
    $playerName = $playerData["Name"];
    $sumid = $playerData["SumID"];
    $puuid = $playerData["PUUID"];
    $masteryData = getMasteryScores($sumid);
    $rankData = getCurrentRank($sumid);
    $matchids = getMatchIDs($puuid, $maxMatchIds);
    $jsonArray = array();
    $jsonArray["PlayerData"] = $playerData;
    $jsonArray["RankData"] = $rankData;
    $jsonArray["MasteryData"] = $masteryData;
    $jsonArray["MatchIDs"] = $matchids;
    $logPath = '/var/www/html/wordpress/clashapp/data/logs/matchDownloader.log';
    
    if($sumid != ""){
        if(file_exists('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json')){
            $existingJson = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json'), true);
            
            if(getMatchIDs($puuid, 1)[0] == $existingJson["MatchIDs"][0] && (count($existingJson["MatchIDs"]) >= count($matchids))){//newest local matchid equels api first 
                echo '{"status":"up-to-date"}';return;
            }
        } else { 
            $existingJson = ""; 
        } // else empty $existingJson string so following if-statement forced into else part
        
        $fp = fopen('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json', 'w');
        // Open the file only to write. If it doesnt exist it will be created. If it exists it won't be truncated (would result in permanent delete-create loop)
        
        if($existingJson == json_encode($jsonArray)){
            fclose($fp);
            // If current existing file equals the new downloaded array data do nothing
        } else {
            // Else update the current existing (or not existing) file with the newest data
            // echo "<pre>";
            // print_r($jsonArray);
            // echo "</pre>";die();
            fwrite($fp, json_encode($jsonArray));
            fclose($fp);
        }
        $playerDataArray = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json'), true);
        foreach($playerDataArray["MatchIDs"] as $match){
            if(!file_exists('/var/www/html/wordpress/clashapp/data/matches/'.$match.'.json')){
                downloadMatchByID($match, $playerName);
            }
        }
        clearstatcache(true, $logPath);
        $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $endofup = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: End of update for \"" . $playerName . "\" - (Final Matchcount: ".count($playerDataArray["MatchIDs"]).", Approximate Filesize: ".number_format((filesize($logPath)/1048576), 3)." MB)";
        $border = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: -------------------------------------------------------------------------------------";
        file_put_contents($logPath, $endofup.PHP_EOL , FILE_APPEND | LOCK_EX);
        file_put_contents($logPath, $border.PHP_EOL , FILE_APPEND | LOCK_EX);
        echo '{"status":"updated"}';
        if($maxMatchIds > 75){
        }else if($maxMatchIds == 75){
            // echo "<script>location.reload()</script>";
        }

    }
}

?>