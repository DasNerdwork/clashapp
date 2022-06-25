<?php
$champname = $_POST["champname"];
$teamID = $_POST["teamid"];
$suggestBanArray = array();
if(file_exists('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json')){
    $suggestedBanFileContent = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json'), true);
    $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
    if($suggestedBanFileContent == json_encode($suggestBanArray)){
        fclose($fp);
    } else {
        $suggestBanArray["SuggestedBans"] = $champname;
        $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
        fwrite($fp, json_encode($suggestBanArray));
        fclose($fp);
    }
} else {
    $suggestBanArray["SuggestedBans"] = $champname;
    $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
    fwrite($fp, json_encode($suggestBanArray));
    fclose($fp);
}

?>
