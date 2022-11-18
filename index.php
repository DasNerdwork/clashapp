<?php session_start(); 
include('head.php');
setCodeHeader('Clash', true, true);
include('header.php');
include_once 'qr-codes.php';

// verifyEntered2FA("dasnerdwork@gmail.com", "756836");


// $cleanAttributeArray = array("kills", "deaths", "assists", "kda", "killParticipation", "totalMinionsKilled", "goldEarned", "visionScore", "wardTakedowns", "wardsPlaced", "wardsGuarded", "detectorWardsPlaced", "consumablesPurchased", "turretPlatesTaken",
// "takedowns", "turretTakedowns", "inhibitorTakedowns", "dragonTakedowns", "riftHeraldTakedowns", "damageDealtToBuildings", "damageDealtToObjectives", "damageSelfMitigated", "totalDamageDealtToChampions", "totalDamageTaken", "totalDamageShieldedOnTeammates",
// "totalHealsOnTeammates", "totalTimeCCDealt", "totalTimeSpentDead", "skillshotsDodged", "skillshotsHit", "championName", "championTransform", "individualPosition", "teamPosition", "lane", "puuid", "summonerId","summonerName", "win");

// $startGetData = microtime(true);
// $memGetData = memory_get_usage();
// if(file_exists('/var/www/html/clash/clashapp/data/matches/EUW1_6147669201.json')){
//     $matchData["EUW1_6147669201"] = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/matches/EUW1_6147669201.json'));
//     $matchData["EUW1_6147691131"] = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/matches/EUW1_6147691131.json')); 
// }
// foreach($matchData as $key => $data){
//     unset($matchData[$key]->metadata);
//     unset($matchData[$key]->info->gameId);
//     unset($matchData[$key]->info->gameMode);
//     unset($matchData[$key]->info->gameName);
//     unset($matchData[$key]->info->gameType);
//     unset($matchData[$key]->info->mapId);
//     $matchData[$key]->info->gameVersion = explode(".",$matchData[$key]->info->gameVersion)[0].".".explode(".",$matchData[$key]->info->gameVersion)[1];
//     foreach($matchData[$key]->info->participants as $player){
//          unset($player->allInPings);
//          unset($player->assistMePings);
//          unset($player->baitPings);
//          unset($player->baronKills);
//          unset($player->basicPings);
//          unset($player->bountyLevel);
//          foreach($player->challenges as $challengeName => $challValue){
//              if(!in_array($challengeName, $cleanAttributeArray)){
//                  unset($player->challenges->$challengeName);
//              }
//          }
//          foreach($player as $statName => $statValue){
//              if(!in_array($statName, $cleanAttributeArray) && $statName != "challenges"){
//                  unset($player->$statName);
//              }
//          }
//      }
//      unset($matchData[$key]->info->platformId); // e.g. EUW
//      unset($matchData[$key]->info->queueId); // E.g. 440 / Solo_Duo_Queue
//      unset($matchData[$key]->info->teams);
//      unset($matchData[$key]->info->tournamentCode);
// }

// echo "<pre>";
// print_r($matchData);
// echo "<pre>";

// $timeAndMemoryArray["Time"] = number_format((microtime(true) - $startGetData), 2, ',', '.')." s";
// $timeAndMemoryArray["Memory"] = number_format((memory_get_usage() - $memGetData)/1024, 2, ',', '.')." kB";

// echo "<pre>";
// print_r($timeAndMemoryArray);
// echo "<pre>";

include('footer.php');
?>