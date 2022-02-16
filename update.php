<?php
include_once('functions.php');

// print_r($_REQUEST);

if(isset($_POST["username"])){
    updateProfile($_POST["username"], 150);
    // echo $_POST["username"]; // ERROR <-- Postet nicht mehr von profile.php zu update.php
    // die;
}

function updateProfile($id, $maxMatchIds){
    $playerData = getPlayerData($id);
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
    
    if($sumid != ""){
        if(file_exists('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json')){
            $existingJson = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json'), true);
            
            if(getMatchIDs($puuid, 1)[0] == $existingJson["MatchIDs"][0] && (count($existingJson["MatchIDs"]) >= count($matchids))){//newest local matchid equels api first 
                echo '{"status":"up-to-date"}';die();
            }
        } else { $existingJson = ""; } // else empty $existingJson string so following if-statement forced into else part
        
        $fp = fopen('/var/www/html/wordpress/clashapp/data/player/'.$sumid.'.json', 'c');
        // Open the file only to write. If it doesnt exist it will be created. If it exists it won't be truncated (would result in permanent delete-create loop)
        
        if($existingJson == json_encode($jsonArray)){
            fclose($fp);
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
        if($maxMatchIds > 75){
            echo '{"status":"updated"}';
        }else if($maxMatchIds == 75){
            // echo "<script>location.reload()</script>";
        }

    }
}

?>