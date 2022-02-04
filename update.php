<?php
include_once('functions.php');

if(isset($_POST["id"])){
    updateProfile($_POST["id"]);
}

function updateProfile($id){
    $playerData = getPlayerData($id);
    $playerName = $playerData["Name"];
    $sumid = $playerData["SumID"];
    $puuid = $playerData["PUUID"];
    $masteryData = getMasteryScores($sumid);
    $rankData = getCurrentRank($sumid);
    $matchids = getMatchIDs($puuid);
    
    $jsonArray = array();
    $jsonArray["PlayerData"] = $playerData;
    $jsonArray["RankData"] = $rankData;
    $jsonArray["MasteryData"] = $masteryData;
    $jsonArray["MatchIDs"] = $matchids;
    
    if($sumid != ""){
        if(file_exists('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json')){
            $existingJson =  file_get_contents('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json');
        } else { $existingJson = ""; } // else empty $existingJson string so following if-statement forced into else part
        
        $fp = fopen('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json', 'c');
        // Open the file only to write. If it doesnt exist it will be created. If it exists it won't be truncated (would result in permanent delete-create loop)
        
        if($existingJson == json_encode($jsonArray)){
            // If current existing file equals the new downloaded array data do nothing
        } else {
            // Else update the current existing (or not existing) file with the newest data
            fwrite($fp, json_encode($jsonArray));
            fclose($fp);
        }
        $playerDataArray = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json'), true);
        foreach($playerDataArray["MatchIDs"] as $match){
            if(!file_exists('/var/www/html/wordpress/clashapp/data/matches/'.$match.'.json')){
                getMatchByID($match, $playerName);
            }

        }
    }
}

?>