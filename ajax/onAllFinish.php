<?php
include_once('/hdd1/clashapp/functions.php');

if(isset($_POST['allFinish'])){
    $suggestedBanMatchData = getMatchData($matchIDTeamArray);
    $suggestedBanArray = getSuggestedBans(array_keys($playerSumidTeamArray), $masteryDataTeamArray, $playerLanesTeamArray, $matchIDTeamArray, $suggestedBanMatchData);
    $currentTeamJSON["SuggestedBanData"] = $suggestedBanArray;
    $fp = fopen('/hdd1/clashapp/data/teams/'.$teamID.'.json', 'w+');
    fwrite($fp, json_encode($currentTeamJSON));
    fclose($fp);
}
?>