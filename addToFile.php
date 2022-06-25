<?php
$champname = $_POST["champname"];
$teamID = $_POST["teamid"];
$suggestBanArray = array();
if(file_exists('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json')){
    $suggestedBanFileContent = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json'), true);
    // if(in_array($suggestedBanFileContent["SuggestedBans"], $championname)){ // not working, no idea why

    // } else {
        $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
        if($suggestedBanFileContent["SuggestedBans"] == $champname){
            fclose($fp);
        } else {
            $suggestedBanFileContent["SuggestedBans"][] = $champname;
            // $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
            fwrite($fp, json_encode($suggestedBanFileContent));
            fclose($fp);
        }
    // }
} else {
    $suggestBanArray["SuggestedBans"][0] = $champname;
    $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
    fwrite($fp, json_encode($suggestBanArray));
    fclose($fp);
}

?>
