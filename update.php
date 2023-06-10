<?php
include_once('functions.php');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


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
$requestIterator = 0;

// if(isset($_POST["username"])){
//     // If function is explicitly called via a POST (e.g. by pressing the update button on a single users profile) start the function with a maximum Match ID fetch count of 150
//     updateProfile($_POST["username"], 150);
// }

// Fetch all the necessary data for updating or generating a single players player.json, stored in /clashapp/data/player/
function updateProfile($id, $maxMatchIds, $type="name", $tempMatchIDs=null){
    if($id != ""){
        $playerData = getPlayerData($type,$id);
        $playerName = $playerData["Name"];
        $sumid = $playerData["SumID"];
        $puuid = $playerData["PUUID"];
        $masteryData = getMasteryScores($sumid);
        $rankData = getCurrentRank($sumid);
        if($tempMatchIDs == null){
            $matchIDs = getMatchIDs($puuid, 15);
        } else {
            $matchIDs = $tempMatchIDs;
        }
        $ajaxArray = [];
        foreach ($matchIDs as $matchID) {
            $ajaxArray[$matchID] = "";
        }
        $ajaxUniquifier = preg_replace("/[^a-zA-Z0-9]/", "", $sumid);
        $jsonArray = array();
        $jsonArray["PlayerData"] = $playerData;
        $jsonArray["RankData"] = $rankData;
        $jsonArray["MasteryData"] = $masteryData;
        for ($i=0; $i < count($matchIDs); $i++) { 
            if(!isset($jsonArray["MatchIDs"][$matchIDs[$i]])){
                $jsonArray["MatchIDs"][$matchIDs[$i]] = "";
            }
        }
        $tempAjaxMatchIDArray = array();
        global $requestIterator;

        $logPath = '/var/www/html/clash/clashapp/data/logs/matchDownloader.log'; // The log patch where any additional info about this process can be found

        /**
         * STEP 1: Check if up-to-date
         */
        if($sumid != "" || $sumid != "/"){ /** @todo additional sanitizing regex check for valid $sumid variants */
            if(file_exists('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json')){
                $existingJson = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$sumid.'.json'), true);
                // If the newest local matchID equals the newest API requested matchID, ergo if there is nothing to update
                // and if we have the same amount or more matchIDs stored locally (no better data to grab) 
                $return = true;
                foreach($matchIDs as $checkSingleMatch){
                    if(!in_array($checkSingleMatch, array_keys($existingJson["MatchIDs"]))){
                        $tempAjaxMatchIDArray[] = $checkSingleMatch;
                    }
                }
                if(empty($tempAjaxMatchIDArray)){
                    echo "<script>console.log('All matches of ".$playerName." already local.');
                    requests['".$sumid."'] = 'Done';
                    xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&path=".$sumid.".json&puuid=".$puuid."&sumid=".$sumid."';".
                    processResponseData($ajaxUniquifier)
                    ."
                    xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                    if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {
                        console.log('ALL PLAYERS FINISHED');
                    }</script>";
                } else {
                // THIS REQUEST IS SENT IF A PLAYER IS MISSING SOME MATCH IDS IN THEIR PLAYERFILE
                echo "<script>
                var startTime".$requestIterator." = new Date().getTime();
                var xhr".$requestIterator." = new XMLHttpRequest();

                xhr".$requestIterator.".open('POST', '/ajax/downloadMatch.php', true);

                xhr".$requestIterator.".setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                xhr".$requestIterator.".onreadystatechange = function() {
                    if (xhr".$requestIterator.".readyState === 4 && xhr".$requestIterator.".status === 200) {
                        var endTime".$requestIterator." = new Date().getTime();
                        var elapsedTime".$requestIterator." = (endTime".$requestIterator." - startTime".$requestIterator.") / 1000;
                        var playerName = xhr".$requestIterator.".responseText;
                        console.log('Match Downloads for ' + playerName + ' completed after ' + elapsedTime".$requestIterator.".toFixed(2) + ' seconds');
                        requests['".$sumid."'] = 'Done';
                        xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&path=".$sumid.".json&puuid=".$puuid."&sumid=".$sumid."';".
                        processResponseData($ajaxUniquifier)
                        ."
                        xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                        if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {
                            console.log('ALL PLAYERS FINISHED');
                        }
                    }
                };

                var data = 'matches=".json_encode($tempAjaxMatchIDArray)."&playerName=".$playerName."';

                xhr".$requestIterator.".send(data);
                </script>
                ";
                $return = false;
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
            foreach(array_keys($playerDataArray["MatchIDs"]) as $match){
                if(!file_exists('/var/www/html/clash/clashapp/data/matches/'.$match.'.json')){
                    $tempAjaxMatchIDArray[] = $match;
                }
            }
            
            if($existingJson == ""){
                if(empty($tempAjaxMatchIDArray)){
                    echo "<script>console.log('All matches of ".$playerName." already local.');
                    requests['".$sumid."'] = 'Done';
                    xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&path=".$sumid.".json&puuid=".$puuid."&sumid=".$sumid."';".
                    processResponseData($ajaxUniquifier)
                    ."
                    xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                    if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {
                        console.log('ALL PLAYERS FINISHED');
                    }</script>";
                } else {
                // THIS REQUEST IS SENT IF NO PLAYER FILE EXISTS / LINE 69 IS SKIPPED
                echo "<script>
                var startTime".$requestIterator." = new Date().getTime();
                var xhr".$requestIterator." = new XMLHttpRequest();

                xhr".$requestIterator.".open('POST', '/ajax/downloadMatch.php', true);

                xhr".$requestIterator.".setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

                xhr".$requestIterator.".onreadystatechange = function() {
                    if (xhr".$requestIterator.".readyState === 4 && xhr".$requestIterator.".status === 200) {
                        var endTime".$requestIterator." = new Date().getTime();
                        var elapsedTime".$requestIterator." = (endTime".$requestIterator." - startTime".$requestIterator.") / 1000;
                        var playerName = xhr".$requestIterator.".responseText;
                        console.log('Match Downloads for ' + playerName + ' completed after ' + elapsedTime".$requestIterator.".toFixed(2) + ' seconds');
                        requests['".$sumid."'] = 'Done';
                        xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&path=".$sumid.".json&puuid=".$puuid."&sumid=".$sumid."';".
                        processResponseData($ajaxUniquifier)
                        ."
                        xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                        if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {
                            console.log('ALL PLAYERS FINISHED');
                        }
                    }
                };

                var data = 'matches=".json_encode($tempAjaxMatchIDArray)."&playerName=".$playerName."';

                xhr".$requestIterator.".send(data);
                </script>
                ";
                }
            }
        }
    }
    $requestIterator++;
}

