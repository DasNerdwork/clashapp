<?php
$champid = $_POST["champid"];
$teamID = $_POST["teamid"];

// $champid = "Aatrox";
// // $champname = "Aatrox";
// $teamID = "dasnerdwork";

$suggestBanArray = array();

if(file_exists('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json')){
    $suggestedBanFileContent = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json'), true);
    
    unset($suggestedBanFileContent["SuggestedBans"][array_search($champid, array_column($suggestedBanFileContent["SuggestedBans"], 'id'))]);
    $suggestedBanFileContent["Status"]++;
    $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'w');
    $suggestedBanFileContent["SuggestedBans"] = array_values($suggestedBanFileContent["SuggestedBans"]);
    fwrite($fp, json_encode($suggestedBanFileContent));
    fclose($fp);
}
?> 
