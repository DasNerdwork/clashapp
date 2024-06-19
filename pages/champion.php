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
include_once('/hdd1/clashapp/src/update.php');
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

echo "<pre>";
print_r($champData["data"]);
echo "</pre>";








include('/hdd1/clashapp/templates/footer.php');
?>