<?php
include_once('/hdd1/clashapp/src/functions.php');
require_once '/hdd1/clashapp/db/mongo-db.php';

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
if(isset($_POST['puuid'])){
    if(!isValidID($_POST['puuid'])){
        die("Invalid puuid: " . $_POST['puuid']);
    }
}

if(isset($_POST['mode'])){
    $mdb = new MongoDBHelper();
    $response = array('puuid' => $_POST['puuid']);
    $recalculatedMatchIDsArray = array();
    $matchData = getMatchData($matchids);
    $playerDataJSON = objectToArray($mdb->findDocumentByField('players', 'PlayerData.PUUID', $_POST['puuid'])["document"]);

    if($_POST['mode'] == "both" || $_POST['mode'] == "lanes"){
        $puuid = $_POST['puuid'];
        $playerLanes = getLanePercentages($matchData, $puuid);
        $playerDataJSON["LanePercentages"] = $playerLanes;
        $response['playerLanes'] = $playerDataJSON["LanePercentages"];
        $playerDataJSON["Tags"] = getPlayerTags($matchData, $puuid);
        $tagContainerContent = tagSelector($playerDataJSON["Tags"][$playerDataJSON["LanePercentages"][0]]);
        $response['playerTags'] = $tagContainerContent;
    } 
    
    if($_POST['mode'] == "both" || $_POST['mode'] == "scores"){
        $puuid = $_POST['puuid'];
        $matchRankingArray = getMatchRanking($matchids, $matchData, $puuid);

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

    $mdb->addElementToDocument('players', 'PlayerData.PUUID', $_POST['puuid'], 'LanePercentages', $playerDataJSON["LanePercentages"]);
    $mdb->addElementToDocument('players', 'PlayerData.PUUID', $_POST['puuid'], 'Tags', $playerDataJSON["Tags"]);
    $mdb->addElementToDocument('players', 'PlayerData.PUUID', $_POST['puuid'], 'MatchIDs', $playerDataJSON["MatchIDs"]);

    if($_POST['mode'] == "both") {
        $matchHistoryAsHTML = printTeamMatchDetailsByPUUID($matchids, $puuid, $matchRankingArray);
        $response['matchHistory'] = $matchHistoryAsHTML;
    }

    unset($recalculatedMatchIDsArray);
    unset($matchData);
    
    echo json_encode($response);
}
?>