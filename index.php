<?php session_start(); 
include_once('/hdd1/clashapp/functions.php');
require_once '/hdd1/clashapp/mongo-db.php';
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

// print_r($_SESSION);
echo '
<script>
document.body.style.backgroundImage = "url(/clashapp/data/misc/webp/background.webp)";
document.body.style.backgroundRepeat = "no-repeat";
document.body.style.backgroundPosition = "50% 20%";
document.body.style.backgroundSize = "40%";
</script>
';
// print_r(getPlayerData("name", "Flokrastinator"));
// $envVariables = getenv();

// foreach ($envVariables as $key => $value) {
//     echo $key . ' = ' . $value . "\n";
// }
$mdb = new MongoDBHelper();

$testmatch = $mdb->findDocumentByField("matches", 'metadata.matchId', "EUW1_6270020637");
// echo "<pre>";
// print_r($testmatch);
// echo "</pre>";


// print_r($mdb->addElementToDocument('players', 'PlayerData.PUUID', 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA', 'RankData', [
//     [
//         "Queue" => "RANKED_SOLO_5x5",
//         "Tier" => "PLATINUM",
//         "Rank" => "IV",
//         "LP" => 0,
//         "Wins" => 18,
//         "Losses" => 26
//     ],
//     [
//         "Queue" => "RANKED_FLEX_SR",
//         "Tier" => "EMERALD",
//         "Rank" => "IV",
//         "LP" => 0,
//         "Wins" => 52,
//         "Losses" => 55
//     ]
// ]));

?>



<?php
include('/hdd1/clashapp/templates/footer.php');
?>