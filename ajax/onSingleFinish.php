<?php

// IF THIS COMES FROM UPDATE PHP ALSO HAVE TO JS ADD LANES

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once('/hdd1/clashapp/functions.php');

if(isset($_POST['mode'])){
    $matchids = array_keys(json_decode($_POST['matchids'], true));
    $path = $_POST['path']; // grab path from ajax request
    $recalculatedMatchIDsArray = array();
    // load players full match data into RAM array for fast access after page load
    $matchData = getMatchData($matchids); 
    $playerDataJSON = json_decode(file_get_contents('/hdd1/clashapp/data/player/'.$path), true); // get CURRENT filecontent
    $fp = fopen('/hdd1/clashapp/data/player/'.$path, 'r+');

    if($_POST['mode'] == "both" || $_POST['mode'] == "lanes"){
        $puuid = $_POST['puuid']; // grab puuid from ajax request
        $playerLanes = getLanePercentages($matchData, $puuid); // Retrieves the two most played lanes of the give puuid
        $playerDataJSON["LanePercentages"] = $playerLanes;
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
    }
    
    fwrite($fp, json_encode($playerDataJSON));
    fclose($fp);
    
    unset($recalculatedMatchIDsArray); // Necessary to reset the array for the next player iteration, otherwise everyone has the same matchids stored
    unset($matchData); // Empty RAM
}
?>