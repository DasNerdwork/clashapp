<?php session_start(); 
include('/hdd2/clashapp/templates/head.php');
setCodeHeader('Clash', true, true);
include('/hdd2/clashapp/templates/header.php');
if (!isset($_SESSION)) session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// $currentPatch = file_get_contents("/hdd2/clashapp/data/patch/version.txt");
// $championJSON = json_decode(file_get_contents("/hdd2/clashapp/data/patch/".$currentPatch."/data/de_DE/champion.json"), true);


// $main = json_decode(file_get_contents("/var/www/html/clash/yoerdle/main.json"), true);
// foreach($main as $championName => $champData) {
//     foreach(array_column($championJSON["data"], 'name') as $champDataName) {
//         if($championName == $champDataName){
//             echo "<pre>";
//             print_r($championJSON["data"][$champDataName]["id"]);
//             echo "</pre>";
//         }
//     }
// }


include('/hdd2/clashapp/templates/footer.php');
?>