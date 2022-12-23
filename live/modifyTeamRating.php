<?php

// Grabbing and referencing the posted variables + current patch as string
$userRating = $_POST["rating"];
$userID = $_POST["hash"];
$teamID = $_POST["teamid"];
// $currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");

// Only proceed if file with bans exists and grab content
if(file_exists('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json')){
    $preexistingBanFileContent = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json'), true);
    $preexistingBanFileContent["Rating"][$userID] = $userRating;

    $fp = fopen('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json', 'w'); // Clear file, add old status and updated Rating
    fwrite($fp, json_encode($preexistingBanFileContent));
    fclose($fp);
    echo '{"status":"Success"}';
} else {
    echo '{"status":"FileDoesNotExist"}';die();
}
?> 
