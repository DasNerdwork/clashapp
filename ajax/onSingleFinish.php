<?php
include_once('/hdd1/clashapp/functions.php');
require_once '/hdd1/clashapp/mongo-db.php';

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Data Validation checks
if(isset($_POST['mode'])){
    if(!in_array($_POST['mode'], ["both", "lanes", "scores"])){
        die("Invalid mode: " . $_POST['mode']);
    }
}
if(isset($_POST['matchids'])){
    try {
        $decodedMatchIds = json_decode($_POST['matchids'], true, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        die("Failed to decode matchids: " . $e->getMessage());
    }
    if ($decodedMatchIds !== null && is_array($decodedMatchIds)) {
        $matchids = array_keys($decodedMatchIds);
    } else {
        die("Invalid matchids format");
    }
    foreach($matchids as $singleMatchID){
        if (!isValidMatchID($singleMatchID)) {
            die("Invalid matchId: " . $singleMatchID);
        }
    }
}
if(isset($_POST['sumid'])){
    if(!isValidID($_POST['sumid'])){
        die("Invalid sumid: " . $_POST['sumid']);
    }
}
if(isset($_POST['puuid'])){
    if(!isValidID($_POST['puuid'])){
        die("Invalid puuid: " . $_POST['puuid']);
    }
}
// End of Data Validation checks

if(isset($_POST['mode'])){
    $mdb = new MongoDBHelper();
    $response = array('sumid' => $_POST['sumid']);
    $recalculatedMatchIDsArray = array();
    // load players full match data into RAM array for fast access after page load
    $matchData = getMatchData($matchids); 
    $playerDataJSON = objectToArray($mdb->findDocumentByField('players', 'PlayerData.SumID', $response["sumid"])["document"]);

    if($_POST['mode'] == "both" || $_POST['mode'] == "lanes"){
        $puuid = $_POST['puuid']; // grab puuid from ajax request
        $playerLanes = getLanePercentages($matchData, $puuid); // Retrieves the two most played lanes of the give puuid
        $playerDataJSON["LanePercentages"] = $playerLanes;
        $response['playerLanes'] = $playerDataJSON["LanePercentages"];
        $playerDataJSON["Tags"] = getPlayerTags($matchData, $puuid);
        $tagContainerContent = tagSelector($playerDataJSON["Tags"][$playerDataJSON["LanePercentages"][0]]);
        $response['playerTags'] = $tagContainerContent;
    } 
    
    if($_POST['mode'] == "both" || $_POST['mode'] == "scores"){
        $sumid = $_POST['sumid']; // grab sumid from ajax request
        $matchRankingArray = getMatchRanking($matchids, $matchData, $sumid); // Fetches ALL match scores to use in section "PRINT AVERAGE MATCHSCORE"

        foreach($matchids as $index => $singleMatchID){
            foreach($matchRankingArray as $matchRankingID => $matchRankingScore){
                if($matchRankingID == $singleMatchID){
                    $recalculatedMatchIDsArray[$singleMatchID] = $matchRankingScore;
                }
            }
        }
        $playerDataJSON["MatchIDs"] = array_slice($recalculatedMatchIDsArray, 0, 15);
        $response['matchScores'] = $playerDataJSON["MatchIDs"];
    }
    // $mdb->insertDocument('players', $playerDataJSON);
    $mdb->addElementToDocument('players', 'PlayerData.SumID', $response["sumid"], 'LanePercentages', $playerDataJSON["LanePercentages"]);
    $mdb->addElementToDocument('players', 'PlayerData.SumID', $response["sumid"], 'Tags', $playerDataJSON["Tags"]);
    $mdb->addElementToDocument('players', 'PlayerData.SumID', $response["sumid"], 'MatchIDs', $playerDataJSON["MatchIDs"]);

    if($_POST['mode'] == "both") {
        $matchHistoryAsHTML = printTeamMatchDetailsByPUUID($matchids, $puuid, $matchRankingArray);
        $response['matchHistory'] = $matchHistoryAsHTML;
    }

    unset($recalculatedMatchIDsArray); // Necessary to reset the array for the next player iteration, otherwise everyone has the same matchids stored
    unset($matchData); // Empty RAM
    
    echo json_encode($response);
}
?>