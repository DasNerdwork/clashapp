<?php
include_once('functions.php');
require_once '/hdd1/clashapp/mongo-db.php';
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

function updateProfile($id, $teamID, $type="riot-id", $tempMatchIDs=null){
    if($id != ""){
        $returnScriptContent = "";
        $mdb = new MongoDBHelper();
        $playerData = getPlayerData($type,$id);
        // addToQueue('api_queue', 'playerData', ['type' => $type, 'id' => $id]); // DEPRECATED
        $playerName = $playerData["GameName"];
        $sumid = $playerData["SumID"];
        $puuid = $playerData["PUUID"];
        $masteryData = getMasteryScores($puuid);
        // addToQueue('api_queue', 'masteryScores', ['puuid' => $puuid]);// DEPRECATED
        $rankData = getCurrentRank($sumid);
        // addToQueue('api_queue', 'currentRank', ['sumid' => $sumid]);// DEPRECATED
        if($tempMatchIDs == null){
            $matchIDs = getMatchIDs($puuid, 15);
            // addToQueue('api_queue', 'matchIds', ['puuid' => $puuid, 'maxMatchIDs' => 15]);// DEPRECATED
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

        /**
         * STEP 1: Check if up-to-date
         */
        if($sumid != "" || $sumid != "/"){ /** @todo additional sanitizing regex check for valid $sumid variants */
            $playerDataRequest = $mdb->getPlayerBySummonerId($sumid);
            if($playerDataRequest["success"]){
                $playerDataJSONString = json_encode($playerDataRequest["data"]);
                $existingJson = json_decode($playerDataJSONString, true);
                // If the newest local matchID equals the newest API requested matchID, ergo if there is nothing to update
                // and if we have the same amount or more matchIDs stored locally (no better data to grab) 
                $return = true;
                foreach($matchIDs as $checkSingleMatch){
                    if(!in_array($checkSingleMatch, array_keys($existingJson["MatchIDs"])) || !$mdb->findDocumentByField("matches", 'metadata.matchId', $checkSingleMatch)["success"]){
                        $tempAjaxMatchIDArray[] = $checkSingleMatch;
                    }
                }
                if(empty($tempAjaxMatchIDArray)){
                    $returnScriptContent .= "console.log('All matches of ".$playerName." already local.');
                    requests['".$sumid."'] = 'Done';
                    xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&puuid=".$puuid."&sumid=".$sumid."';".
                    processResponseData($ajaxUniquifier)
                    ."
                    xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                    if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {".
                        callAllFinish($requestIterator, $teamID)
                        ."
                    }</>";
                } else {
                // THIS REQUEST IS SENT IF A PLAYER IS MISSING SOME MATCH IDS IN THEIR PLAYERFILE
                $returnScriptContent .= "
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
                        xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&puuid=".$puuid."&sumid=".$sumid."';".
                        processResponseData($ajaxUniquifier)
                        ."
                        xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                        if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {".
                            callAllFinish($requestIterator, $teamID)
                            ."
                        }
                    }
                };

                var data = 'matches=".json_encode($tempAjaxMatchIDArray)."&playerName=".$playerName."';

                xhr".$requestIterator.".send(data);
                
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
            $mdb->insertDocument('players', $jsonArray);

            /**
             * STEP 3: Fetch all given matchIDs and download each match via downloadMatchByID
             */
            $playerDataArrayRequest = $mdb->getPlayerBySummonerId($sumid);
            if($playerDataArrayRequest["success"]){
                $playerDataJSONString = json_encode($playerDataArrayRequest["data"]);
                $playerDataArray = json_decode($playerDataJSONString, true);
            }
            foreach(array_keys($playerDataArray["MatchIDs"]) as $match){
                if(!$mdb->findDocumentByField("matches", 'metadata.matchId', $match)["success"]){
                    $tempAjaxMatchIDArray[] = $match;
                }
            }
            
            if($existingJson == ""){
                if(empty($tempAjaxMatchIDArray)){
                    $returnScriptContent .= "console.log('All matches of ".$playerName." already local.');
                    requests['".$sumid."'] = 'Done';
                    xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&puuid=".$puuid."&sumid=".$sumid."';".
                    processResponseData($ajaxUniquifier)
                    ."
                    xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                    if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {".
                        callAllFinish($requestIterator, $teamID)
                        ."
                    }";
                } else {
                // THIS REQUEST IS SENT IF NO PLAYER FILE EXISTS / LINE 69 IS SKIPPED
                $returnScriptContent .= "
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
                        xhrMessage = 'mode=both&matchids=".json_encode($ajaxArray)."&puuid=".$puuid."&sumid=".$sumid."';".
                        processResponseData($ajaxUniquifier)
                        ."
                        xhrAfter".$ajaxUniquifier.".send(xhrMessage);
                        if (Object.values(requests).every(value => value === 'Done') && Object.keys(requests).length === playerCount) {".
                            callAllFinish($requestIterator, $teamID)
                            ."
                        }
                    }
                };

                var data = 'matches=".json_encode($tempAjaxMatchIDArray)."&playerName=".$playerName."';

                xhr".$requestIterator.".send(data);
                
                ";
                }
            }
        }
    }
    $requestIterator++;
    return $returnScriptContent;
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
                                newImage.src = '/clashapp/data/misc/lanes/' + singleLane + '.avif';
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
                        var scoresArray = Object.values(response.matchScores).filter(score => !isNaN(parseFloat(score)));
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
                    if(response.playerTags) {
                        let tagContainer = item.parentElement.parentElement.nextElementSibling.nextElementSibling.nextElementSibling.children[0].children[0];
                        tagContainer.classList.add('opacity-100');
                        tagContainer.innerHTML = response.playerTags;
                        setTimeout(function() {
                            tagContainer.classList.remove('opacity-0');
                        }, 100);
                    }
                }
            }
            for (let historyColumn of matchHistories) {
                if(response.sumid === historyColumn.dataset.sumid) {
                    if(response.matchHistory) {
                        historyColumn.innerHTML = response.matchHistory;
                    }
                }
            }
        }
    };";
}

function callAllFinish($requestIterator, $teamID) {
    return "
    console.log('ALL PLAYERS FINISHED');

    var xhrFinal".$requestIterator." = new XMLHttpRequest();
    xhrFinal".$requestIterator.".open('POST', '/ajax/onAllFinish.php', true);
    xhrFinal".$requestIterator.".setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    xhrFinal".$requestIterator.".onreadystatechange = function() {
        if (xhrFinal".$requestIterator.".readyState === 4 && xhrFinal".$requestIterator.".status === 200) {
            var finalResponse = xhrFinal".$requestIterator.".responseText;
            var suggestedBanContainer = document.getElementById('suggestedBans');
            suggestedBanContainer.innerHTML = finalResponse;
            console.log('Suggested Bans generated dynamically');
        }
    };
    sumids = Object.keys(requests).join(',');
    teamID = '".$teamID."';
    var data = 'sumids=' + sumids + '&teamid=' + teamID;
    setTimeout(function() {
        xhrFinal".$requestIterator.".send(data);
    }, 100);";
}

?>