function processResponseData($ajaxUniquifier){
    return "
    var xhrAfter".$ajaxUniquifier." = new XMLHttpRequest();
    xhrAfter".$ajaxUniquifier.".open('POST', '/ajax/onSingleFinish.php', true);
    xhrAfter".$ajaxUniquifier.".setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhrAfter".$ajaxUniquifier.".onreadystatechange = function() {
        if (xhrAfter".$ajaxUniquifier.".readyState === 4 && xhrAfter".$ajaxUniquifier.".status === 200) {
            xhrAfter".$ajaxUniquifier.".responseText;
            var playerColumns = document.getElementsByClassName('single-player-column');
            var matchHistories = document.getElementsByClassName('single-player-match-history');
            var response = JSON.parse(xhrAfter".$ajaxUniquifier.".responseText);
            for (let item of playerColumns) {
                if(response.sumid === item.dataset.sumid) {
                    if(response.playerLanes) {
                        response.playerLanes.forEach(function(singleLane) {
                            if(singleLane != ''){
                                let laneContainerPath = item.children[1].children[0].children[1].children[1];
                                let newImage = document.createElement('img');
                                newImage.className = 'saturate-0 brightness-150 transition-opacity duration-500 easy-in-out opacity-0';
                                newImage.src = '/clashapp/data/misc/lanes/' + singleLane + '.webp';
                                newImage.width = 32;
                                newImage.height = 32;
                                newImage.alt = 'A league of legends lane icon corresponding to a players main position';
                      
                                laneContainerPath.appendChild(newImage);
                                setTimeout(function() {
                                    for (let child of laneContainerPath.children) {
                                        child.classList.remove('opacity-0');
                                    }
                                }, 100);
                            }
                        });
                    }
                    if(response.matchScores) {
                        let avgScorePath = item.children[1].children[0].children[2].children[1].children[0];
                        var scoresArray = Object.values(response.matchScores);
                        var sum = 0;
                        for (var i = 0; i < scoresArray.length; i++) {
                            sum += parseFloat(scoresArray[i]);
                        }
                        var averageScore = (sum / scoresArray.length).toFixed(2);
                        avgScorePath.innerText = averageScore;
                        setTimeout(function() {
                            avgScorePath.classList.remove('opacity-0');
                        }, 100);
                    }
                }
            }
            for (let historyColumn of matchHistories) {
                if(response.sumid === historyColumn.dataset.sumid) {
                    if(response.matchHistory) {
                        historyColumn.insertAdjacentHTML('beforeend', response.matchHistory);
                    }
                }
            }
        }
    };";
}

?>