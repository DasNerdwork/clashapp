<?php
include_once('functions.php');

/** update.php updates the player.json of a given user by a specific matchcount. This includes the count of give matches, matchids, mastery data, rank data, etc.
 *
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 * 
 * @param mixed $id As we can fetch the $playerData in three different ways (by summonername, sumid or puuid) we define the input here as $id and provide what kind of $id it is
 *                     via the $type argument. If none is provided we switch to the default of getPlayerData by summonername
 * @param int $maxMatchIds The maximum amount of matchids we want to update to/from via an API request
 * @param mixed $type Usually by "name" but can also be PUUID or SumID. Used for player data request variants
 * @var array $playerData API requested return json consisting of the entries Name (Playername in clean text), Playerlevel, PUUID, SumID, AccountID & the last change date
 * @var string $playerName The name of a player, e.g. DasNerdwork
 * @var string $sumid A unique ID used for one specific summoner. Riot has multiple types of these ID's for different internal uses which is why we don't only use one
 * @var string $puuid See description for $sumid
 * @var array $masteryData An array consisting of every necessery data to display the mastery scores of a summoners champions, consisting of the chamions Name, id, 
 *                              level, mastery points earned, the timestamp of the last time played aswell as any LvlUpTokens if available
 * @var array $rankData An array consisting of all rank specific data, including the queue type (solo due, flex, etc.), the tier, rank name, LP count & wins and loses
 * @var array $matchIDs An array including all all matchid's up to a given max-count
 * @var array $jsonArray This array combines all the arrays above (playerData, rankData, masteryData & matchData) into a single structure
 * @var array $existingJson If the file_exists check returns true, this array contains all of the current preexisting/local stored data in the same format as the $jsonArray
 * 
 * Example data of $_POST:
 * $_POST["username"] = "DasNerdwork"
 */

if(isset($_POST["username"])){
    // If function is explicitly called via a POST (e.g. by pressing the update button on a single users profile) start the function with a maximum Match ID fetch count of 150
    updateProfile($_POST["username"], 150);
}

// Fetch all the necessary data for updating or generating a single players player.json, stored in /clashapp/data/player/
function updateProfile($id, $maxMatchIds, $type="name"){
    if($id != ""){
        $playerData = getPlayerData($type,$id);
        $playerName = $playerData["Name"];
        $sumid = $playerData["SumID"];
        $puuid = $playerData["PUUID"];
        $masteryData = getMasteryScores($sumid);
        $rankData = getCurrentRank($sumid);
        $matchIDs = getMatchIDs($puuid, $maxMatchIds);
        $jsonArray = array();
        $jsonArray["PlayerData"] = $playerData;
        $jsonArray["RankData"] = $rankData;
        $jsonArray["MasteryData"] = $masteryData;
        $jsonArray["MatchIDs"] = $matchIDs;
        $logPath = '/var/www/html/clash/clashapp/data/logs/matchDownloader.log'; // The log patch where any additional info about this process can be found

        /**
         * STEP 1: Check if up-to-date
         */
        if($sumid != "" || $sumid != "/"){ /** @todo additional sanitizing regex check for valid $sumid variants */
            if(file_exists('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json')){
                $existingJson = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json'), true);
                // If the newest local matchID equals the newest API requested matchID, ergo if there is nothing to update
                // and if we have the same amount or more matchIDs stored locally (no better data to grab) 
                // if(getMatchIDs($puuid, 1)[0] == $existingJson["MatchIDs"][0] && (count($existingJson["MatchIDs"]) >= count($matchIDs))){
                //     echo '{"status":"up-to-date"}';
                //     return; // Stop this whole process from further actions below
                // }
                $return = true;
                foreach(getMatchIDs($puuid, 15) as $checkSingleMatch){
                    if(!in_array($checkSingleMatch, $existingJson["MatchIDs"])){
                        downloadMatchByID($checkSingleMatch, $playerName);
                        $return = false;
                    }
                }
                if($return){
                    return '{"status":"up-to-date"}';
                }


            } else { 
                // else empty $existingJson string so following if-statement forced into its else part
                $existingJson = ""; 
            }

            /**
             * STEP 2: Rewrite file if it doesn't exist or has to be updated
             */

            $fp = fopen('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json', 'w');
            // Open the file only to write. If it doesnt exist it will be created. If it exists it will be reset and updated with the newest data
            fwrite($fp, json_encode($jsonArray));
            fclose($fp);

            /**
             * STEP 3: Fetch all given matchIDs and download each match via downloadMatchByID
             */
            $playerDataArray = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json'), true);
            foreach($playerDataArray["MatchIDs"] as $match){
                if(!file_exists('/var/www/html/clash/clashapp/data/matches/'.$match.'.json')){
                    $downloadReturn = downloadMatchByID($match, $playerName);
                    if(($downloadReturn["Status"] == "Success") && ($downloadReturn["ErrorFile"] != null)){
                        if(($found = array_search($downloadReturn["ErrorFile"], $jsonArray["MatchIDs"])) !== false){
                            unset($jsonArray["MatchIDs"][$found]);
                            $fp = fopen('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json', 'w');
                            fwrite($fp, json_encode($jsonArray));
                            fclose($fp);
                        }
                    }
                }
            }

            /**
             * STEP 4: Logging & Finishing up
             */
            clearstatcache(true, $logPath); // Used for proper filesize calculation at the end of line 82
            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $endofup = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: End of update for \"" . $playerName . "\" - (Final Matchcount: ".count($playerDataArray["MatchIDs"]).", Approximate Filesize: ".number_format((filesize($logPath)/1048576), 3)." MB)";
            $border = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: -------------------------------------------------------------------------------------";
            file_put_contents($logPath, $endofup.PHP_EOL , FILE_APPEND | LOCK_EX);
            file_put_contents($logPath, $border.PHP_EOL , FILE_APPEND | LOCK_EX);
            // Finally return successful updated status via javascript json format
            // echo '{"status":"updated"}';
        }
    }
}

?>