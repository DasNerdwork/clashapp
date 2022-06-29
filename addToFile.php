<?php
$champid = $_POST["champid"];
$champname = $_POST["champname"];
$teamID = $_POST["teamid"];

// $champid = "Aatrox";
// $champname = "Aatrox";
// $teamID = "dasnerdwork";

$suggestBanArray = array();

if(file_exists('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json')){
    $suggestedBanFileContent = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json'), true);
    
    if(array_search($champid, array_column($suggestedBanFileContent["SuggestedBans"], 'id')) !== false){
        echo '{"status":"ElementAlreadyInArray"}';die();
    } else if (count($suggestedBanFileContent["SuggestedBans"]) >= 10) {
        echo '{"status":"MaximumElementsExceeded"}';die();
    } else {
        echo '{"status":"Success"}';
        $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
        if($suggestedBanFileContent["SuggestedBans"] == $champid){
            fclose($fp);
        } else {
            $suggestedBanFileContent["SuggestedBans"][] = array("id"=>$champid,"name"=>$champname);
            $suggestedBanFileContent["Status"]++;
            fwrite($fp, json_encode($suggestedBanFileContent));
            fclose($fp);
        }
    }
} else {
    echo '{"status":"FileDoesNotExist"}';
    $suggestBanArray["SuggestedBans"][] = array("id"=>$champid,"name"=>$champname);
    $suggestBanArray["Status"] = 1;

    $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
    fwrite($fp, json_encode($suggestBanArray));
    fclose($fp);
}
?>
