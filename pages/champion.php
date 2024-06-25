<?php
/**
 *
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 *
 */

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include_once('/hdd1/clashapp/src/functions.php');
require_once '/hdd1/clashapp/db/mongo-db.php';
if (isset($_GET["name"])){
    isset($_COOKIE["lang"]) ? $lang = $_COOKIE["lang"] : $lang = null;
    $champData = doesChampionExist($_GET["name"], $lang);
    if(!$champData["success"]) header('Location: /404');
}
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Profile', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php'); 

?> <body class="bg-darker"> <?php

$autosuggestRequest = $mdb->getAutosuggestAggregate();
$championDataArray = json_decode(file_get_contents("/hdd1/clashapp/data/patch/".$currentPatch."/data/en_US/champion.json"), true);
$championArray = array();
foreach ($championDataArray['data'] as $championKey => $championInfo) {
    $championArray["{$championInfo['name']}"] = "{$championInfo['image']['full']}";
}
if($autosuggestRequest["success"]){
    $autosuggestData = $autosuggestRequest["data"];
    echo "<script>const autosuggestData = " . json_encode(array_map('trim', $autosuggestData)) . ";</script>";
} else {
    echo "<script>const autosuggestData = '';</script>";
}
echo "
<script>
var cached = 0;
const currentPatch = " . json_encode($currentPatch) . ";
const championData = " . json_encode($championArray) . ";
const containerTitle = '" . __("Summoner") . "';
const searchHistoryTitle = '" . __("Recently Searched") . "';
</script>";

$mdb = new MongoDBHelper();
$mainChampImgPath = str_replace(".png", ".avif", $champData["data"]["image"]["full"]);
$currentPatchShort = substr($currentPatch, 0, strrpos($currentPatch, '.')); // E.g. 14.12
$allMatches = $mdb->countDocuments('matches', ["info.gameVersion" => ['$regex' => "^$currentPatchShort"]]);
$allCurrentMatches = $mdb->countDocuments('matches', ["info.gameVersion" => ['$regex' => "^$currentPatchShort"]]);
$winCount = $mdb->countDocuments('matches', ["info.participants" => ['$elemMatch' => ["championName" => $champData["data"]["name"], "win" => true]], "info.gameVersion" => ['$regex' => "^$currentPatchShort"]]);
$loseCount = $mdb->countDocuments('matches', ["info.participants" => ['$elemMatch' => ["championName" => $champData["data"]["name"], "win" => false]], "info.gameVersion" => ['$regex' => "^$currentPatchShort"]]);
$banCount = $mdb->countDocuments('matches', ["info.teams" => ['$elemMatch' => ["bans" => ['$elemMatch' => ["championId" => (int)$champData["data"]["key"]]]]], "info.gameVersion" => ['$regex' => "^$currentPatchShort"]]);

echo "<img src='/clashapp/data/patch/".$currentPatch."/img/champion/".$mainChampImgPath."?version=".md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$mainChampImgPath)."' alt='A league of legends champion icon of ".$champData["data"]["name"]."'>";

if($allMatches["success"]){
    echo "All Matches: ".$allMatches["count"]."<br>";
} else {
    echo "All Matches: N/A<br>";
}
if($winCount["success"] && $loseCount["success"]){
    $matchCount = $winCount["count"]+$loseCount["count"];
    echo "Matchcount: ".$matchCount."<br>";
} else {
    echo "Matchcount: N/A<br>";
}
if($winCount["success"]){
    echo "Wins: ".$winCount["count"]."<br>";
} else {
    echo "Wins: N/A<br>";
}
if($loseCount["success"]){
    echo "Loses: ".$loseCount["count"]."<br>";
} else {
    echo "Loses: N/A<br>";
}

if(isset($matchCount) && $matchCount != 0 && $allMatches["count"] != 0){
    echo "Winrate: ".round((($winCount["count"]/($matchCount))*100),2)."%<br>";
    echo "Pickrate: ".round(((($matchCount/$allMatches["count"])*100)),2)."%<br>";
    echo "Banrate: ".round(((($banCount["count"]/$allMatches["count"])*100)),2)."%<br>";
} else {
    echo "Winrate: N/A<br>";
    echo "Pickrate: N/A<br>";
    echo "Banrate: N/A<br>";
}



echo "<pre>";
print_r($champData["data"]);
echo "</pre>";

include('/hdd1/clashapp/templates/footer.php');
?>