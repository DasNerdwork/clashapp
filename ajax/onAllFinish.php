<?php
include_once('/hdd1/clashapp/functions.php');
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if(isset($_POST['sumids'])){
    $matchIDTeamArray = array();
    $masteryDataTeamArray = array();
    $playerLanesTeamArray = array();
    $playerSumidTeamArray = explode(',', $_POST['sumids']);
    $teamID = $_POST['teamid'];
    foreach($playerSumidTeamArray as $playerSumid){
        if(!file_exists('/hdd1/clashapp/data/player/'.$playerSumid.'.json')){
            echo "Could not find playerfile for ".$playerSumid;
            return;
        } else {
            $playerDataJSON = json_decode(file_get_contents('/hdd1/clashapp/data/player/'.$playerSumid.'.json'), true);
            foreach(array_keys($playerDataJSON["MatchIDs"]) as $singleMatchID){
                if(!in_array($singleMatchID, $matchIDTeamArray)){
                    $matchIDTeamArray[] = $singleMatchID;
                }
            }
            $masteryDataTeamArray[$playerSumid] = $playerDataJSON["MasteryData"];
            $playerLanesTeamArray[$playerSumid]["Mainrole"] = $playerDataJSON["LanePercentages"][0];
            if(isset($playerDataJSON["LanePercentages"][1])){
                $playerLanesTeamArray[$playerSumid]["Secrole"] = $playerDataJSON["LanePercentages"][1];
            } else {
                $playerLanesTeamArray[$playerSumid]["Secrole"] = "";
            }
        }
    }
    // --------------- DEBUGGING ----------------------
    $returnArray = array();
    // $returnArray["MatchIDs"] = $matchIDTeamArray;
    // $returnArray["PlayerLanes"] = $playerLanesTeamArray;
    // $returnArray["PlayerSumids"] = $playerSumidTeamArray;
    // $returnArray["MasteryData"] = $masteryDataTeamArray;
    // echo json_encode($returnArray);

    $suggestedBanMatchData = getMatchData($matchIDTeamArray);
    $suggestedBanArray = getSuggestedBans($playerSumidTeamArray, $masteryDataTeamArray, $playerLanesTeamArray, $matchIDTeamArray, $suggestedBanMatchData);
    $currentTeamJSON = json_decode(file_get_contents('/hdd1/clashapp/data/teams/'.$teamID.'.json'), true);
    $currentTeamJSON["SuggestedBanData"] = $suggestedBanArray;
    $fp = fopen('/hdd1/clashapp/data/teams/'.$teamID.'.json', 'w+');
    fwrite($fp, json_encode($currentTeamJSON));
    fclose($fp);
    echo json_encode($suggestedBanArray);
}
?>