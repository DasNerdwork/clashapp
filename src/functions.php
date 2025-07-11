<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/hdd1/clashapp/src/apiFunctions.php';
require_once '/hdd1/clashapp/db/mongo-db.php';

/** Main functions.php containing overall used functions throughout different php files
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 *
 * Initializing of global variables used throughout all functions below
 *
 * @global string $currentPatch For example "12.4.1", gets fetched from the version.txt which itself gets daily updated by the patcher.py script
 * @global int $counter Necessary counter variable for the getMatchByID Function
 * @global int $currenttimestam The current time stamp usable as a global variable
 */

global $mdb;
$mdb = new MongoDBHelper();
global $currentPatch;
$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");
$counter = 0;
$currentTimestamp = time();
$fileExistsCache = array();
$matchDataCache = array();
global $rankingAttributeArray;
$rankingAttributeArray = array("Kills", "Deaths", "Assists", "KDA", "KillParticipation", "CS", "Gold", "VisionScore", "WardTakedowns", "WardsPlaced", "WardsGuarded", "VisionWards", "Consumables", "TurretPlates", "TotalTakedowns", "TurretTakedowns",
"InhibitorTakedowns", "DragonTakedowns", "HeraldTakedowns", "DamageToBuildings", "DamageToObjectives", "DamageMitigated", "DamageDealtToChampions", "DamageTaken", "TeamShielded", "TeamHealed", "TimeCC", "DeathTime", "SkillshotsDodged", "SkillshotsHit");
global $cleanAttributeArray;
$cleanAttributeArray = array("kills", "deaths", "assists", "kda", "killParticipation", "totalMinionsKilled", "goldEarned", "visionScore", "wardTakedowns", "wardsPlaced", "wardsGuarded", "detectorWardsPlaced", "consumablesPurchased", "turretPlatesTaken",
"takedowns", "turretTakedowns", "inhibitorTakedowns", "dragonTakedowns", "riftHeraldTakedowns", "damageDealtToBuildings", "damageDealtToObjectives", "damageSelfMitigated", "totalDamageDealtToChampions", "totalDamageTaken", "totalDamageShieldedOnTeammates",
"totalHealsOnTeammates", "totalTimeCCDealt", "totalTimeSpentDead", "skillshotsDodged", "skillshotsHit", "championName", "championTransform", "individualPosition", "teamId", "teamPosition", "lane", "puuid", "summonerId","summonerName", "win", "neutralMinionsKilled");

/** Important performance-saving function to collect locally stored matchdata into dynamically used array
 * This function loops through every given matchID's matchID.json and adds the data to a single $matchData array
 * At the same time collecting the necessary memory amount and limiting the returnvalue to 500 matchIDs or 256MB of RAM at once
 *
 * @param array $matchIDArray Inputarray of all MatchIDs of the user
 * @var int $startMemory The necessary value to retrieve information about current stored memory amount of the array
 *
 * Returnvalue:
 * @return array $matchData Array full of all given MatchID.json file contents up to the below maximum
 */
function getMatchData($matchIDArray){
    global $mdb, $cleanAttributeArray;
    $startMemory = memory_get_usage();
    $matchData = array();

    $pipeline = [
        [
            '$match' => ['metadata.matchId' => ['$in' => $matchIDArray]],
        ],
        [
            '$project' => [
                'metadata.dataVersion' => 0,
                'metadata.participants' => 0,
                'info.gameId' => 0,
                'info.gameMode' => 0,
                'info.gameName' => 0,
                'info.gameType' => 0,
                'info.mapId' => 0,
                'info.platformId' => 0,
                'info.queueId' => 0,
                'info.teams' => 0,
                'info.tournamentCode' => 0,
                'info.participants.allInPings' => 0,
                'info.participants.assistMePings' => 0,
                'info.participants.baitPings' => 0,
                'info.participants.baronKills' => 0,
                'info.participants.basicPings' => 0,
                'info.participants.bountyLevel' => 0,
            ],
        ],
    ];

    // Call the aggregation pipeline
    $cursor = $mdb->aggregate("matches", $pipeline, []);

    // Process the results as needed
    foreach ($cursor as $document) {
        if(memory_get_usage() - $startMemory > "268435456") return $matchData; // If matchData array bigger than 256MB size or more than 500 matches -> stop and return
        $document->info->gameVersion = explode(".",$document->info->gameVersion)[0].".".explode(".",$document->info->gameVersion)[1];
        foreach($document->info->participants as $player){
            unset($player->allInPings);
            unset($player->assistMePings);
            unset($player->baitPings);
            unset($player->baronKills);
            unset($player->basicPings);
            unset($player->bountyLevel);
            foreach($player->challenges as $challengeName => $challValue){
                if(!in_array($challengeName, $cleanAttributeArray)){
                    unset($player->challenges->$challengeName);
                }
            }
            foreach($player as $statName => $statValue){
                if(!in_array($statName, $cleanAttributeArray) && $statName != "challenges"){
                    unset($player->$statName);
                }
            }
        }
        $matchData[$document->metadata->matchId] = $document;
        foreach ($matchData as $singleMatchData) {
            unset($singleMatchData->metadata);
        }
    }
    return $matchData;
}

/** Function to convert seconds to readable time
 *
 * @param int $seconds The amount of seconds given that we wan't to convert to human-readable time words
 *
 * Returnvalue:
 * @return string|null Depending on switch case as seen below, but string sentence
 */
function secondsToTime($seconds) {
    if(is_numeric($seconds)){
        if ($seconds < 120) {
            return __("1 minute ago");
        } elseif ($seconds >= 120 && $seconds < 3600) {
            return sprintf(__("%d minutes ago"), floor($seconds / 60));
        } elseif ($seconds >= 3600 && $seconds < 7200) {
            return __("1 hour ago");
        } elseif ($seconds >= 7200 && $seconds < 86400) {
            return sprintf(__("%d hours ago"), floor($seconds / 3600));
        } elseif ($seconds >= 86400 && $seconds < 172800) {
            return __("1 day ago");
        } elseif ($seconds >= 172800 && $seconds < 2592000) {
            return sprintf(__("%d days ago"), floor($seconds / 86400));
        } elseif ($seconds >= 2592000 && $seconds < 5260000) {
            return __("1 month ago");
        } elseif ($seconds >= 5260000 && $seconds < 31104000) {
            return sprintf(__("%d months ago"), floor($seconds / 2592000));
        } elseif ($seconds >= 31104000 && $seconds < 62208000) {
            return __("1 year ago");
        } elseif ($seconds >= 62208000) {
            return sprintf(__("%d years ago"), floor($seconds / 31104000));
        }
    }
    else {
        return NULL;
    }
}

/** Detailed Team-Information about a specific clash team
 * Prints all locally stored information about all selected content stored in the players playerdata.jsons
 *
 * @param array $matchIDArray This input parameter array contains all matchIDs of a specific user
 * @param array $matchRankingArray This input parameter array is used for the displaying of a matches score
 * @param string $puuid The players PUUID
 * @var string $username Is the given username or PUUID
 * @var int $count the countervalue to display the amount of locally stored files in which the player (PUUID) is part of
 *
 * Returnvalue:
 * @return array
 *
 * @codeCoverageIgnore
 */
function printTeamMatchDetailsByPUUID($matchIDArray, $puuid, $matchRankingArray, $withButton = true){
    global $mdb;
    global $currentPatch;
    global $currentTimestamp;
    global $matchDataCache;
    $matchTestTempArray = array();
    $returnArray = array();
    $count = 0;
    $totalTeamTakedowns = 0;
    $returnString = "";
    $open = true;
    $advanced = true;

    // Initiating Matchdetail Table
    if($withButton) {
        $returnString .= "<button type='button' class='collapsible bg-dark cursor-pointer h-6 w-full'
                :aria-label='(open ? \"&#11167;\" : \"&#11165;\")'
                @click='open = !open'
                x-text='open ? \"&#11167;\" : \"&#11165;\" '></button>";
    }
    $returnString .= "<div class='smooth-transition w-full overflow-hidden' x-show='open' x-transition>";

    foreach ($matchIDArray as $matchId) {
        if(isset($matchDataCache[$matchId])){
            $matchDataCache[$matchId]->metadata->cached = true;
            $matchTestTempArray[] = (array)$matchDataCache[$matchId];
        }
    }
    // Überschneidung vom Cache und Parameter von mongodb query entfernen
    $intersection = array_intersect($matchIDArray, array_keys($matchDataCache));
    $matchIDArrayWithoutCached = array_values(array_diff($matchIDArray, $intersection));

    $fieldsToRetrieve = [
        'metadata.matchId',
        'info.gameDuration',
        'info.queueId',
        'info.gameEndTimestamp',
        'metadata.participants',
        'info.participants.puuid',
        'info.participants.teamId',
        'info.participants.gameEndedInEarlySurrender',
        'info.participants.win',
        'info.participants.championName',
        'info.participants.championTransform',
        'info.participants.champLevel',
        'info.participants.teamPosition',
        'info.participants.summoner1Id',
        'info.participants.summoner2Id',
        'info.participants.perks.styles.selections.perk',
        'info.participants.perks.styles.style',
        'info.participants.kills',
        'info.participants.deaths',
        'info.participants.assists',
        'info.participants.item0',
        'info.participants.item1',
        'info.participants.item2',
        'info.participants.item3',
        'info.participants.item4',
        'info.participants.item5',
        'info.participants.totalDamageDealtToChampions',
        'info.participants.totalDamageTaken',
        'info.participants.challenges.effectiveHealAndShielding',
        'info.participants.damageDealtToObjectives',
        'info.participants.detectorWardsPlaced',
        'info.participants.totalMinionsKilled',
        'info.participants.neutralMinionsKilled',
        'info.participants.visionScore',
        'info.participants.challenges.turretPlatesTaken',
    ];
    $result = $mdb->findDocumentsByMatchIds('matches', 'metadata.matchId', $matchIDArrayWithoutCached, $fieldsToRetrieve);

    if($result['success']){
        $matchDataArray = $result['documents'];
        // Combine cached data with new queried data
        $mergedMatchData = array_merge((array)$matchDataArray, $matchTestTempArray);
        $matchDataArray = json_decode(json_encode($mergedMatchData));
        $sortedMatchData = sortByMatchIds($matchDataArray);
        foreach($sortedMatchData as $inhalt){
            // Cache MongoDB Query Data (if not already cached) for future requests
            addToGlobalMatchDataCache($inhalt);
            if(isset($inhalt->metadata->cached)){
                echo "<script>cached++;</script>";
            }
            if(isset($inhalt->metadata->participants) && $inhalt->info->gameDuration != 0) {
                if(in_array($puuid, (array) $inhalt->metadata->participants)){
                    $count++;
                    for($in = 0; $in < 10; $in++){
                        if($inhalt->info->participants[$in]->puuid == $puuid) {
                            $returnArray[$inhalt->metadata->matchId] = array();
                            $teamID = $inhalt->info->participants[$in]->teamId;
                            if($inhalt->info->participants[$in]->gameEndedInEarlySurrender){                            
                                $returnArray[$inhalt->metadata->matchId]["EarlySurrender"] = 1;
                                $returnString .= '<div class="w-full bg-gray-800 border-b border-[4px] border-dark" x-data="{ advanced: false }" @page-advanced="advanced = true" style="content-visibility: auto;" data-matchid='.$inhalt->metadata->matchId.'>';
                            } elseif ($inhalt->info->participants[$in]->win == false){
                                $returnArray[$inhalt->metadata->matchId]["Win"] = 0;
                                $returnString .= '<div class="w-full bg-lose border-b border-[4px] border-dark" x-data="{ advanced: false }" @page-advanced="advanced = true" style="content-visibility: auto;" data-matchid='.$inhalt->metadata->matchId.'>';
                            } else {
                                $returnArray[$inhalt->metadata->matchId]["Win"] = 1;
                                $returnString .= '<div class="w-full bg-win border-b border-[4px] border-dark" x-data="{ advanced: false }" @page-advanced="advanced = true" style="content-visibility: auto;" data-matchid='.$inhalt->metadata->matchId.'>';
                            }
                                $returnString .= '<div id="match-header" class="inline-flex w-full gap-2 pt-2 px-2">';
                                    $returnString .= '<div class="match-result mb-2">';
                                    // Display of W(in) or L(ose)
                                    if($inhalt->info->participants[$in]->gameEndedInEarlySurrender){
                                        $returnString .= '<span class="text-white font-bold">'.__("R").'</span>';
                                    } elseif($inhalt->info->participants[$in]->win == true) {
                                        $returnString .= '<span class="text-online font-bold">'.__("W").'</span>';
                                    } else {
                                        $returnString .= '<span class="text-offline font-bold">'.__("L").'</span>';
                                    }
                                    $returnString .= '</div>';

                                    $returnString .= '<div class="match-type-and-time">';
                                    // Display of Ranked Queuetype & Gamelength
                                    $returnArray[$inhalt->metadata->matchId]["Queue"] = $inhalt->info->queueId;
                                    switch ($inhalt->info->queueId) {
                                        case 420:
                                            $returnString .= "<span>".__("Solo/Duo")." ";
                                            break;
                                        case 440:
                                            $returnString .= "<span>".__("Flex")." ";
                                            break;
                                        case 700:
                                            $returnString .= "<span>".__("Clash")." ";
                                            break;
                                    }
                                    $returnArray[$inhalt->metadata->matchId]["GameLength"] = $inhalt->info->gameDuration;
                                    $returnString .= gmdate("i:s", $inhalt->info->gameDuration)."</span>";
                                    $returnString .= "</div>";

                                    // $returnString .= "<div class='match-id hidden'>".$matchIDJSON."</div>";

                                    $returnString .= '<div id="match-time-ago" class="ml-auto">';

                                    // Display when the game date was, if > than 23h -> day format, if > than 30d -> month format, etc.
                                    $returnArray[$inhalt->metadata->matchId]["GameTime"] = $inhalt->info->gameEndTimestamp;
                                    $returnString .= "<span>".secondsToTime(strtotime('now')-intdiv($inhalt->info->gameEndTimestamp, 1000))."</span></div>";
                                    $returnString .= '</div>';

                                    // Display of the played champions icon
                                    $returnString .= '<div class="champion-data flex gap-2 twok:h-[68px] fullhd:h-[56px] justify-between px-2"><div class="champion-data-left inline-flex gap-2"><div class="champion-icon">';
                                    if ($inhalt->info->participants[$in])
                                    $champion = $inhalt->info->participants[$in]->championName;
                                    if($champion == "FiddleSticks"){$champion = "Fiddlesticks";}
                                    $returnArray[$inhalt->metadata->matchId]["Champion"] = $inhalt->info->participants[$in]->championName;
                                    if($champion == "Kayn"){
                                        $returnArray[$inhalt->metadata->matchId]["Transform"] = $inhalt->info->participants[$in]->championTransform;
                                        if($inhalt->info->participants[$in]->championTransform == "1"){
                                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/webp/kayn_rhaast_darkin.avif')){
                                                $returnString .= '<img src="/clashapp/data/misc/webp/kayn_rhaast_darkin.avif?version='.md5_file("/hdd1/clashapp/data/misc/webp/kayn_rhaast_darkin.avif").'" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                                $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.avif?version='.md5_file("/hdd1/clashapp/data/misc/LevelAndLaneOverlay.avif").'" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                            }
                                        } else if($inhalt->info->participants[$in]->championTransform == "2") {
                                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/webp/kayn_shadow_assassin.avif')){
                                                $returnString .= '<img src="/clashapp/data/misc/webp/kayn_shadow_assassin.avif?version='.md5_file("/hdd1/clashapp/data/misc/webp/kayn_shadow_assassin.avif").'" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                                $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.avif?version='.md5_file("/hdd1/clashapp/data/misc/LevelAndLaneOverlay.avif").'" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                            }
                                        } else {
                                            if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.avif')){
                                                $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.avif" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                                $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.avif" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                            }
                                        }
                                    } else {
                                        if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.avif')){
                                            $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.avif?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.avif').'" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.avif?version='.md5_file('/hdd1/clashapp/data/misc/LevelAndLaneOverlay.avif').'" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        } else {
                                            $returnString .= '<img src="/clashapp/data/misc/0.avif?version='.md5_file('/hdd1/clashapp/data/misc/0.avif').'" width="68" height="68" class="align-middle twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] rounded" loading="lazy" alt="This icon represents a value not being available">';
                                        }
                                    }

                                    // Save values dealt for later print below
                                    $dealt = isset($inhalt->info->participants[$in]->totalDamageDealtToChampions) ? number_format($inhalt->info->participants[$in]->totalDamageDealtToChampions, 0) : __("N/A");
                                    $tanked = isset($inhalt->info->participants[$in]->totalDamageTaken) ? number_format($inhalt->info->participants[$in]->totalDamageTaken, 0) : __("N/A");
                                    $shealed = isset($inhalt->info->participants[$in]->challenges->effectiveHealAndShielding) ? number_format($inhalt->info->participants[$in]->challenges->effectiveHealAndShielding, 0) : __("N/A");
                                    $objs = isset($inhalt->info->participants[$in]->damageDealtToObjectives) ? number_format($inhalt->info->participants[$in]->damageDealtToObjectives, 0) : __("N/A");
                                    $visionWards = isset($inhalt->info->participants[$in]->detectorWardsPlaced) ? $inhalt->info->participants[$in]->detectorWardsPlaced : __("N/A");
                                    $creepScore = isset($inhalt->info->participants[$in]->totalMinionsKilled, $inhalt->info->participants[$in]->neutralMinionsKilled) ? $inhalt->info->participants[$in]->totalMinionsKilled + $inhalt->info->participants[$in]->neutralMinionsKilled : __("N/A");
                                    $visionScore = isset($inhalt->info->participants[$in]->visionScore) ? $inhalt->info->participants[$in]->visionScore : __("N/A");
                                    $turretPlatings = isset($inhalt->info->participants[$in]->challenges->turretPlatesTaken) ? $inhalt->info->participants[$in]->challenges->turretPlatesTaken : __("N/A");
                                    $returnArray[$inhalt->metadata->matchId]["Dealt"] = $dealt;
                                    $returnArray[$inhalt->metadata->matchId]["Tanked"] = $tanked;
                                    $returnArray[$inhalt->metadata->matchId]["Shealed"] = $shealed;
                                    $returnArray[$inhalt->metadata->matchId]["Objs"] = $objs;
                                    $returnArray[$inhalt->metadata->matchId]["VisionWards"] = $visionWards;
                                    $returnArray[$inhalt->metadata->matchId]["CreepScore"] = $creepScore;
                                    $returnArray[$inhalt->metadata->matchId]["VisionScore"] = $visionScore;
                                    $returnArray[$inhalt->metadata->matchId]["TurretPlatings"] = $turretPlatings;

                            // Display of champion level at end of game
                            $returnString .= '<div class="champion-level flex relative w-4 h-4 max-w-[16px] min-w-[16px] z-20 -ml-4 twok:bottom-[17px] twok:-right-[17px] twok:text-[13px] fullhd:bottom-[8px] fullhd:-right-[15px] fullhd:text-[12px] justify-center items-center">';
                            $returnString .= $inhalt->info->participants[$in]->champLevel;
                            $returnArray[$inhalt->metadata->matchId]["ChampLevel"] = $inhalt->info->participants[$in]->champLevel;
                            $returnString .= '</div>';

                            // Display of played Position
                            $returnString .= "<div class='champion-lane flex relative w-4 h-4 twok:max-w-[16px] twok:min-w-[16px] z-20 -ml-4 twok:bottom-[33px] twok:-right-[66px] fullhd:max-w-[14px] fullhd:min-w-[14px] fullhd:bottom-[25px] fullhd:-right-[56px] justify-center items-center'>";
                            $matchLane = $inhalt->info->participants[$in]->teamPosition;
                            $returnArray[$inhalt->metadata->matchId]["Lane"] = $inhalt->info->participants[$in]->teamPosition;
                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/lanes/'.$matchLane.'.avif')){
                                $returnString .= '<img src="/clashapp/data/misc/lanes/'.$matchLane.'.avif?version='.md5_file('/hdd1/clashapp/data/misc/lanes/'.$matchLane.'.avif').'" width="16" height="16"  loading="lazy" class="max-w-[16px] min-w-[16px] saturate-0 brightness-150" alt="Icon of a league of legends position for '.$matchLane.'">';
                            }
                            $returnString .= "</div>";
                            $returnString .= "</div>";

                            // Display summoner spells
                            $returnString .= '<div class="summoner-spells grid grid-rows-2 gap-1 twok:max-w-[32px] fullhd:max-w-[26px]">';
                            $summoner1Id = $inhalt->info->participants[$in]->summoner1Id;
                            $summoner2Id = $inhalt->info->participants[$in]->summoner2Id;
                            $returnArray[$inhalt->metadata->matchId]["Spell1"] = summonerSpellFetcher($inhalt->info->participants[$in]->summoner1Id);
                            $returnArray[$inhalt->metadata->matchId]["Spell2"] = summonerSpellFetcher($inhalt->info->participants[$in]->summoner2Id);
                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).".avif")){
                                $returnString .= '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).'.avif?version='.md5_file('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).'.avif').'" width="32" height="32" class="rounded" loading="lazy" alt="Icon of a players first selected summoner spell">';
                            }
                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).".avif")){
                                $returnString .= '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).'.avif?version='.md5_file('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).'.avif').'" width="32" height="32" class="rounded" loading="lazy" alt="Icon of a players second selected summoner spell">';
                            }
                            $returnString .= "</div>";



                            // Display of the equipped keyrune + secondary tree
                            $returnString .= '<div class="rune-container grid grid-cols-2 grid-rows-2 gap-y-1">';
                            $returnString .= "<div class='flex col-span-2 row-span-1 justify-start items-center gap-1'>";
                            $keyRune = $inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk;
                            $secRune = $inhalt->info->participants[$in]->perks->styles[1]->style;
                            $returnArray[$inhalt->metadata->matchId]["Keyrune"] = runeIconFetcher($inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk);
                            $returnArray[$inhalt->metadata->matchId]["Secrune"] = runeTreeIconFetcher($inhalt->info->participants[$in]->perks->styles[1]->style);
                            if(fileExistsWithCache('/hdd1/clashapp/data/patch/img/'.runeIconFetcher($keyRune).'.avif')){
                                $returnString .= '<img src="/clashapp/data/patch/img/'.runeIconFetcher($keyRune).'.avif?version='.md5_file('/hdd1/clashapp/data/patch/img/'.runeIconFetcher($keyRune).'.avif').'" width="32" height="32" loading="lazy" alt="Icon of a players first selected rune" class="fullhd:max-w-[26px] twok:max-w-[32px]">';
                            } else {
                                $returnString .= '<img src="/clashapp/data/misc/0.avif?version='.md5_file('/hdd1/clashapp/data/misc/0.avif').'" width="32" height="32" loading="lazy" alt="This icon represents a value not being available" class="fullhd:max-w-[26px] twok:max-w-[32px]">';
                            }
                            if(fileExistsWithCache('/hdd1/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune).'.avif')){
                                $returnString .= '<img src="/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune).'.avif?version='.md5_file('/hdd1/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune).'.avif').'" height="18" width="18" class="m-auto" loading="lazy" alt="Icon of a players second selected rune" class="fullhd:max-w-[14.625px] twok:max-w-[18px]">';
                            } else {
                                $returnString .= '<img src="/clashapp/data/misc/0.avif?version='.md5_file('/hdd1/clashapp/data/misc/0.avif').'" width="18" height="18" loading="lazy" alt="This icon represents a value not being available" class="fullhd:max-w-[14.625px] twok:max-w-[18px]">';
                            }
                            $returnString .= "</div>";

                            // calculate of Match Score 1-10
                            foreach ($matchRankingArray as $matchID => $rankingValue){
                                // print_r($matchID."<br>");
                                if($matchID == $inhalt->metadata->matchId){
                                    $matchScore = $matchRankingArray[$matchID];
                                }
                            }

                            // Display Matchscore
                            $returnString .= '<div class="matchscore-container flex row-span-1 col-span-2 justify-center items-center">';
                            $returnArray[$inhalt->metadata->matchId]["MatchScore"] = $matchScore;
                            if($matchScore == "" || $matchScore == "N/A"){
                                $returnString .= "<span class='cursor-help' onmouseenter='showTooltip(this, \"".__("Game length below minimum of 10min")."\", 500, \"top-right\")' onmouseleave='hideTooltip(this)'>&Oslash; N/A</span>";
                            } else {
                                $returnString .= '<span>&Oslash; '.$matchScore.'</span>';
                            }
                            $returnString .= "</div></div></div>";

                            // Display of the players Kills/Deaths/Assists
                            $returnString .= '<div class="kda-stats flex flex-col justify-center items-center"><div class="stats twok:text-[1.75rem] twok:tracking-tighter fullhd:text-[1.3rem] fullhd:-tracking-[.15rem]">';
                            $kills = $inhalt->info->participants[$in]->kills;
                            $deaths = $inhalt->info->participants[$in]->deaths;
                            $assists = $inhalt->info->participants[$in]->assists;
                            $returnString .= $kills . " / ";
                            $returnString .= "<div class='inline text-threat-s'>".$deaths."</div> / ";
                            $returnString .= $assists;
                            $returnString .= '</div><div class="kda text-xs">';
                            $returnArray[$inhalt->metadata->matchId]["Kills"] = runeTreeIconFetcher($inhalt->info->participants[$in]->perks->styles[1]->style);
                            $returnArray[$inhalt->metadata->matchId]["Deaths"] = runeTreeIconFetcher($inhalt->info->participants[$in]->perks->styles[1]->style);
                            $returnArray[$inhalt->metadata->matchId]["Assists"] = runeTreeIconFetcher($inhalt->info->participants[$in]->perks->styles[1]->style);
                            if($deaths != 0){
                                $returnString .= __("KDA").": ".number_format(($kills+$assists)/$deaths, 2)."</div>";
                                $returnArray[$inhalt->metadata->matchId]["KDA"] = number_format(($kills+$assists)/$deaths, 2);
                            } else {
                                $returnString .= __("KDA").": ".number_format(($kills+$assists)/1, 2)."</div>";
                                $returnArray[$inhalt->metadata->matchId]["KDA"] = number_format(($kills+$assists)/1, 2);
                            }
                            $returnString .= "</div>";

                            // Display of the last items the user had at the end of the game in his inventory
                            $returnString .= '<div class="items grid grid-rows-2 grid-cols-3 twok:max-w-[104px] twok:min-w-[104px] fullhd:max-w-[84.5px] fullhd:min-w-[84.5px] gap-1">';
                            $noItemCounter = 0;
                            // $lastItemSlot = 0;
                            for($b=0; $b<6; $b++){
                                // if($b == 6){
                                //     for($c=0; $c<$noItemCounter; $c++){
                                //         $returnString .= '<div class="item'.($lastItemSlot+1).'">';
                                //         $returnString .= '<img src="/clashapp/data/misc/0.avif?version='.md5_file('/hdd1/clashapp/data/misc/0.avif').'" width="32" loading="lazy">';
                                //         $returnString .= '</div>';
                                //         $lastItemSlot++;
                                //     }
                                //     $returnString .= '<div class="trinket">';
                                // }
                                $allItems = "item".$b;
                                $itemId = $inhalt->info->participants[$in]->$allItems;
                                $returnArray[$inhalt->metadata->matchId][$allItems] = $inhalt->info->participants[$in]->$allItems;
                                if($itemId == 0){
                                    $noItemCounter += 1;
                                } else {
                                    $returnString .= '<div class="item'.($b - $noItemCounter).'">';
                                    if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/item/'.$itemId.'.avif')){
                                        $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/' . $itemId . '.avif?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/item/' . $itemId . '.avif').'" width="32" height="32" loading="lazy" class="rounded" alt="This icon represents an equipped item at the end of a game">';
                                    } else if(fileExistsWithCache('/hdd1/clashapp/data/misc/'.$itemId.'.avif')){
                                        $returnString .= '<img src="/clashapp/data/misc/'.$itemId.'.avif?version='.md5_file('/hdd1/clashapp/data/misc/'.$itemId.'.avif').'" width="32" height="32" loading="lazy" class="rounded" alt="This icon represents an equipped special ornn item at the end of the game or other exceptions">';
                                    } else {
                                        $returnString .= '<img src="/clashapp/data/misc/0.avif?version='.md5_file('/hdd1/clashapp/data/misc/0.avif').'" width="32" height="32" loading="lazy" class="rounded" alt="This icon will only be visible of neither the data dragon nor the local files contain the corresponding image">';
                                    }
                                    // $lastItemSlot = $b;
                                    $returnString .= "</div>";
                                }
                            }
                            for($i=0; $i<$noItemCounter; $i++){
                                $returnString .= '<div class="emptySlot block w-8 h-8 rounded bg-dark opacity-40"></div>';
                            }
                            $returnString .= "</div>";
                            // Calculate own Takedowns of Kill Participation
                            $ownTakedowns = 0;
                            $ownTakedowns += $inhalt->info->participants[$in]->kills;
                            $ownTakedowns += $inhalt->info->participants[$in]->assists;
                        }
                    }

                    $returnString .= '</div>';
                    $returnString .= '<div class="additional-info px-2" x-cloak x-show="advanced || advancedGlobal" x-transition><div class="additional-info-1 grid twok:grid-cols-[1fr_1fr_1fr_1fr_46px_auto] fullhd:twok:grid-cols-[1fr_1fr_1fr_1fr_37.375px_auto] twok:text-base fullhd:text-[13px] grid-rows-3 justify-center items-center gap-1 mt-2 text-sm">';
                    // Display of enemy champions icon in lane
                        for($i = 0; $i < 10; $i++){
                            if (($inhalt->info->participants[$i]->teamPosition == $matchLane) && ($inhalt->info->participants[$i]->championName != $champion)){
                            $returnString .= '<div class="lane-opponent col-span-1 row-span-1 h-full flex justify-center items-center gap-2"><span>vs. </span>';
                            $enemyChamp = $inhalt->info->participants[$i]->championName;
                            if($enemyChamp == "FiddleSticks"){$enemyChamp = "Fiddlesticks";}
                            if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.avif')){
                                $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.avif?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.avif').'" width="32" height="32" class="twok:max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="This icon represents the champion '.$enemyChamp.', but tinier as a normal champion icon as it shows the enemy laner"></div>';
                            } else {
                                $returnString .= '<img src="/clashapp/data/misc/0.avif?version='.md5_file('/hdd1/clashapp/data/misc/0.avif').'" width="32" height="32" class="twok:max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="This icon represents a value not being available"></div>';
                            }
                            }
                            if ($inhalt->info->participants[$i]->teamId == $teamID){
                                $totalTeamTakedowns += $inhalt->info->participants[$i]->kills;
                            }
                        }

                        $returnString .= '<div class="damage-dealt col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/Dealt.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/Dealt.avif').'" width="24" height="26" class="twok:max-w-[24px] fullhd:max-w-[19.5px]" loading="lazy" alt="An icon of a sword clashing through a bone">';
                        $returnString .= '<span>'.$dealt.'</span>';
                        $returnString .= '</div>';


                        $returnString .= '<div class="kill-participation col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/KillParticipation.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/KillParticipation.avif').'" width="32" height="26" class="max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="An icon of two swords clashing with each other">';
                            if($totalTeamTakedowns != 0){
                                $returnString .= "<span>".number_format(($ownTakedowns/$totalTeamTakedowns)*100, 0). "%</span>";
                                $returnArray[$inhalt->metadata->matchId]["KillParticipation"] = number_format(($ownTakedowns/$totalTeamTakedowns)*100, 0);
                            } else {
                                $returnString .= "<span>0%</span>";
                                $returnArray[$inhalt->metadata->matchId]["KillParticipation"] = 0;
                            }
                        $returnString .= '</div>';

                        $returnString .= '<div class="visionscore col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/VisionScore.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/VisionScore.avif').'" width="36" height="23" class="max-w-[36px] fullhd:max-w-[29.25px]" loading="lazy" alt="An icon of a vision ward from League of Legends">';
                        $returnString .= '<span>'.$visionScore.'</span>';
                        $returnString .= "</div>";

                        $returnString .= '<div class="col-span-1 row-span-2 h-full flex justify-center items-center">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/Turret.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/Turret.avif').'" width="36" height="76" class="twok:max-w-[36px] fullhd:max-w-[29.25px]" loading="lazy" alt="An icon of a tower from League of Legends">';
                        $returnString .= '</div>';

                        $returnString .= '<div class="damage-to-objectives col-span-1 row-span-1 h-full flex justify-center items-center">';
                        $returnString .= __("Objs").": ".$objs;
                        $returnString .= "</div>";

                        $returnString .= '<div class="creepscore col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/Creepscore.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/Creepscore.avif').'" width="32" height="19" class="twok:max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="An icon of two coins">';
                        $returnString .= '<span>'.$creepScore.'</span>';
                        $returnString .= "</div>";

                        $returnString .= '<div class="damage-tanked col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/Tanked.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/Tanked.avif').'" width="20.5" height="26" class="twok:max-w-[20.5px] fullhd:max-w-[16.65625px]" loading="lazy" alt="An icon of a shield with two cracks">';
                        $returnString .= '<span>'.$tanked.'</span>';
                        $returnString .= '</div>';

                        $returnString .= '<div class="damage-healed-and-shielded col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/Shealed.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/Shealed.avif').'" width="27" height="28" class="twok:max-w-[27px] fullhd:max-w-[21.9375px]" loading="lazy" alt="An icon of a plus symbol converging into a shield">';
                        $returnString .= '<span>'.$shealed.'</span>';
                        $returnString .= "</div>";

                        $returnString .= '<div class="control-wards col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                        $returnString .= '<img src="/clashapp/data/misc/icons/ControlWard.avif?version='.md5_file('/hdd1/clashapp/data/misc/icons/ControlWard.avif').'" width="36" height="25" class="twok:max-w-[36px] fullhd:max-w-[29.25px]" loading="lazy" alt="An icon of a control ward from League of Legends">';
                        $returnString .= '<span>'.$visionWards.'</span>';
                        $returnString .= "</div>";

                        $returnString .= '<div class="turret-platings col-span-1 row-span-1 h-full flex justify-center items-center">';
                        $returnString .= __("Platings").': '.$turretPlatings;
                        $returnString .= "</div>";

                        $returnString .= '<div class="match-tag-container col-span-6 row-span-1 h-full flex justify-start items-center gap-4 ">';
                        $returnString .= "</div>";

                        $returnString .= '</div></div>';
                        $returnString .= "<button type='button' :aria-label='(advanced ? \"&#11165;\" : \"&#11167;\")'  class='collapsible bg-[#0e0f18] cursor-pointer h-6 w-full opacity-50 mt-4' @click='advanced = !advanced' x-text='advanced ? \"&#11165;\" : \"&#11167;\"'></button>";
                        $returnString .= '</div>';

                    $totalTeamTakedowns = 0; // Necessary to reset Kill Participation
                    }
                }
            }
        } else {
        return "";
    }


    $returnString .= "</div>";
    $returnArray['OldString'] = $returnString;
    return $returnArray;
    // End of Matchdetail Table & Counttext of local specific amount
    // $returnString += "<br>Es wurden " . $count ." lokale Matchdaten gefunden<br>";
}

/** Followup function to print API::getMasteryScores(); returninfo
 * This function is only printing collected values, also possible to shove into profile.php
 *
 * @param array $masteryArray Inputarray of all MasteryScores
 * @param int $index Index of the masterychamp (0 = first & highest mastery champ, 1 = second, etc.)
 *
 * Returnvalue:
 * @return void N/A, just printing values to page TODO: Write possible testcase for this
 * 
 * @codeCoverageIgnore
 */
function printMasteryInfo($masteryArray, $index){
    global $currentPatch;

    // Print image if it exists
    if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.avif')){
        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.avif?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.avif').'" width="64" height="64" alt="A league of legends champion icon of '.$masteryArray[$index]["Filename"].'"><br>';
    }

    // Print the additional info
    echo $masteryArray[$index]["Champion"]."<br>";
    echo "<br>Mastery Level: ".$masteryArray[$index]["Lvl"]."<br>";
    echo "Points: ".$masteryArray[$index]["Points"]."<br>";
    echo "Last played: ".date('d.m.Y', $masteryArray[$index]["LastPlayed"]);
    return;
}

/** Fetching rune icon ID to image path
 * This function iterates through the current patches runesReforged.json and returns the folder of the rune icons
 *
 * @param string $id The passed rune ID corresponding to Riot's data found in the runesReforged.json
 * @var array $data Content of the runesReforged.json containing any image path for any rune ID
 *
 * Returnvalue:
 * @return string $rune->icon Path of Iconimage
 */
function runeIconFetcher($id){
    global $currentPatch;
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/en_US/runesReforged.json');
    $json = json_decode($data);
    foreach($json as $runetree){
        foreach($runetree->slots as $keyRunes){
            foreach($keyRunes as $runeid){
                foreach($runeid as $rune){
                    if($id == $rune->id){
                        return substr($rune->icon, 0, -4);
                    }
                }
            }
        }
    }
    return "";
}

/** Returning random icon ID between 1 - 28 except for the current given icon ID
 *
 * @param int $currentIconID The passed icon ID corresponding to a user
 * @var int $randomIconID The randomly selected icon ID between 1 - 28
 *
 * Returnvalue:
 * @return int $randomIconID
 */
function getRandomIcon($currentIconID){
    if($currentIconID >= 1 && $currentIconID <= 28){
        do {
            $randomIconID = rand(1,28);
        } while($currentIconID == $randomIconID);
    } else {
        $randomIconID = rand(1,28);
    }
    return $randomIconID;
}

/** Summoner spell icon ID to image path
 * This function iterates through the current patches summoner.json and returns the folder of the summoner icons
 *
 * @param string $id The passed summoner icon ID corresponding to Riot's data found in the summoner.json
 * @var array $data Content of the summoner.json containing any image path for any summoner icon ID
 *
 * Returnvalue:
 * @return string $summoner->id Path of Iconimage
 */
function summonerSpellFetcher($id){
    global $currentPatch;
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/summoner.json');
    $json = json_decode($data);
    foreach($json->data as $summoner){
        if($id == $summoner->key){
            return $summoner->id;
        }
    }
    return "";
}

/** Fetching runetree icon ID to image path
 * This function iterates through the current patches runesReforged.json and returns the folder of the runetree icons
 *
 * @param string $id The passed runetree ID corresponding to Riot's data found in the runesReforged.json
 * @var array $data Content of the runesReforged.json containing any image path for any rune icon ID
 *
 * Returnvalue:
 * @return string $runetree->icon Path of Iconimage
 */
function runeTreeIconFetcher($id){
    global $currentPatch;
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json');
    $json = json_decode($data);
    foreach($json as $runetree){
        if($id == $runetree->id){
            return substr($runetree->icon, 0, -4);
        }
    }
    return "";
}

function randRuneTreeIcon(){
    global $currentPatch;
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json');
    $json = json_decode($data, true);
    $randomPath = $json[array_rand($json)];
    $runes = $randomPath['slots'][0]['runes'];
    $icons = array_column($runes, 'icon');
    $randomIcon = $icons[array_rand($icons)];
    return $randomIcon;
}

function randSummonerSpell(){
    $summonerSpells = array(
        "SummonerBarrier",
        "SummonerDot",
        "SummonerExhaust",
        "SummonerHaste",
        "SummonerHeal",
        "SummonerSmite",
        "SummonerTeleport"
    );
    $randomSpell = $summonerSpells[array_rand($summonerSpells)];

    return $randomSpell;
}

function randGameLane(){
    $lanes = array(
        "BOTTOM",
        "UTILITY",
        "MIDDLE",
        "TOP",
        "JUNGLE",
    );
    $randomLane = $lanes[array_rand($lanes)];

    return $randomLane;
}


/** Resolving a championid to the champions clean name
 * This function iterates through the current patches champion.json and returns the name of the champion given by id
 *
 * @param string $id The passed champion ID corresponding to Riot's data found in the champion.json
 * @var array $data Content of the champion.json containing all necessary champion data like their clear names and IDs
 *
 * Returnvalue:
 * @return string|void $champion->name The clean name of the champion
 */
function championIdToName($id){
    global $currentPatch;
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
    $json = json_decode($data);
    foreach($json->data as $champion){
        if($id == $champion->key){
            return $champion->name;
        }
    }
    return;
}

/** Resolving a championid to the champions filename
 * This function iterates through the current patches champion.json and returns the name of the champions image file given by id
 *
 * @param string $id The passed champion ID corresponding to Riot's data found in the champion.json
 * @var array $data Content of the champion.json containing all necessary champion data like their clear names and IDs
 *
 * Returnvalue:
 * @return string|void $champion->id The filename of the champion
 */
function championIdToFilename($id){
    global $currentPatch;
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
    $json = json_decode($data);
    foreach($json->data as $champion){
        if($id == $champion->key){
            return $champion->id;
        }
    }
    return;
}

/** Fetches the 3 most common values of specific attributes
 * This function retrieves the 3 most common occurences of a specific attribute by iterating through a users matches
 * It is possible that it gets executed multiple times for multiple attributes, therefore $attributes is an array();
 *
 * @param array $attributesArray Array of every attribute that we want to check via this function
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $puuid The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * @param int $counter An input counter used for getting the selected data in the second part of this function
 *
 * Returnvalue:
 * @return array $mostCommonReturn Array containing the sorted most common of specific attributes
 */
function getMostCommon($attributesArray, $matchDataArray, $puuid, $counter){
    $mostCommonArray = array();
    $mostCommonReturn = array();

    // Store all values into separate array corresponding to each attribute
    foreach ($matchDataArray as $matchData) {
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid) {
                foreach ($attributesArray as $attribute){
                    if($matchData->info->participants[$i]->$attribute != ""){
                        $mostCommonArray[$attribute][] = $matchData->info->participants[$i]->$attribute;
                    }
                }
            }
        }
    }

    // Count, Sort and Slice to retrieve selected data
    foreach ($attributesArray as $attribute){
        $temp[$attribute] = array_count_values($mostCommonArray[$attribute]);
        arsort($temp[$attribute]);
        $values[$attribute] = array_slice(array_keys($temp[$attribute]), 0, $counter+1, true);
        $count[$attribute] = array_slice(array_values($temp[$attribute]), 0, $counter+1, true);
        for($i = 0; $i <= $counter; $i++){
            if(isset($count[$attribute][$i])) $mostCommonReturn[$attribute][$values[$attribute][$i]] = $count[$attribute][$i];
        }
    }
    return $mostCommonReturn;
}

/** Calculating the percentage of specific lane presence over given matches
 * This function calculates how high the percentage on the first two most common laning positions is. It's an advanced "getMostCommon" function for lanes
 *
 * @param array $matchDaten The compacted information of all matches of a user in a single array (performance reasons)
 * @param string $puuid The personal users ID set by Riot Games and fetched either from the players own json file or via an API request
 * @var array $laneCountArray An array containing the position and count (of matches played there) of all 5 lanes (BOTTOM, UTILITY, MID, TOP, JUNGLE)
 * @var int $matchCount The whole count of all matches played, used for calculation of percentages
 * @var string $mainLane The highest percentage of games played on this lane
 * @var string $secondaryLane The second highest percentage of games played on this lane
 *
 * Returnvalue:
 * @return array $laneReturnArray An array containing the two most-played lanes
 */
function getLanePercentages($matchDaten, $puuid){
    $laneReturnArray = array();
    $laneCountArray = getMostCommon(array("teamPosition"), $matchDaten, $puuid, 4)['teamPosition'];
    $matchCount = array_sum($laneCountArray);
    foreach ($laneCountArray as $key => $count){
        $laneCountArray[$key] = number_format(($count / $matchCount * 100), 2);
    }
    // @codeCoverageIgnoreStart
    if (array_values($laneCountArray)[0] >= 90){
        $mainLane = array_keys($laneCountArray)[0];
        $secondaryLane = "";
    } else if (array_values($laneCountArray)[0] <= 40){
        $mainLane = "FILL";
        $secondaryLane = "";
    } else if (array_values($laneCountArray)[1] <= 20){
        $mainLane = array_keys($laneCountArray)[0];
        $secondaryLane = "FILL";
    } else if (array_values($laneCountArray)[1] >= 20){
        $mainLane = array_keys($laneCountArray)[0];
        $secondaryLane = array_keys($laneCountArray)[1];
    } else {
        $mainLane = array_keys($laneCountArray)[0];
        $secondaryLane = array_keys($laneCountArray)[1];
    }
    // @codeCoverageIgnoreEnd
    $laneReturnArray[0] = $mainLane;
    $laneReturnArray[1] = $secondaryLane;

    return $laneReturnArray;
}


/**
 * This function fetches the tags of a player generated by comparing them with our calculated average stats
 * @param array $matchDaten The compacted information of all matches of a user in a single array (performance reasons)
 * @param string $puuid The personal users ID set by Riot Games and fetched either from the players own json file or via an API request
 *
 * Returnvalue:
 * @return array $tagReturnArray An array containing all fitting tags of a player
 */
function getPlayerTags($matchDaten, $puuid){
    $tagReturnArray = array();
    $tempAverageArray = array();
    $generalKey = array();
    $statNameArray = array("assists","consumablesPurchased","damageDealtToBuildings","damageDealtToObjectives","damageSelfMitigated","deaths","detectorWardsPlaced","goldEarned","inhibitorTakedowns",
    "kills","neutralMinionsKilled","totalDamageDealtToChampions","totalDamageShieldedOnTeammates","totalDamageTaken","totalHealsOnTeammates","totalMinionsKilled","totalTimeCCDealt","totalTimeSpentDead",
    "turretTakedowns","visionScore","wardsPlaced");

    // Step 1: Fetch average stats over all match data

    foreach($matchDaten as $singleMatch){                            // For every match
        foreach ($singleMatch->info->participants as $participant) { // For every player
            if ($participant->puuid === $puuid) {                    // That is us
                if(!isset($tempAverageArray[$participant->teamPosition])){ // Preinitialize the specific played lanes for further comparison later on
                    $tempAverageArray[$participant->teamPosition] = array();
                }
                if (isset($tempAverageArray[$participant->teamPosition]["gameCount"])) { // Set & increment gameCount for every match played as $lane
                    $tempAverageArray[$participant->teamPosition]["gameCount"]++;
                } else {
                    $tempAverageArray[$participant->teamPosition]["gameCount"] = 1;
                }
                foreach($participant->challenges as $challenge => $value) { // Get all challenge sums
                    if(!isset($tempAverageArray[$participant->teamPosition][$challenge])){
                        $tempAverageArray[$participant->teamPosition][$challenge] = $value;
                    } else {
                        $tempAverageArray[$participant->teamPosition][$challenge] += $value;
                    }
                }
                foreach($participant as $statName => $statValue){ // Get all stat sums
                    if(in_array($statName, $statNameArray)){
                        if(!isset($tempAverageArray[$participant->teamPosition][$statName])){
                            $tempAverageArray[$participant->teamPosition][$statName] = $statValue;
                        } else {
                            $tempAverageArray[$participant->teamPosition][$statName] += $statValue;
                        }
                    }
                }
            }
        }
    }

    // Step 2: Generate FILL array

    foreach ($tempAverageArray as $lane) {
        foreach ($lane as $key => $value) {
            if (!isset($generalKey[$key])) {
                $generalKey[$key] = $value;
            } else {
                // @codeCoverageIgnoreStart
                $generalKey[$key] += $value;
                // @codeCoverageIgnoreEnd
            }
        }
    }
    $tempAverageArray["FILL"] = $generalKey;

    // Step 3: Calculate averages over gameCount

    foreach ($tempAverageArray as $lane => $data) {
        foreach ($data as $key => $value) {
            if ($key != "gameCount" && $data["gameCount"] != 0) {
                $finalValue = $value / $data["gameCount"];
                if (preg_match('/\.\d{2,}$/', $finalValue)) { // If the number has 2 or more decimals
                    $tempAverageArray[$lane][$key] = number_format($finalValue, 2, ".", "");
                } else {
                    $tempAverageArray[$lane][$key] = $value / $data["gameCount"];
                }
            }
        }
    }

    // Step 4: Compare averages with averageStats.json (fetched in scripts/statFetcher.py)
    $averageStats = json_decode(file_get_contents('/hdd1/clashapp/data/misc/averageStats.json'), true);

    // Loop through tempAverageArray and compare with averageStats
    foreach ($tempAverageArray as $lane => $data) {
        foreach ($data as $key => $value) {
            if ($key != "gameCount" && isset($averageStats[$lane][$key])) {
                if($value != 0){
                    $averageValue = $averageStats[$lane][$key];
                    $difference = (($value - $averageValue) / abs($averageValue));
                    if ($difference <= -0.5 || $difference >= 0.5) { // Only save difference if relevant
                        $tagReturnArray[$lane][$key] = number_format($difference, 2);
                    }
                }
            }
        }
    }

    return $tagReturnArray; // TODO: Add Lane differentiating
}

/** Fetches the average value of specific attributes
 * This function retrieves the average value of a specific attribute by iterating through a users matches
 * It is possible that it gets executed multiple times for multiple attributes, therefore $attributes is an array();
 *
 * @param array $attributesArray Array of every attribute that we want to check via this function
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $puuid The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * @param string $lane The lane on where this function should fetch/get the averages from, as we collect the values for each lane separately
 * @var array $averageArray The returnvalue
 *
 * Returnvalue:
 * @return array $averageArray
 */
function getAverage($attributesArray, $matchDataArray, $puuid, $lane){
    $averageArray = array();
    $counterArray = array();
    $averageStatsJson = json_decode(file_get_contents('/hdd1/clashapp/data/misc/averageStats.json'), true);

    // Store all values into separate array corresponding to each attribute
    foreach ($matchDataArray as $matchData) {
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid) {
                foreach ($attributesArray as $attribute){
                    if(!isset($averageArray[$attribute])){
                        $averageArray[$attribute]["SELF"] = 0;
                    }
                    if(!isset($counterArray[$attribute])){
                        $counterArray[$attribute]["SELF"] = 0;
                    }
                    if(isset($matchData->info->participants[$i]->$attribute)){
                        $averageArray[$attribute]["SELF"] += $matchData->info->participants[$i]->$attribute;
                        $counterArray[$attribute]["SELF"] += 1;
                    } else if(isset($matchData->info->participants[$i]->challenges->$attribute)){
                        $averageArray[$attribute]["SELF"] += $matchData->info->participants[$i]->challenges->$attribute;
                        $counterArray[$attribute]["SELF"] += 1;
                    }
                    foreach ($averageStatsJson as $laneKey => $lanes) {
                        foreach ($lanes as $attributeCode => $value) {
                            if($attribute == $attributeCode){
                                $averageArray[$attribute][$laneKey] = $value;
                            }
                        }
                    }
                }
            }
        }
    }

    foreach($averageArray as $attributeKey => $data){
        if(isset($counterArray[$attributeKey]) && $counterArray[$attributeKey] > 0){
            $averageArray[$attributeKey]['SELF'] = number_format($averageArray[$attributeKey]['SELF'] / $counterArray[$attributeKey]['SELF'], 2, '.', '') + 0; // The + 0 removes unnecessary zeros
        }
    }

    return $averageArray;
}

/** getHighestWinrateOrMostLossesAgainst Aliase
 *  Aliase for the two getHighestWinrateOrMostLossesAgainst function possibilities to make it clearer
 */
function getMostLossesAgainst($variant, $matchDataArray, $puuid){ return getHighestWinrateOrMostLossesAgainst("mostLosses", $variant, $matchDataArray, $puuid);}
function getHighestWinrateAgainst($variant, $matchDataArray, $puuid){ return getHighestWinrateOrMostLossesAgainst("highestWinrate", $variant, $matchDataArray, $puuid);}

/** Function to retrieve the Highest Winrate Against or Most Losses against a specific champion
 * This function is only printing collected values, also possible to shove into profile.php
 *
 * @param string $type Either "mostLosses" or "highestWinrate" depending on which way the function should proceed
 * @param string $variant Either "lane" or "general" depending on wether you want to check for opponent laner or general disregarding if they played on the same lane
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $puuid The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * @var array $returnArray The final array where we story the collected values
 * @var array $maxCountArray The array to collect all counts
 *              E.g. disregarding the functions $type the highest count of a match against enemy player, like if the player played the most against Yasuo with 42 matches
 *              Then takes this 42 matches and halves it for the maxCount to shorten the returnArray later and unsset any value with too low counts
 * @var array $champArray In the second half of this function the containing all the champion data from "Win", "Lose", "Count" and "Winrate"
 *
 * Returnvalue:
 * @return array $champArray
 */
function getHighestWinrateOrMostLossesAgainst($type, $variant, $matchDataArray, $puuid){ // TODO: Reimplement function to be used on page
    $returnArray = array();
    $maxCountArray = array();
    $champArray = array();

    // Looping through all files & collecting the users data in returnArray[matchid][0]
    foreach ($matchDataArray as $matchDataId => $matchData) {
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid){
                if($matchData->info->participants[$i]->teamPosition != ""){
                    $ourLane = $matchData->info->participants[$i]->teamPosition;
                    // Fallback to individualPosition or N/A if riot is missing data
                    // @codeCoverageIgnoreStart
                } else if ($matchData->info->participants[$i]->individualPosition != "" && $matchData->info->participants[$i]->individualPosition != "Invalid"){
                    $ourLane = $matchData->info->participants[$i]->individualPosition;
                } else {
                    $ourLane = "N/A";
                    // @codeCoverageIgnoreEnd
                }
                $returnArray[$matchDataId][] = ["lane" => $ourLane, "champion" => $matchData->info->participants[$i]->championName, "win" => $matchData->info->participants[$i]->win, "teamID" => $matchData->info->participants[$i]->teamId];
                break;
            }
        }

        // Second loop, necessary after the first one because of the if comparison below (!= $returnArray[$matchDataId][0]["win"])
        // Looping again through all users and collecting users data in returnArray[matchid][1-5] if in enemy team of the user (PUUID) above
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->win != $returnArray[$matchDataId][0]["win"] && $matchData->info->participants[$i]->teamId != $returnArray[$matchDataId][0]["teamID"]){
                if($matchData->info->participants[$i]->teamPosition != ""){
                    $enemyLane = $matchData->info->participants[$i]->teamPosition;
                    // Fallback to individualPosition or N/A if riot is missing data
                    // @codeCoverageIgnoreStart
                } else if ($matchData->info->participants[$i]->individualPosition != "" && $matchData->info->participants[$i]->individualPosition != "Invalid"){
                    $enemyLane = $matchData->info->participants[$i]->individualPosition;
                } else {
                    $enemyLane = "N/A";
                    // @codeCoverageIgnoreEnd
                }

                $returnArray[$matchDataId][] = ["lane" => $enemyLane, "champion" => $matchData->info->participants[$i]->championName, "win" => $matchData->info->participants[$i]->win];
            }
        }
    }

    // Get Wins, Loses, Count and Winrate on lane sorted in $champArray
    if($variant == "lane"){
        foreach ($returnArray as $singleMatch) {
            $lane = $singleMatch[0]["lane"];
            for($i = 1; $i < 6; $i++){
                $champion = $singleMatch[$i]["champion"];
                if (!isset($champArray[$champion])) { // Initialize champion entry if not exists
                    $champArray[$champion] = ["win" => 0, "lose" => 0, "count" => 0, "winrate" => 0];
                }
                if($lane == $singleMatch[$i]["lane"] && !$singleMatch[$i]["win"]){
                    $champArray[$champion]["win"]++;
                }else if($lane == $singleMatch[$i]["lane"] && $singleMatch[$i]["win"]){
                    $champArray[$champion]["lose"]++;
                }
                if($lane == $singleMatch[$i]["lane"]){
                    $champArray[$champion]["count"] = $champArray[$champion]["win"] + $champArray[$champion]["lose"];
                    $champArray[$champion]["winrate"] = ($champArray[$champion]["count"] > 0) ? ($champArray[$champion]["win"] / $champArray[$champion]["count"]) * 100 : 0;
                    asort($champArray[$champion]);
                }
            }
        }

        

    // Get Wins, Loses, Count and Winrate in general sorted in $champArray
    } else if ($variant == "general"){
        foreach ($returnArray as $singleMatch) {
            for($i = 1; $i < 6; $i++){
                $champion = $singleMatch[$i]["champion"];
                if (!isset($champArray[$champion])) { // Initialize champion entry if not exists
                    $champArray[$champion] = ["win" => 0, "lose" => 0, "count" => 0, "winrate" => 0];
                }

                if(!$singleMatch[$i]["win"]){
                    $champArray[$champion]["win"]++;
                }else if($singleMatch[$i]["win"]){
                    $champArray[$champion]["lose"]++;
                }
                $champArray[$champion]["count"] = $champArray[$champion]["win"] + $champArray[$champion]["lose"];
                $champArray[$champion]["winrate"] = ($champArray[$champion]["count"] > 0) ? ($champArray[$champion]["win"] / $champArray[$champion]["count"]) * 100 : 0;
                asort($champArray[$champion]);
            }
        }
    }


    // Sort descending, from highest to lowest if first element should be of type "highestWinrate"
    if($type == "highestWinrate"){
        uasort($champArray, function($a, $b){
            if ($a['winrate'] != $b['winrate']) {
                return $b['winrate'] <=> $a['winrate'];
            }
            if ($a['count'] != $b['count']) {
                return $b['count'] <=> $a['count'];
            }
            return 0;
        });
    // Sort ascending, from lowest to highest if first element should be of type "mostLosses"
    } else if($type == "mostLosses"){
        uasort($champArray, function($a, $b){
            if ($a['winrate'] != $b['winrate']) {
                return $a['winrate'] <=> $b['winrate'];
            }
            if ($a['count'] != $b['count']) {
                return $b['count'] <=> $a['count'];
            }
            return 0;
        });
    }

    // Generate $maxCountArray with each champions occurence counts and sort descending
    foreach($champArray as $championname => $champion){
        $maxCountArray[$championname] = $champion["count"];
    }
    arsort($maxCountArray);
    $maxCount = floor(reset($maxCountArray)/2); // $maxCount Halve of first element in array

    // Remove unnecessary elements with too low counts
    foreach($champArray as $key => $champion){
        if(!($champion["count"] >= $maxCount)){
            unset($champArray[$key]);
        }
    }
    return $champArray;
}

/** Gets the 5 most-played-with summoners
 * This function temp stores every summoner you played with in your team, sorts them and counts their occurences
 *
 * @param array $matchDataArray Input array of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $puuid The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * @var array $mostPlayedArray The returnvalue array but not printed
 *
 * Returnvalue:
 * @return array $returnArray Containing the players and counts of matches played with descending
 */
function mostPlayedWith($matchDataArray, $puuid){
    $mostPlayedArray = array();
    $returnArray = array();

    // Store all values into separate array corresponding to each attribute
    foreach ($matchDataArray as $matchData) {
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid != $puuid){
                $mostPlayedArray[] = $matchData->info->participants[$i]->puuid;
            }
        }
    }

    // Count, Sort & Slice to 5 to retrieve printable data
    $temp = array_count_values($mostPlayedArray);
    arsort($temp);
    
    $returnArray = [];
    
    foreach ($temp as $player => $count) {
        if ($count > 2) {
            $returnArray[$player] = $count;
        }
    }

    return $returnArray;
}

/** Prints the champion and info a given player by $puuid has the highest winrate with
 * This function is only printing collected values, also possible to shove into profile.php
 *
 * @param string $lane Either "TOP", "JUNGLE", "MID", "BOT" or "UTILITY", but also "FILL" (all lanes) possible
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $puuid The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * @var array $highestWinrateArray Returnarray which is printed, it contains the final data
 *
 * Returnvalue:
 * @return void N/A, just printing values to page
 */
function getHighestWinrateWith($lane, $matchDataArray, $puuid){
    $highestWinrateArray = array();

    // Resetting $count and $winrate each iteration and saving a champions "Wins", "Loses", total matches "Count" and "Winrate" in $highestWinrateArray[championname]
    foreach ($matchDataArray as $matchData) {
        unset($count, $winrate);
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid){
                if($matchData->info->participants[$i]->teamPosition != ""){
                    $myLane = $matchData->info->participants[$i]->teamPosition;
                    // @codeCoverageIgnoreStart
                } else if ($matchData->info->participants[$i]->individualPosition != "" && $matchData->info->participants[$i]->individualPosition != "Invalid"){
                    $myLane = $matchData->info->participants[$i]->individualPosition;
                } else {
                    $myLane = "N/A";
                    // @codeCoverageIgnoreEnd
                }

                $champion = $matchData->info->participants[$i]->championName;

                if (!isset($highestWinrateArray[$champion])) {
                    $highestWinrateArray[$champion] = ["win" => 0, "lose" => 0, "count" => 0, "winrate" => 0];
                }

                if($matchData->info->participants[$i]->win){
                    $highestWinrateArray[$champion]["win"]++;
                } else {
                    $highestWinrateArray[$champion]["lose"]++;
                }
                $count = $highestWinrateArray[$champion]["win"]+$highestWinrateArray[$champion]["lose"];
                $winrate = ($highestWinrateArray[$champion]["win"]/$count)*100;
                if($lane == "FILL" || $lane == $myLane){
                    $highestWinrateArray[$champion]["lane"] = $myLane;
                    $highestWinrateArray[$champion]["count"] = $count;
                    $highestWinrateArray[$champion]["winrate"] = $winrate;
                }
                break;
            }
        }
    }

    // Generate $maxCountArray with each champions occurence counts and sort descending
    foreach($highestWinrateArray as $championname => $champion){
        $maxCountArray[$championname] = $champion["count"];
    }
    arsort($maxCountArray);
    $maxCount = floor(reset($maxCountArray)/2); // $maxCount Halve of first element in array

    // Sort descending, from highest to lowest if first element should be of type "highestWinrate"
    uasort($highestWinrateArray, function($a, $b){
        return $b['winrate'] <=> $a['winrate'];
    });

    // Remove unnecessary elements with too low counts
    foreach($highestWinrateArray as $championname => $champion){
        if(!($champion["count"] >= $maxCount)){
            unset($highestWinrateArray[$championname]);
        }
    }

    return $highestWinrateArray;
}

/** Game Ranking Function to identify the places 1-10 in a match
 * This function returns an array of 2-decimal numbers (e.g. 8,74) which arrange from best player with highest score to worst one with lowest
 *
 * @param array $matchIDArray An array containing all matchIDs of a player
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $sumid The summoners SumID necessary to evaluate the player specific data
 * @var array $rankingAttributeArray An array containing the names of the attriutes we use to check on for the final matchscore
 * @var int $maxRankScore The matchscore calculated for each match separately
 *
 * Returnvalue:
 * @return array $returnArray Contains the combination of matchID and matchScore for a given player
 */
function getMatchRanking($matchIDArray, $matchData, $sumid){
    global $rankingAttributeArray;
    $returnArray = array();
    $reasonArray = array();
    $cleanNameArray = array("Kills","Deaths","Assists","KDA","KillParticipation","CS","Gold","VisionScore","WardTakedowns","WardsPlaced","WardsGuarded","VisionWards","Consumables","TurretPlates","TotalTakedowns","TurretTakedowns",
    "InhibitorTakedowns","DragonTakedowns","HeraldTakedowns","DamageToBuildings","DamageToObjectives","DamageMitigated","DamageDealtToChampions","DamageTaken","TeamShielded","TeamHealed","TimeCC","DeathTime","SkillshotsDodged","SkillshotsHit");
    // $matchIDArray = array_slice($matchIDArray, 0, 15);
    foreach ($matchIDArray as $matchID) {
        $maxRankScore = 0;
        unset($mainArray);
        unset($reasonArray);
        //going through all matches to save all data in array per sumid
        if(isset($matchData[$matchID])){ // Necessary check to secure that we have the matchdata of a matchid
            if($matchData[$matchID]->info->gameDuration > 600){
                foreach ($matchData[$matchID]->info as $player) {
                    for ($i = 0; $i < 10; $i++){
                        if (isset($player[$i]->summonerId)) { // Necessary to loop over every player to get comparable results
                            // echo $i."<br>";
                            // Ternary Operator == if(isset(playerStat)) then set "Attribute" to the playerStat else set the "Attribute" to 0
                            isset($player[$i]->kills) ? $mainArray[$player[$i]->summonerId]["Kills"] = $player[$i]->kills : $mainArray[$player[$i]->summonerId]["Kills"] = 0;
                            isset($player[$i]->deaths) ? $mainArray[$player[$i]->summonerId]["Deaths"] = $player[$i]->deaths : $mainArray[$player[$i]->summonerId]["Deaths"] = 0;
                            isset($player[$i]->assists) ? $mainArray[$player[$i]->summonerId]["Assists"] = $player[$i]->assists : $mainArray[$player[$i]->summonerId]["Assists"] = 0;
                            isset($player[$i]->challenges->kda) ? $mainArray[$player[$i]->summonerId]["KDA"] = $player[$i]->challenges->kda : $mainArray[$player[$i]->summonerId]["KDA"] = 0;
                            isset($player[$i]->challenges->killParticipation) ? $mainArray[$player[$i]->summonerId]["KillParticipation"] = $player[$i]->challenges->killParticipation : $mainArray[$player[$i]->summonerId]["KillParticipation"] = 0;
                            isset($player[$i]->totalMinionsKilled) ? $mainArray[$player[$i]->summonerId]["CS"] = $player[$i]->totalMinionsKilled+$player[$i]->neutralMinionsKilled : $mainArray[$player[$i]->summonerId]["CS"] = 0;
                            isset($player[$i]->goldEarned) ? $mainArray[$player[$i]->summonerId]["Gold"] = $player[$i]->goldEarned : $mainArray[$player[$i]->summonerId]["Gold"] = 0;
                            isset($player[$i]->visionScore) ? $mainArray[$player[$i]->summonerId]["VisionScore"] = $player[$i]->visionScore : $mainArray[$player[$i]->summonerId]["VisionScore"] = 0;
                            isset($player[$i]->challenges->wardTakedowns) ? $mainArray[$player[$i]->summonerId]["WardTakedowns"] = $player[$i]->challenges->wardTakedowns : $mainArray[$player[$i]->summonerId]["WardTakedowns"] = 0;
                            isset($player[$i]->wardsPlaced) ? $mainArray[$player[$i]->summonerId]["WardsPlaced"] = $player[$i]->wardsPlaced : $mainArray[$player[$i]->summonerId]["WardsPlaced"] = 0;
                            isset($player[$i]->challenges->wardsGuarded) ? $mainArray[$player[$i]->summonerId]["WardsGuarded"] = $player[$i]->challenges->wardsGuarded : $mainArray[$player[$i]->summonerId]["WardsGuarded"] = 0;
                            isset($player[$i]->detectorWardsPlaced) ? $mainArray[$player[$i]->summonerId]["VisionWards"] = $player[$i]->detectorWardsPlaced : $mainArray[$player[$i]->summonerId]["VisionWards"] = 0;
                            isset($player[$i]->consumablesPurchased) ? $mainArray[$player[$i]->summonerId]["Consumables"] = $player[$i]->consumablesPurchased : $mainArray[$player[$i]->summonerId]["Consumables"] = 0;
                            isset($player[$i]->challenges->turretPlatesTaken) ? $mainArray[$player[$i]->summonerId]["TurretPlates"] = $player[$i]->challenges->turretPlatesTaken : $mainArray[$player[$i]->summonerId]["TurretPlates"] = 0;
                            isset($player[$i]->challenges->takedowns) ? $mainArray[$player[$i]->summonerId]["TotalTakedowns"] = $player[$i]->challenges->takedowns : $mainArray[$player[$i]->summonerId]["TotalTakedowns"] = 0;
                            isset($player[$i]->turretTakedowns) ? $mainArray[$player[$i]->summonerId]["TurretTakedowns"] = $player[$i]->turretTakedowns : $mainArray[$player[$i]->summonerId]["TurretTakedowns"] = 0;
                            isset($player[$i]->inhibitorTakedowns) ? $mainArray[$player[$i]->summonerId]["InhibitorTakedowns"] = $player[$i]->inhibitorTakedowns : $mainArray[$player[$i]->summonerId]["InhibitorTakedowns"] = 0;
                            isset($player[$i]->challenges->dragonTakedowns) ? $mainArray[$player[$i]->summonerId]["DragonTakedowns"] = $player[$i]->challenges->dragonTakedowns : $mainArray[$player[$i]->summonerId]["DragonTakedowns"] = 0;
                            isset($player[$i]->challenges->riftHeraldTakedowns) ? $mainArray[$player[$i]->summonerId]["HeraldTakedowns"] = $player[$i]->challenges->riftHeraldTakedowns : $mainArray[$player[$i]->summonerId]["HeraldTakedowns"] = 0;
                            isset($player[$i]->damageDealtToBuildings) ? $mainArray[$player[$i]->summonerId]["DamageToBuildings"] = $player[$i]->damageDealtToBuildings : $mainArray[$player[$i]->summonerId]["DamageToBuildings"] = 0;
                            isset($player[$i]->damageDealtToObjectives) ? $mainArray[$player[$i]->summonerId]["DamageToObjectives"] = $player[$i]->damageDealtToObjectives : $mainArray[$player[$i]->summonerId]["DamageToObjectives"] = 0;
                            isset($player[$i]->damageSelfMitigated) ? $mainArray[$player[$i]->summonerId]["DamageMitigated"] = $player[$i]->damageSelfMitigated : $mainArray[$player[$i]->summonerId]["DamageMitigated"] = 0;
                            isset($player[$i]->totalDamageDealtToChampions) ? $mainArray[$player[$i]->summonerId]["DamageDealtToChampions"] = $player[$i]->totalDamageDealtToChampions : $mainArray[$player[$i]->summonerId]["DamageDealtToChampions"] = 0;
                            isset($player[$i]->totalDamageTaken) ? $mainArray[$player[$i]->summonerId]["DamageTaken"] = $player[$i]->totalDamageTaken : $mainArray[$player[$i]->summonerId]["DamageTaken"] = 0;
                            isset($player[$i]->totalDamageShieldedOnTeammates) ? $mainArray[$player[$i]->summonerId]["TeamShielded"] = $player[$i]->totalDamageShieldedOnTeammates : $mainArray[$player[$i]->summonerId]["TeamShielded"] = 0;
                            isset($player[$i]->totalHealsOnTeammates) ? $mainArray[$player[$i]->summonerId]["TeamHealed"] = $player[$i]->totalHealsOnTeammates : $mainArray[$player[$i]->summonerId]["TeamHealed"] = 0;
                            isset($player[$i]->totalTimeCCDealt) ? $mainArray[$player[$i]->summonerId]["TimeCC"] = $player[$i]->totalTimeCCDealt : $mainArray[$player[$i]->summonerId]["TimeCC"] = 0;
                            isset($player[$i]->totalTimeSpentDead) ? $mainArray[$player[$i]->summonerId]["DeathTime"] = $player[$i]->totalTimeSpentDead : $mainArray[$player[$i]->summonerId]["DeathTime"] = 0;
                            isset($player[$i]->challenges->skillshotsDodged) ? $mainArray[$player[$i]->summonerId]["SkillshotsDodged"] = $player[$i]->challenges->skillshotsDodged : $mainArray[$player[$i]->summonerId]["SkillshotsDodged"] = 0;
                            isset($player[$i]->challenges->skillshotsHit) ? $mainArray[$player[$i]->summonerId]["SkillshotsHit"] = $player[$i]->challenges->skillshotsHit : $mainArray[$player[$i]->summonerId]["SkillshotsHit"] = 0;
                            if($player[$i]->summonerId == $sumid){
                                $reasonArray[$matchID]["Sumid"] = $sumid;
                                foreach($cleanNameArray as $attributeName){
                                    $reasonArray[$matchID][$attributeName]["Value"] = $mainArray[$player[$i]->summonerId][$attributeName];
                                }
                            }
                        }
                    }
                }
                // print "<pre>";print_r($mainArray);print "</pre>";
                // echo mb_strlen(serialize((array)$mainArray), '8bit');
                foreach ($rankingAttributeArray as $attribute){

                    foreach ($mainArray as $key => $playersumid) {

                        $tempArray[] = array (
                            "SumID" => $key,
                            $attribute => $playersumid[$attribute],
                        );
                    }
                    if ($attribute == "Deaths" || $attribute == "DeathTime") {
                        usort($tempArray, function($a, $b) use($attribute){
                            return $b[$attribute] <=> $a[$attribute];
                        });
                    } else if (in_array($attribute, $rankingAttributeArray)){
                        usort($tempArray, function($a, $b) use($attribute){
                            return $a[$attribute] <=> $b[$attribute];
                        });
                    }

                    // print_r($tempArray);

                    foreach($tempArray as $rank => $value){
                        if ($value["SumID"] == $sumid){
                            switch ($attribute){
                                case "Kills":
                                    $maxRankScore += (($rank+1)*7);
                                    $reasonArray[$matchID]["Kills"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["Kills"]["Points"] = ($rank+1)*7;
                                    break;
                                case "Deaths":
                                    $maxRankScore += (($rank+1)*10);
                                    $reasonArray[$matchID]["Deaths"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["Deaths"]["Points"] = ($rank+1)*10;
                                    break;
                                case "Assists":
                                    $maxRankScore += (($rank+1)*7);
                                    $reasonArray[$matchID]["Assists"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["Assists"]["Points"] = ($rank+1)*7;
                                    break;
                                case "KDA":
                                    $maxRankScore += (($rank+1)*20);
                                    $reasonArray[$matchID]["KDA"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["KDA"]["Points"] = ($rank+1)*20;
                                    break;
                                case "CS":
                                    $maxRankScore += (($rank+1)*5);
                                    $reasonArray[$matchID]["CS"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["CS"]["Points"] = ($rank+1)*5;
                                    break;
                                case "Gold":
                                    $maxRankScore += (($rank+1)*6);
                                    $reasonArray[$matchID]["Gold"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["Gold"]["Points"] = ($rank+1)*6;
                                    break;
                                case "VisionScore":
                                    $maxRankScore += (($rank+1)*20);
                                    $reasonArray[$matchID]["VisionScore"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["VisionScore"]["Points"] = ($rank+1)*20;
                                    break;
                                case "WardTakedowns":
                                    $maxRankScore += (($rank+1)*4);
                                    $reasonArray[$matchID]["WardTakedowns"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["WardTakedowns"]["Points"] = ($rank+1)*4;
                                    break;
                                case "WardsPlaced":
                                    $maxRankScore += (($rank+1)*2);
                                    $reasonArray[$matchID]["WardsPlaced"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["WardsPlaced"]["Points"] = ($rank+1)*2;
                                    break;
                                case "WardsGuarded":
                                    $maxRankScore += (($rank+1)*4);
                                    $reasonArray[$matchID]["WardsGuarded"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["WardsGuarded"]["Points"] = ($rank+1)*4;
                                    break;
                                case "VisionWards":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["VisionWards"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["VisionWards"]["Points"] = ($rank+1)*8;
                                    break;
                                case "Consumables":
                                    $maxRankScore += (($rank+1)*1);
                                    $reasonArray[$matchID]["Consumables"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["Consumables"]["Points"] = ($rank+1)*1;
                                    break;
                                case "TurretPlates":
                                    $maxRankScore += (($rank+1)*5);
                                    $reasonArray[$matchID]["TurretPlates"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["TurretPlates"]["Points"] = ($rank+1)*5;
                                    break;
                                case "TotalTakedowns":
                                    $maxRankScore += (($rank+1)*20);
                                    $reasonArray[$matchID]["TotalTakedowns"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["TotalTakedowns"]["Points"] = ($rank+1)*20;
                                    break;
                                case "TurretTakedowns":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["TurretTakedowns"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["TurretTakedowns"]["Points"] = ($rank+1)*8;
                                    break;
                                case "InhibitorTakedowns":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["InhibitorTakedowns"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["InhibitorTakedowns"]["Points"] = ($rank+1)*8;
                                    break;
                                case "DragonTakedowns":
                                    $maxRankScore += (($rank+1)*7);
                                    $reasonArray[$matchID]["DragonTakedowns"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DragonTakedowns"]["Points"] = ($rank+1)*7;
                                    break;
                                case "HeraldTakedowns":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["HeraldTakedowns"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["HeraldTakedowns"]["Points"] = ($rank+1)*8;
                                    break;
                                case "DamageToBuildings":
                                    $maxRankScore += (($rank+1)*3);
                                    $reasonArray[$matchID]["DamageToBuildings"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DamageToBuildings"]["Points"] = ($rank+1)*3;
                                    break;
                                case "DamageToObjectives":
                                    $maxRankScore += (($rank+1)*4);
                                    $reasonArray[$matchID]["DamageToObjectives"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DamageToObjectives"]["Points"] = ($rank+1)*4;
                                    break;
                                case "DamageMitigated":
                                    $maxRankScore += (($rank+1)*3);
                                    $reasonArray[$matchID]["DamageMitigated"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DamageMitigated"]["Points"] = ($rank+1)*3;
                                    break;
                                case "DamageDealtToChampions":
                                    $maxRankScore += (($rank+1)*15);
                                    $reasonArray[$matchID]["DamageDealtToChampions"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DamageDealtToChampions"]["Points"] = ($rank+1)*15;
                                    break;
                                case "DamageTaken":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["DamageTaken"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DamageTaken"]["Points"] = ($rank+1)*8;
                                    break;
                                case "TeamShielded":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["TeamShielded"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["TeamShielded"]["Points"] = ($rank+1)*8;
                                    break;
                                case "TeamHealed":
                                    $maxRankScore += (($rank+1)*7);
                                    $reasonArray[$matchID]["TeamHealed"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["TeamHealed"]["Points"] = ($rank+1)*7;
                                    break;
                                case "TimeCC":
                                    $maxRankScore += (($rank+1)*8);
                                    $reasonArray[$matchID]["TimeCC"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["TimeCC"]["Points"] = ($rank+1)*5;
                                    break;
                                case "DeathTime":
                                    $maxRankScore += (($rank+1)*20);
                                    $reasonArray[$matchID]["DeathTime"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["DeathTime"]["Points"] = ($rank+1)*20;
                                    break;
                                case "SkillshotsDodged":
                                    $maxRankScore += (($rank+1)*20);
                                    $reasonArray[$matchID]["SkillshotsDodged"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["SkillshotsDodged"]["Points"] = ($rank+1)*20;
                                    break;
                                case "SkillshotsHit":
                                    $maxRankScore += (($rank+1)*1);
                                    $reasonArray[$matchID]["SkillshotsHit"]["Rank"] = 10-$rank;
                                    $reasonArray[$matchID]["SkillshotsHit"]["Points"] = ($rank+1)*1;
                                    break;
                            }
                        }
                    }
                    unset($tempArray);
                }
                $returnArray[$matchID] = number_format(($maxRankScore/247), 2);
                // $returnArray["Reasons"][$matchID] = $reasonArray[$matchID];
            } else {
                // @codeCoverageIgnoreStart
                $returnArray[$matchID] = "N/A";
                // @codeCoverageIgnoreEnd
            }
        }
    }
    return $returnArray;
}

function unique_multidim_array($array, $key) {
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach($array as $val) {
        if (!in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}


/** Printing function to display the champion selector
 *
 * @var array $championNamingFile|$championNamingData This array contains all necessary champion data of the current patches grabbed from the champion.json of datadragon
 * @var string $imgPath The path of a icon image retrieved from the champion.json
 * @var string $dataId The ID of a specific champion retrieved from the champion.json
 *
 * Returnvalue:
 * @return void $teamDataArray with keys "TeamID", "TournamentID", "Name", "Tag", "Icon", "Tier", "Captain" and the array itself of "Players"
 * 
 * @codeCoverageIgnore
 */
function showBanSelector(){
    global $currentPatch;
    $i=0;
    $championNamingData = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
    $championNamingFile = json_decode($championNamingData);
    foreach($championNamingFile->data as $champData){
        $champName = $champData->name;
        $i++;
        $imgPath = substr($champData->image->full, 0, -4).".avif";
        $dataId = $champData->id;
        if($i<15){
            if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath)){
                echo "<div class='align-top inline-block text-center h-18 fullhd:w-[4.25rem] twok:w-[4.75rem] champ-select-champion' style='content-visibility: auto;'>";
                    echo '<div class="ban-hoverer inline-grid group" onclick="addToFile(this.parentElement);">';
                        echo '<img loading="lazy" width="56" height="56" class="min-h-8 champ-select-icon twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11" data-id="' . $dataId . '" data-abbr="' . abbreviationFetcher($champName) . '" src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'').'"
                        alt="A league of legends champion icon of '.$imgPath.'">';
                        echo '<img loading="lazy" width="56" height="56" class="min-h-8 ban-overlay twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/icon-ban.avif?version='.md5_file('/hdd1/clashapp/data/misc/icon-ban.avif').'" alt="Prohibition overlay icon in grey">';
                        echo '<img loading="lazy" width="56" height="56" class="min-h-8 ban-overlay-red twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-active:opacity-100" draggable="false" src="/clashapp/data/misc/icon-ban-red.avif?version='.md5_file('/hdd1/clashapp/data/misc/icon-ban-red.avif').'" alt="Prohibition overlay icon in red"></div>';
                    echo "<span class='caption text-ellipsis overflow-hidden whitespace-nowrap block'>".$champName."</span>";
            echo "</div>";
            }
        } else {
            if(fileExistsWithCache('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath)){
                echo "<div class='align-top inline-block text-center h-18 fullhd:w-[4.25rem] twok:w-[4.75rem] champ-select-champion' style='content-visibility: auto;'>";
                    echo '<div class="ban-hoverer inline-grid group" onclick="addToFile(this.parentElement);">';
                        echo '<img loading="lazy" width="56" height="56" class="min-h-8 champ-select-icon twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11" data-id="' . $dataId . '" data-abbr="' . abbreviationFetcher($champName) . '" src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'?version='.md5_file('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'').'"
                        alt="A league of legends champion icon of '.$imgPath.'" loading="lazy">';
                        echo '<img loading="lazy" width="56" height="56" class="min-h-8 ban-overlay twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/icon-ban.avif?version='.md5_file('/hdd1/clashapp/data/misc/icon-ban.avif').'" loading="lazy" alt="Prohibition overlay icon in grey">';
                        echo '<img loading="lazy" width="56" height="56" class="min-h-8 ban-overlay-red twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-active:opacity-100" draggable="false" src="/clashapp/data/misc/icon-ban-red.avif?version='.md5_file('/hdd1/clashapp/data/misc/icon-ban-red.avif').'" loading="lazy" alt="Prohibition overlay icon in red"></div>';
                    echo "<span class='caption text-ellipsis overflow-hidden whitespace-nowrap block'>".$champName."</span>";
            echo "</div>";
            }
        }
    }
    return;
}

/** This function collects the JSON formatted data in the abbreviations.json and maps every champion to it's own abbreviations. To make the .json more readable it is allowed
 * to add spaces there, although they are filtered out below, so the javascript later on can easily split the string by the "," separator. The abbreviations are used as possible
 * alternative searchterms for a champion in a form field, in this case the #champSelector. If they are supposed to match parts of words and not only the whole word all possible
 * search terms have to be written into the abbreviations.json (like -> "abbr": "frel, frelj, freljo, freljor, freljord").
 *
 * @param string $champName The provided name of a champion, NOT the ID and has to be exactly written both as param here aswell as in the abbreviations.json
 * @var array $abbrArray This array contains the decoded (as object) contents of the abbreviations.json
 *
 * Returnvalue:
 * @return string $abbreviations is the return string that will get split by "," separator and added into the data-abbr attribute in the html code above
 */
function abbreviationFetcher($champName){
    $abbreviations = [];
    $abbrArray = json_decode(file_get_contents('/hdd1/clashapp/data/misc/abbreviations.json'));
    if (isset($abbrArray->{$champName})) {
        $abbreviations = $abbrArray->{$champName}->abbr;
    }
    $abbreviationString = implode(',', $abbreviations);
    return $abbreviationString;
}

function timeDiffToText($timestamp){
    switch ($timestamp){
        case $timestamp < strtotime("-1 year"): // Über ein Jahr her
            return __("over a year ago");
        case $timestamp < strtotime("-6 months"): // Über 6 Monate unter 1 Jahr
            return __("over 6 months ago");
        case $timestamp < strtotime("-3 months"): // Über 3 Monate unter 6 Monate
            return __("over 3 months ago");
        case $timestamp < strtotime("-1 months"): // Über einen Monat unter 3 Monate
            return __("over a month ago");
        case $timestamp < strtotime("-2 weeks"): // Über zwei Wochen unter 1 Monat
            return __("over two weeks ago");
        case $timestamp > strtotime("-2 weeks"): // Unter zwei Wochen her
            return __("under two weeks ago");
    }
}

/** Function that generates the teams win, lose and winrate stats, recommended picks against aswell as discommended picks against them
 *
 * @param array $sumidArray This array contains all 5 summonerIDs of each team member, which is later used to identifiy if one of them played and won/lost a game
 * @param array $matchIDArray This array contains all matchIDs of all 5 clash team members without duplicates
 * @param array $matchData The compacted matchData of all IDs from the $matchIDArray, used for performance reasons (see getMatchData())
 * @var array $tempArray Temporary array used for array combination processes
 * @var array $sortArray Temporary sort array used for sorting by "Matchscore" values
 * @var int $counter The incrementing counter for every match the given team lost
 * @var int $counter2 The incrementing counter for every match the given team won
 *
 * Returnvalue:
 * @return array $returnArray Contains all info about the teams stats (wins, loses & WR) aswell as 20 recommended and 20 discommended picks
 */
function getSuggestedPicksAndTeamstats($sumidArray, $matchIDArray, $matchData){
    $matchscoreArray = array();
    $returnArray = array();
    $tempArray = array();
    $sortArray = array();
    $counter=0;
    $counter2=0;

    foreach($matchData as $matchID => $inhalt){
        foreach($inhalt->info->participants as $player){
            if(in_array($player->summonerId, $sumidArray) && $player->win == true){
                $teamId = $player->teamId;
            } else if(in_array($player->summonerId, $sumidArray) && $player->win == false) {
                $teamId = $player->teamId;
                foreach($inhalt->info->participants as $enemy){
                    if($enemy->teamId != $teamId){ // Select only enemy team
                        $tempArray[$enemy->summonerId]["Champion"] = $enemy->championName;
                        $tempArray[$enemy->summonerId]["Matchscore"] = implode("",getMatchRanking(array($matchID), $matchData, $enemy->summonerId));
                    }
                }
                $matchscoreArray[$matchID] = $tempArray;
                unset($tempArray);
            }
        }
        foreach($inhalt->info->participants as $test){
            if(in_array($test->summonerId, $sumidArray) && $test->win == false) {
                $counter++; // Team has lost a game
                break;
            } else if(in_array($test->summonerId, $sumidArray) && $test->win == true){
                $counter2++; // Team has won a game
                break;
            }
        }
    }
    foreach($matchscoreArray as $singleMatch){
        $sortArray += $singleMatch;
    }

    usort($sortArray, function($a, $b){
        return $b["Matchscore"] <=> $a["Matchscore"];
    });

    foreach($sortArray as $key1 => $values1){
        foreach($sortArray as $key2 => $values2){
            if($values1["Champion"] == $values2["Champion"]){
                $sortArray[$key1]["Champion"] = $values1["Champion"];
                $sortArray[$key1]["Matchscore"] = round((($values1["Matchscore"]*100+$values2["Matchscore"]*100)/2)/100, 2);
                unset($sortArray[$key2]);
            }
        }
    }

    usort($sortArray, function($a, $b){
        return $b["Matchscore"] <=> $a["Matchscore"];
    });

    $returnArray["Teamstats"]["Wins"] = $counter2;
    $returnArray["Teamstats"]["Losses"] = $counter;
    $returnArray["Teamstats"]["Winrate"] = number_format(($counter2/($counter+$counter2))*100, 2, '.', ' ');

    $weakAgainstArray = array_slice($sortArray, 0, 20); // Recommended Picks - Team is weak against those champions

    $strongAgainstArray = array_slice($sortArray, count($sortArray)-20, count($sortArray)); // Discommended Picks - Team is strong against those champions

    usort($strongAgainstArray, function($a, $b){
        return $a["Matchscore"] <=> $b["Matchscore"];
    });

    $returnArray["TeamIsWeakAgainst"] = $weakAgainstArray;
    $returnArray["TeamIsStrongAgainst"] = $strongAgainstArray;

    return $returnArray;
}

/** Function that generates the teams win, lose and winrate stats, recommended picks against aswell as discommended picks against them
 *
 * @param array $sumidArray This array contains all 5 summonerIDs of each team member, which is later used to identifiy if one of them played and won/lost a game
 * @param array $matchIDArray This array contains all matchIDs of all 5 clash team members without duplicates
 * @param array $matchData The compacted matchData of all IDs from the $matchIDArray, used for performance reasons (see getMatchData())
 * @var array $tempArray Temporary array used for array combination processes
 * @var array $sortedMasteryArray Temporary sort array used for sorting by "Matchscore" values
 * @var int $counter The incrementing counter for every match the given team lost
 * @var int $counter2 The incrementing counter for every match the given team won
 *
 * Returnvalue:
 * @return array $returnArray Contains all info about the teams stats (wins, loses & WR) aswell as 20 recommended and 20 discommended picks
 */
function getSuggestedBans($sumidArray, $masterDataArray, $playerLanesTeamArray, $matchIDArray, $matchData){
    $sortedMasteryArray = array();
    $countArray = array();
    $returnAndExplainArray = array();
    $banExplainArray = array();

    // Merge single player masteries together to create combined team mastery data array
    foreach($masterDataArray as $singleMasteryData){
        $sortedMasteryArray = array_merge($sortedMasteryArray, $singleMasteryData);
    }

    // Sort this combined team mastery data array
    usort($sortedMasteryArray, function($a, $b){
        $a["Points"] = str_replace(',', '', $a["Points"]);
        $b["Points"] = str_replace(',', '', $b["Points"]);
        return $b["Points"] <=> $a["Points"];
    });

    // Remove any duplicates and always choose the highest one (select combined data and remove single data from array)
    foreach($sortedMasteryArray as $key1 => $champData1){ // Total Teampoints = Grouped mastery points of specific champion for the whole team e.g. two people play one champion -> mastery scores combined for that champ
        $sortedMasteryArray[$key1]["TotalTeamPoints"] = 0;
        $banExplainArray[$champData1["Champion"]]["TotalTeamPoints"]["Value"] = 0;
        foreach($sortedMasteryArray as $key2 => $champData2){
            if(($champData1 != $champData2) && ($champData1["Champion"] == $champData2["Champion"])){
                $sortedMasteryArray[$key1]["TotalTeamPoints"] += str_replace(',', '', $champData2["Points"]);
                $banExplainArray[$champData1["Champion"]]["TotalTeamPoints"]["Value"] += str_replace(',', '', $champData2["Points"]);
            }
        }
    }

    // Delete unnecessary information from remaining array
    foreach(array_keys($sortedMasteryArray) as $championData){
        unset($sortedMasteryArray[$championData]["Lvl"]);
        unset($sortedMasteryArray[$championData]["LvlUpTokens"]);
        $sortedMasteryArray[$championData]["MatchingLanersPrio"] = 0;
    }
    // print_r($sortedMasteryArray); // This is now the sorted team mastery data array

    // Count how many people play a champion by adding their sumid if they have at least 20k mastery points on a champ (eq. to average understanding and not just played once)
    foreach($masterDataArray as $sumid => $playersMasteryData){
        foreach($playersMasteryData as $data){
            $points = str_replace(',', '', $data["Points"]);
            if($points >= 20000){
                $countArray[$data["Champion"]][] = $sumid;
            }
        }
    }

    // Remove all if a champ only got played by one person -> useless info
    foreach($countArray as $champion => $players){
        if(count($players)<2){
            unset($countArray[$champion]);
        }
    }

    // Sort the array of how many people played what champion from 5per champ highest to 2per champ lowest
    uasort($countArray, function($a, $b){
        return count($b) <=> count($a);
    });
    // print_r($countArray); // This is now an array of total player capable to play a champion

    foreach($countArray as $champion => $players){ // For every champion that is played by more than 2 people with each more than 20k mastery
        foreach($players as $comparePlayer1){ // Take comparePlayer1
            foreach($players as $comparePlayer2){ // And comparePlayer 2
                if($comparePlayer1 != $comparePlayer2){ // If those two are two different people
                    if($playerLanesTeamArray[$comparePlayer1]["Mainrole"] != "UNKNOWN" && $playerLanesTeamArray[$comparePlayer1]["Mainrole"] != ""){ // And if comparePlayer1's lanes are known
                        if(($playerLanesTeamArray[$comparePlayer1]["Mainrole"] == $playerLanesTeamArray[$comparePlayer2]["Mainrole"]) || ($playerLanesTeamArray[$comparePlayer1]["Mainrole"] == $playerLanesTeamArray[$comparePlayer2]["Secrole"])){
                            // If the mainrole of Player1 is the same of Player2 or the same as Player2s Secondary, e.g. Player1 (JGL|MID) Player2 (MID) -> true
                            if($playerLanesTeamArray[$comparePlayer1]["Mainrole"] == "FILL"){
                                // @codeCoverageIgnoreStart
                                foreach($sortedMasteryArray as $key => $championData){
                                    if($championData["Champion"] == $champion){
                                        $sortedMasteryArray[$key]["MatchingLanersPrio"] += 0.5;
                                        if(!isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])) $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"] = []; // initialize "Cause"
                                        if(!in_array($comparePlayer1, $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])){
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"][] = $comparePlayer1;
                                        }
                                        if(isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                            if(!in_array("FILL", $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                                $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = "FILL";
                                            }
                                        } else {
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = "FILL";
                                        }
                                        break;
                                        // @codeCoverageIgnoreEnd
                                    }
                                }
                                // echo "Low Prio Match found: M-".$playerLanesTeamArray[$comparePlayer1]["Mainrole"]." to M-".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/S-".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                            } else {
                                foreach($sortedMasteryArray as $key => $championData){
                                    if($championData["Champion"] == $champion){
                                        $sortedMasteryArray[$key]["MatchingLanersPrio"]++;
                                        if(!isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])) $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"] = []; // initialize "Cause"
                                        if(!in_array($comparePlayer1, $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])){
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"][] = $comparePlayer1;
                                        }
                                        if(isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                            if(!in_array($playerLanesTeamArray[$comparePlayer1]["Mainrole"], $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                                // @codeCoverageIgnoreStart
                                                $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = $playerLanesTeamArray[$comparePlayer1]["Mainrole"];
                                                // @codeCoverageIgnoreEnd
                                            }
                                        } else {
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = $playerLanesTeamArray[$comparePlayer1]["Mainrole"];
                                        }
                                        break;
                                    }
                                }
                                // echo "High Prio Match found: M-".$playerLanesTeamArray[$comparePlayer1]["Mainrole"]." to M-".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/S-".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                            }
                        }
                    }
                    if($playerLanesTeamArray[$comparePlayer1]["Secrole"] != "UNKNOWN" && $playerLanesTeamArray[$comparePlayer1]["Secrole"] != ""){
                        if(($playerLanesTeamArray[$comparePlayer1]["Secrole"] == $playerLanesTeamArray[$comparePlayer2]["Mainrole"]) || ($playerLanesTeamArray[$comparePlayer1]["Secrole"] == $playerLanesTeamArray[$comparePlayer2]["Secrole"])){
                            // @codeCoverageIgnoreStart
                            if($playerLanesTeamArray[$comparePlayer1]["Secrole"] == "FILL"){
                                foreach($sortedMasteryArray as $key => $championData){
                                    if($championData["Champion"] == $champion){
                                        $sortedMasteryArray[$key]["MatchingLanersPrio"] += 0.5;
                                        if(!isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])) $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"] = []; // initialize "Cause"
                                        if(!in_array($comparePlayer1, $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])){
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"][] = $comparePlayer1;
                                        }
                                        if(isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                            if(!in_array("FILL", $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                                $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = "FILL";
                                            }
                                        } else {
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = "FILL";
                                        }
                                        break;
                                    }
                                }
                                // echo "Low Prio Match found: S-".$playerLanesTeamArray[$comparePlayer1]["Secrole"]." to M-".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/S-".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                            } else {
                                foreach($sortedMasteryArray as $key => $championData){
                                    if($championData["Champion"] == $champion){
                                        $sortedMasteryArray[$key]["MatchingLanersPrio"] += 0.5;
                                        if(!isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])) $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"] = []; // initialize "Cause"
                                        if(!in_array($comparePlayer1, $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"])){
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Cause"][] = $comparePlayer1;
                                        }
                                        if(isset($banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                            if(!in_array($playerLanesTeamArray[$comparePlayer1]["Secrole"], $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"])){
                                                $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = $playerLanesTeamArray[$comparePlayer1]["Secrole"];
                                            }
                                        } else {
                                            $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = $playerLanesTeamArray[$comparePlayer1]["Secrole"];
                                        }
                                        break;
                                    }
                                }
                                // @codeCoverageIgnoreEnd
                                // echo "High Prio Match found: S-".$playerLanesTeamArray[$comparePlayer1]["Secrole"]." to M-".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/S-".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                            }
                        }
                    }
                }
            }
        }
    }
    // print_r($sortedMasteryArray); // This array now contains under "MatchingLanersPrio" how many laners are capable to play the champion on the same lane (E.g. if two players can play Kayn JGL -> Score of 2)
    /** The score also increases if the champion can be played on multiple lanes
     * TOP + JGLTOP + JGL all play Kayn                                         SUPP + MIDSUPP play Leona
     * Mainrole TOP eq. Secrole JGLTOP +1                                       Mainrole SUPP eq. Secrole MIDSUPP +1
     * Mainrole JGLTOP eq. Mainrole JGL +1                                      Secrole MIDSUPP eq. Mainrole SUPP +0.5
     * Mainrole JGL eq. Mainrole JGLTOP +1                                      ---------------------------------> 1.5
     * Secrole JGLTOP eq. Mainrole TOP +0.5
     * ---------------------------------> 3.5
     */

    // Count how many players have +20k Mastery on a champion without any matching lanes (Everyone could play some games Leona, but that doesnt mean it's as important as 2x supp mains on leona)
    $playerCountOfChampionArray = array_count_values(array_column($sortedMasteryArray, "Champion"));
    foreach($sortedMasteryArray as $key => $championData){
        foreach($playerCountOfChampionArray as $championName => $countData){
            if($championData["Champion"] == $championName){
                $sortedMasteryArray[$key]["CapablePlayers"] = $countData;
            }
        }
    }
    // print_r($sortedMasteryArray); // Array now contains information about how many players of the team can play a specific champion (not as important as when they match with their lanes as 3+ twitch players on random roles != 3+ twitch jgl)

    // Calculate the occurences of a champion in the last fetched games (E.g. Viktor played in 7 of 15 games is important information, many points on irelia too, but 0 occurences in 15 last games of that player less important)
    foreach($matchIDArray as $matchID){
        foreach($matchData[$matchID]->info->participants as $player){
            if(in_array($player->summonerId, $sumidArray)){
                foreach($sortedMasteryArray as $key => $championData){
                    if($championData["Champion"] == $player->championName){
                        if(!isset($sortedMasteryArray[$key]["OccurencesInLastGames"])) $sortedMasteryArray[$key]["OccurencesInLastGames"] = 0;
                        if(!isset($banExplainArray[$championData["Champion"]]["OccurencesInLastGames"]["Count"])) $banExplainArray[$championData["Champion"]]["OccurencesInLastGames"]["Count"] = 0;
                        $sortedMasteryArray[$key]["OccurencesInLastGames"]++;
                        $banExplainArray[$championData["Champion"]]["OccurencesInLastGames"]["Count"]++;
                        break; // Break to prevent unnecessary loops
                    }
                }
            }
        }
    }

    // This block saves all matchscores achieved per champion per match if there were occurences in the last games. E.g. Kayn was played 3 times with scores [0] => 5.23, [1] => 6.77 [2] => 4.34
    foreach($matchData as $mainKey => $inhalt){
        foreach($inhalt->info->participants as $player){
            if(in_array($player->summonerId, $sumidArray)){
                foreach($sortedMasteryArray as $key => $championData){
                    if($championData["Champion"] == $player->championName){
                        if(!isset($sortedMasteryArray[$key]["AverageMatchScore"])) $sortedMasteryArray[$key]["AverageMatchScore"] = [];
                        $sortedMasteryArray[$key]["AverageMatchScore"][] = implode("",getMatchRanking(array($mainKey), $matchData, $player->summonerId));
                        break; // Break to prevent unnecessary loops
                    }
                }
            }
        }
    }
    // Additionally this block sums the single matchscores together. E.g. from the values in the comment above -> (5.23 + 6.77 + 4.34) / 3 == 5.44
    foreach ($sortedMasteryArray as $key => $championData) {
        if (isset($sortedMasteryArray[$key]["AverageMatchScore"])) {
            $considerableScores = array_filter($sortedMasteryArray[$key]["AverageMatchScore"], function ($score) {
                return is_numeric($score); // Filter out any unwanted scores (like N/A)
            });
    
            // Check if there are numeric scores before calculating the average
            if (count($considerableScores) > 0) {
                $average = number_format(array_sum($considerableScores) / count($considerableScores), 2, ".", "");
                $sortedMasteryArray[$key]["AverageMatchScore"] = $average;
            } else {
                // If there are no numeric scores, set the average to 0
                // @codeCoverageIgnoreStart
                $sortedMasteryArray[$key]["AverageMatchScore"] = 0;
                // @codeCoverageIgnoreEnd
            }
        }
    }
    
    // print_r($sortedMasteryArray); // Array now contains the average matchscore on a champion if there were occurences in the last games

    $sortedMasteryArray = unique_multidim_array($sortedMasteryArray, "Champion"); // Remove any duplicates

    foreach($sortedMasteryArray as $key => $championData){
        if(!isset($sortedMasteryArray[$key]["OccurencesInLastGames"])) $sortedMasteryArray[$key]["OccurencesInLastGames"] = 0; // Handle empty occurences
        if(!isset($sortedMasteryArray[$key]["AverageMatchScore"])) $sortedMasteryArray[$key]["AverageMatchScore"] = 0; // Handle empty Scores
        $sortedMasteryArray[$key]["FinalScore"] = number_format((str_replace(',', '', $championData["Points"])**1.1)/(398107*1.25),2,'.','');
        $banExplainArray[$championData["Champion"]]["Points"]["Add"] = number_format((str_replace(',', '', $championData["Points"])**1.1)/(398107*1.25),2,'.','');
        $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["CapablePlayers"]*0.15;
        $banExplainArray[$championData["Champion"]]["CapablePlayers"]["Add"] = $sortedMasteryArray[$key]["CapablePlayers"]*0.15;
        $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["MatchingLanersPrio"]*0.4;
        $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Add"] = $sortedMasteryArray[$key]["MatchingLanersPrio"]*0.4;
        if(isset($sortedMasteryArray[$key]["TotalTeamPoints"])){
            $sortedMasteryArray[$key]["FinalScore"] += number_format((str_replace('.', '', $sortedMasteryArray[$key]["TotalTeamPoints"])**1.1)/(398107/(0.02*$sortedMasteryArray[$key]["CapablePlayers"])),2,'.','');
            $banExplainArray[$championData["Champion"]]["TotalTeamPoints"]["Add"] = number_format((str_replace('.', '', $sortedMasteryArray[$key]["TotalTeamPoints"])**1.1)/(398107/(0.02*$sortedMasteryArray[$key]["CapablePlayers"])),2,'.','');
        }

        switch ($sortedMasteryArray[$key]["LastPlayed"]){
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-1 year"): // Über ein Jahr her
                // @codeCoverageIgnoreStart
                $sortedMasteryArray[$key]["FinalScore"] += 0.16;
                $banExplainArray[$championData["Champion"]]["LastPlayed"]["Add"] = 0.16;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-6 months"): // Über 6 Monate unter 1 Jahr
                $sortedMasteryArray[$key]["FinalScore"] += 0.33;
                $banExplainArray[$championData["Champion"]]["LastPlayed"]["Add"] = 0.33;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-3 months"): // Über 3 Monate unter 6 Monate
                $sortedMasteryArray[$key]["FinalScore"] += 0.5;
                $banExplainArray[$championData["Champion"]]["LastPlayed"]["Add"] = 0.5;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-1 months"): // Über einen Monat unter 3 Monate
                $sortedMasteryArray[$key]["FinalScore"] += 0.66;
                $banExplainArray[$championData["Champion"]]["LastPlayed"]["Add"] = 0.66;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-2 weeks"): // Über zwei Wochen unter 1 Monat
                $sortedMasteryArray[$key]["FinalScore"] += 0.83;
                $banExplainArray[$championData["Champion"]]["LastPlayed"]["Add"] = 0.83;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] > strtotime("-2 weeks"): // Unter zwei Wochen her
                $sortedMasteryArray[$key]["FinalScore"] += 1;
                $banExplainArray[$championData["Champion"]]["LastPlayed"]["Add"] = 1;
                break;
                // @codeCoverageIgnoreEnd
        }

        if($sortedMasteryArray[$key]["OccurencesInLastGames"] > 0){
            $sortedMasteryArray[$key]["FinalScore"] += number_format(($sortedMasteryArray[$key]["OccurencesInLastGames"]**0.7)/4.07090,2,'.',''); // Exponential Function
            $banExplainArray[$championData["Champion"]]["OccurencesInLastGames"]["Add"] = number_format(($sortedMasteryArray[$key]["OccurencesInLastGames"]**0.7)/4.070905,2,'.','');
        }

        /**
         * Exponentiell:
         * 1  -> 0.24
         * 2  -> 0.39
         * 3  -> 0.53
         * 5  -> 0.75
         * 10 -> 1.23
         * 15 -> 1.63
         * 20 -> 2.00
         *
         */


        if($sortedMasteryArray[$key]["AverageMatchScore"] > 0){
            $sortedMasteryArray[$key]["FinalScore"] += number_format(($sortedMasteryArray[$key]["AverageMatchScore"]**1.75)/18.75,2,'.',''); // Exponential Function
            $banExplainArray[$championData["Champion"]]["AverageMatchScore"]["Add"] = number_format(($sortedMasteryArray[$key]["AverageMatchScore"]**1.75)/18.75,2,'.','');
        }

        /**
         * Linear:          Exponentiell:
         * 1  -> 0.30       1  -> 0.05
         * 2  -> 0.60       2  -> 0.17
         * 3  -> 0.90       3  -> 0.36
         * 4  -> 1.20       4  -> 0.60
         * 5  -> 1.50       5  -> 0.89
         * 6  -> 1.80       6  -> 1.22
         * 7  -> 2.10       7  -> 1.60
         * 8  -> 2.40       8  -> 2.02
         * 9  -> 2.70       9  -> 2.49
         * 10 -> 3.00       10 -> 2.99
         *
         */

         $sortedMasteryArray[$key]["FinalScore"] = number_format($sortedMasteryArray[$key]["FinalScore"],2 ,".", "");
    }


    $returnArray = $sortedMasteryArray;

    usort($returnArray, function($a, $b){
        return $b["FinalScore"] <=> $a["FinalScore"];
    });

    $returnArray = array_slice($returnArray, 0, 10);

    // Fetch which player contributes most to single mastery points
    foreach($returnArray as $suggestedBan){
        foreach($masterDataArray as $sumid => $data){
            foreach($data as $singleChamp){
                if($singleChamp["Points"] == $suggestedBan["Points"]){
                    $banExplainArray[$suggestedBan["Champion"]]["Filename"] = $suggestedBan["Filename"];
                    $banExplainArray[$suggestedBan["Champion"]]["Points"]["Value"] = str_replace(',', '', $suggestedBan["Points"]);
                    $banExplainArray[$suggestedBan["Champion"]]["Points"]["Cause"] = $sumid;
                    $banExplainArray[$suggestedBan["Champion"]]["CapablePlayers"]["Value"] = $suggestedBan["CapablePlayers"];
                    $banExplainArray[$suggestedBan["Champion"]]["MatchingLanersPrio"]["Value"] = $suggestedBan["MatchingLanersPrio"];
                    $banExplainArray[$suggestedBan["Champion"]]["LastPlayed"]["Value"] = $suggestedBan["LastPlayed"]; // Also includes last time played in normals
                    if(isset($banExplainArray[$suggestedBan["Champion"]]["OccurencesInLastGames"]["Add"])){
                        $banExplainArray[$suggestedBan["Champion"]]["OccurencesInLastGames"]["Games"] = count($matchIDArray);
                    }
                    $banExplainArray[$suggestedBan["Champion"]]["AverageMatchScore"]["Value"] = $suggestedBan["AverageMatchScore"];
                    $banExplainArray[$suggestedBan["Champion"]]["FinalScore"] = $suggestedBan["FinalScore"];
                }
            }
        }
    }

    // Remove any entries from the banExplainArray if they are not necessary information
    foreach($banExplainArray as $championName => $data){
        if(!in_array($championName, array_column($returnArray, "Champion"))){
            unset($banExplainArray[$championName]);
        }
    }

    // Sort the final info by FinalScore
    $finalScores = array_column($banExplainArray, "FinalScore");
    array_multisort($finalScores, SORT_DESC, $banExplainArray);

    // $returnAndExplainArray["Return"] = $returnArray;
    // $returnAndExplainArray["Explain"] = $banExplainArray;

    return $banExplainArray;
}

/** This function the necessary information for a correct profile icon + border display
 * @param array $rankData A players stored information about his rank, viewable in his sumid.json
 *
 * @var int $rankVal The valuater, which saves a score to later determine the highest rank of a player if multiple are present
 * @var string $highestRank A playeholder variable which will be overwritten if another $rankVal is higher than the previous $rankVel
 *             This one holds the current highest rank tier, e.g. PLATINUM
 * @var string $rankNumber The roman form number of a give rank, e.g. IV
 * @var string $highEloLP Just to store the LP count in case the rank is high elo (Master+)
 *
 * Returnvalue:
 * @return array The custom return array consists of a type which is either Rank or Level to determine the icon border
 *              Additionally it receives the necessary level filename or whole ranked data to further do stuff with it -> see team.php
 */
function getRankOrLevel($rankData, $playerData){
    $rankVal = 0; // This score is used to find the highest Rank from both Flex and Solo Queue | Local Variable
    $highEloLP = ""; // If the user has reached high elo the LP count is important (just for Master, Grandmaster and Challenger)
    $winrate = 0; // Initialize winrate variable

    foreach($rankData as $rankedQueue){ // Sorted after rank distribution (https://www.leagueofgraphs.com/de/rankings/rank-distribution)
        if($rankedQueue["Queue"] == "RANKED_SOLO_5x5" || $rankedQueue["Queue"] == "RANKED_FLEX_SR" ){
            if($rankedQueue["Tier"] == "SILVER" && $rankVal < 3){
                $rankVal = 3;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "GOLD" && $rankVal < 4){
                // @codeCoverageIgnoreStart
                $rankVal = 4;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "BRONZE" && $rankVal < 2){
                $rankVal = 2;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "PLATINUM" && $rankVal < 5){
                $rankVal = 5;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "EMERALD" && $rankVal < 6){
                $rankVal = 6;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "IRON" && $rankVal < 1){
                $rankVal = 1;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "DIAMOND" && $rankVal < 7){
                $rankVal = 7;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
            } else if($rankedQueue["Tier"] == "MASTER" && $rankVal < 8){
                $rankVal = 8;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
                // @codeCoverageIgnoreEnd
            } else if($rankedQueue["Tier"] == "GRANDMASTER" && $rankVal < 9){
                $rankVal = 9;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
                // @codeCoverageIgnoreStart
            } else if($rankedQueue["Tier"] == "CHALLENGER" && $rankVal < 10){
                $rankVal = 10;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
                if(isset($rankedQueue["Winrate"])) $winrate = $rankedQueue["Winrate"];
                // @codeCoverageIgnoreEnd
            }
        }
    }
    if($rankVal != 0){
        return array("Type" => "Rank", "HighestRank" => $highestRank, "HighEloLP" => $highEloLP, "RankNumber" => $rankNumber, "Winrate" => $winrate);
    } else {
        if($playerData["Level"] < 30){
            $levelFileName = "001";
        } else if($playerData["Level"] < 50){
            // @codeCoverageIgnoreStart
            $levelFileName = "030";
        } else if($playerData["Level"] < 75){
            $levelFileName = "050";
        } else if($playerData["Level"] < 100){
            $levelFileName = "075";
        } else if($playerData["Level"] < 125){
            $levelFileName = "100";
        } else if($playerData["Level"] < 150){
            $levelFileName = "125";
        } else if($playerData["Level"] < 175){
            $levelFileName = "150";
        } else if($playerData["Level"] < 200){
            $levelFileName = "175";
        } else if($playerData["Level"] < 225){
            $levelFileName = "200";
        } else if($playerData["Level"] < 250){
            $levelFileName = "225";
        } else if($playerData["Level"] < 275){
            $levelFileName = "250";
        } else if($playerData["Level"] < 300){
            $levelFileName = "275";
        } else if($playerData["Level"] < 325){
            $levelFileName = "300";
        } else if($playerData["Level"] < 350){
            $levelFileName = "325";
        } else if($playerData["Level"] < 375){
            $levelFileName = "350";
        } else if($playerData["Level"] < 400){
            $levelFileName = "375";
        } else if($playerData["Level"] < 425){
            $levelFileName = "400";
        } else if($playerData["Level"] < 450){
            $levelFileName = "425";
        } else if($playerData["Level"] < 475){
            $levelFileName = "450";
        } else if($playerData["Level"] < 500){
            $levelFileName = "475";
            // @codeCoverageIgnoreEnd
        } else if($playerData["Level"] >= 500){
            $levelFileName = "500";
        }
        return array("Type" => "Level", "LevelFileName" => $levelFileName);
    }
}

/** This function simply returns a color code corresponding to a textual rank input, e.g. "PLATINUM"
 * @param $currentRank The current rank as capslocked string
 *
 * Returnvalue:
 * @return string A hexadecimal color code
 */
function getMasteryColor($masteryPoints){
    if(is_numeric($masteryPoints)){
        if ($masteryPoints < 100000){
            return "threat-xxs";
        } else if ($masteryPoints >= 100000 && $masteryPoints < 200000){
            return "threat-xs";
        } else if ($masteryPoints >= 200000 && $masteryPoints < 300000){
            return "threat-s";
        } else if ($masteryPoints >= 300000 && $masteryPoints < 500000){
            return "threat-m";
        } else if ($masteryPoints >= 500000 && $masteryPoints < 700000){
            return "threat-l";
        } else if ($masteryPoints >= 700000 && $masteryPoints < 1000000){
            return "threat-xl";
        } else if ($masteryPoints >= 1000000){
            return "threat-xxl";
        }
    } else {
        return "";
    }
}

function calculateSmurfProbability($playerData, $rankData, $masteryData) {
    $resultArray = array();

    // Detect suspicion about last profile change (the longer no change the higher the suspicion)
    $timestamp = intval($playerData["LastChange"] / 1000); // summoner name change, summoner level change, or profile icon change will trigger a reset of this timestamp/suspicion
    if ($timestamp < strtotime("-1 year")) { // Über ein Jahr her
        $resultArray["LastChangeSus"] = 1;
        // @codeCoverageIgnoreStart
    } elseif ($timestamp < strtotime("-6 months")) { // Über 6 Monate unter 1 Jahr
        $resultArray["LastChangeSus"] = 0.8;
    } elseif ($timestamp < strtotime("-3 months")) { // Über 3 Monate unter 6 Monate
        $resultArray["LastChangeSus"] = 0.6;
    } elseif ($timestamp < strtotime("-1 months")) { // Über einen Monat unter 3 Monate
        $resultArray["LastChangeSus"] = 0.4;
    } elseif ($timestamp < strtotime("-2 weeks")) { // Über zwei Wochen unter 1 Monat
        $resultArray["LastChangeSus"] = 0.2;
        // @codeCoverageIgnoreEnd
    } else { // Unter zwei Wochen her
        $resultArray["LastChangeSus"] = 0;
    }

    // Level suspicion detection
    if ($playerData["Level"] <= 30) { // Level 30 oder niedriger
        $resultArray["LevelSus"] = 1;
        // @codeCoverageIgnoreStart
    } elseif ($playerData["Level"] <= 50) { // Level 50 oder niedriger
        $resultArray["LevelSus"] = 0.8;
    } elseif ($playerData["Level"] <= 70) { // Level 70 oder niedriger
        $resultArray["LevelSus"] = 0.6;
    } elseif ($playerData["Level"] <= 90) { // Level 90 oder niedriger
        $resultArray["LevelSus"] = 0.4;
    } elseif ($playerData["Level"] <= 110) { // Level 110 oder niedriger
        $resultArray["LevelSus"] = 0.2;
        // @codeCoverageIgnoreEnd
    } else { // Level 111 oder höher
        $resultArray["LevelSus"] = 0;
    }
    

    // Ranked Game Count suspicion detection
    $totalRankedMatches = 0;
    if(empty($rankData) || empty(array_intersect(array("RANKED_SOLO_5x5", "RANKED_FLEX_SR"), array_column($rankData,"Queue")))){
        $resultArray["RankedGameCountSus"] = 1;
    } else {
        foreach($rankData as $rankQueue){
            if($rankQueue["Queue"] == "RANKED_SOLO_5x5"){
                $totalRankedMatches += $rankQueue["Wins"] + $rankQueue["Losses"];
            } else if($rankQueue["Queue"] == "RANKED_FLEX_SR"){
                $totalRankedMatches += $rankQueue["Wins"] + $rankQueue["Losses"];
            }
        }
        if ($totalRankedMatches == 0) { // Keine Ranked Games gespielt
            // @codeCoverageIgnoreStart
            $resultArray["RankedGameCountSus"] = 1;
        } elseif ($totalRankedMatches <= 20) { // 20 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.8;
        } elseif ($totalRankedMatches <= 40) { // 40 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.6;
        } elseif ($totalRankedMatches <= 60) { // 60 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.4;
        } elseif ($totalRankedMatches <= 80) { // 80 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.2;
            // @codeCoverageIgnoreEnd
        } else { // 81 oder mehr gespielt
            $resultArray["RankedGameCountSus"] = 0;
        }
    }

    // Mastery Data Point suspicion detection
    $totalMastery = 0;
    if(empty($masteryData)){
        $resultArray["MasteryDataSus"] = 1;
    } else {
        foreach($masteryData as $champMastery){
            $totalMastery += str_replace(',', '', (int)$champMastery["Points"]);
        }
    }
    if ($totalMastery == 0) { // Keine Champion Mastery
        $resultArray["MasteryDataSus"] = 1;
        // @codeCoverageIgnoreStart
    } elseif ($totalMastery <= 40) { // weniger als 40k Punkte
        $resultArray["MasteryDataSus"] = 0.8;
    } elseif ($totalMastery <= 80) { // weniger als 80k Punkte
        $resultArray["MasteryDataSus"] = 0.6;
    } elseif ($totalMastery <= 120) { // weniger als 120k Punkte
        $resultArray["MasteryDataSus"] = 0.4;
    } elseif ($totalMastery <= 160) { // weniger als 160k Punkte
        $resultArray["MasteryDataSus"] = 0.2;
        // @codeCoverageIgnoreEnd
    } elseif ($totalMastery > 160) { // mehr als 160k Punkte
        $resultArray["MasteryDataSus"] = 0;
    }

    // Durchschnitt berechnen
    $sum = 0;
    $count = count($resultArray);
    foreach ($resultArray as $susScore) {
        $sum += $susScore;
    }
    return $sum / $count;
}

/**
 * This function generates a Tag with a specific background color aswell as a tooltip when hovering over
 *
 * @param string $tagText The displayed text content of the tag
 * @param string $bgColor The background color of the tag-button
 * @param string $tooltipText The tooltip text shown when hovering over the tag
 * @param string $additionalData Possible additiona data like the determination of positive or negative
 *
 * @return string A generated html tag as a div element with a tooltop hover function
 *
 */
function generateTag($tagText, $bgColor, $tooltipText, $additionalData) {
    $translatedTagText = __($tagText);
    $translatedTooltipText = __($tooltipText);
    if(isset($_COOKIE["tagOptions"])){
        if($_COOKIE["tagOptions"] == "multi-colored"){
            return "<div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] $bgColor cursor-help'
                    onmouseenter='showTooltip(this, \"$translatedTooltipText\", 500, \"top-right\")'
                    onmouseleave='hideTooltip(this)' data-type=\"$additionalData\" data-color=\"$bgColor\">
                    $translatedTagText
                    </div>";
        } else {
            return "Unknown tag option";
        }
    } else {
        if($additionalData != ""){
            $bgClass = ($additionalData == "positive") ? "bg-tag-lime" : "bg-tag-red";
            return "<div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] $bgClass cursor-help'
                    onmouseenter='showTooltip(this, \"$translatedTooltipText\", 500, \"top-right\")'
                    onmouseleave='hideTooltip(this)' data-type=\"$additionalData\" data-color=\"$bgColor\">
                    $translatedTagText
                    </div>";
        }else {
            return "Unknown tag option";
        }
    }
}

/**
 * This function generates all tags from a fetched array
 *
 * @param array $tagArray An array containing all fetched/calculated tags of a player
 *
 * @return string $returnString as the "filled" tag list
 *
 */
function tagSelector($tagArray) {
    $returnString = "";
    foreach($tagArray as $tag => $value){
        switch ($tag) {
            case 'dragonTakedowns':
                if ($value > 0) {
                    $returnString .= generateTag(__("Dragonmaster"), "bg-tag-navy", sprintf(__("%s more likely to take down drakes"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Dragonfumbler"), "bg-tag-blue", sprintf(__("%s less likely to take down drakes"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'kda':
                if ($value > 0) {
                    $returnString .= generateTag(__("K/DA"), "bg-tag-pink", sprintf(__("The KDA of this player is %s better than usual"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Careless"), "bg-tag-yellow", sprintf(__("The KDA of this player is %s worse than usual"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            // @codeCoverageIgnoreStart
            case 'killParticipation':
                if ($value > 0) {
                    $returnString .= generateTag(__("Relevant"), "bg-tag-purple", sprintf(__("%s better kill participation than usual"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Irrelevant"), "bg-tag-cyan", sprintf(__("%s worse kill participation than usual"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'riftHeraldTakedowns':
                if ($value > 0) {
                    $returnString .= generateTag(__("Harbinger"), "bg-tag-purple", sprintf(__("%s more likely to take down heralds"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Scared"), "bg-tag-purple", sprintf(__("%s less likely to take down heralds"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'skillshotsDodged':
                if ($value > 0) {
                    $returnString .= generateTag(__("Evasive"), "bg-tag-cyan", sprintf(__("Dodges %s more often"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Target"), "bg-tag-green", sprintf(__("Gets hit %s more often"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'skillshotsHit':
                if ($value > 0) {
                    $returnString .= generateTag(__("Precision"), "bg-tag-red", sprintf(__("Hits %s more skillshots"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("One-Eyed"), "bg-tag-blue", sprintf(__("Misses %s more skillshots"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'takedowns':
                if ($value > 0) {
                    $returnString .= generateTag(__("Slayer"), "bg-tag-red", sprintf(__("%s more takedowns than usual"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Retired"), "bg-tag-red", sprintf(__("%s less takedowns than usual"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'turretPlatesTaken':
                if ($value > 0) {
                    $returnString .= generateTag(__("Hammer"), "bg-tag-yellow", sprintf(__("%s more turret plates taken"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Hesitator"), "bg-tag-navy", sprintf(__("%s fewer turret plates taken"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'turretTakedowns':
                if ($value > 0) {
                    $returnString .= generateTag(__("Sieger"), "bg-tag-orange", sprintf(__("%s more turrets demolished than usual"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Bystander"), "bg-tag-purple", sprintf(__("%s fewer turrets demolished than usual"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'wardTakedowns':
                if ($value > 0) {
                    $returnString .= generateTag(__("Obfuscator"), "bg-tag-cyan", sprintf(__("Eliminates %s more wards than usual"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Flare"), "bg-tag-yellow", sprintf(__("Clears %s less wards than usual"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'wardsGuarded':
                if ($value > 0) {
                    $returnString .= generateTag(__("Protector"), "bg-tag-pink", sprintf(__("Protects %s more wards than usual"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Eye-Opener"), "bg-tag-cyan", sprintf(__("Ignores enemies trying to take down wards %s more likely"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'assists':
                if ($value > 0) {
                    $returnString .= generateTag(__("Advocate"), "bg-tag-red", sprintf(__("Playstyle is %s more assist-heavy"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Loner"), "bg-tag-green", sprintf(__("Playstyle is %s less assist-heavy"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'consumablesPurchased':
                if ($value > 0) {
                    $returnString .= generateTag(__("Prepared"), "bg-tag-orange", sprintf(__("%s more frequent consumable purchases"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Ill"), "bg-tag-orange", sprintf(__("%s less frequent consumable purchases"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'damageDealtToBuildings':
                if ($value > 0) {
                    $returnString .= generateTag(__("Demolisher"), "bg-tag-purple", sprintf(__("%s higher damage to buildings"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Architect"), "bg-tag-navy", sprintf(__("%s lower damage to buildings"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'damageDealtToObjectives':
                if ($value > 0) {
                    $returnString .= generateTag(__("Controller"), "bg-tag-red", sprintf(__("%s higher objective damage"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Primate"), "bg-tag-blue", sprintf(__("%s lower objective damage"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'damageSelfMitigated':
                if ($value > 0) {
                    $returnString .= generateTag(__("Mitigator"), "bg-tag-lime", sprintf(__("%s more self-mitigated damage"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Amplifier"), "bg-tag-orange", sprintf(__("%s less self-mitigated damage"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'deaths':
                if ($value > 0) {
                    $returnString .= generateTag(__("Survivor"), "bg-tag-cyan", sprintf(__("%s fewer deaths"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Fragile"), "bg-tag-red", sprintf(__("%s more deaths"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'detectorWardsPlaced':
                if ($value > 0) {
                    $returnString .= generateTag(__("Detector"), "bg-tag-pink", sprintf(__("%s more detector wards placed"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Thriftly"), "bg-tag-yellow", sprintf(__("%s less detector wards placed"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'goldEarned':
                if ($value > 0) {
                    $returnString .= generateTag(__("Hoarder"), "bg-tag-gold", sprintf(__("%s more gold earned"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Broke"), "bg-tag-navy", sprintf(__("%s less gold earned"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'inhibitorTakedowns':
                if ($value > 0) {
                    $returnString .= generateTag(__("Conqueror"), "bg-tag-purple", sprintf(__("%s more successful inhibitor takedowns"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Struggler"), "bg-tag-red", sprintf(__("%s more failed inhibitor takedowns"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'kills':
                if ($value > 0) {
                    $returnString .= generateTag(__("Killer"), "bg-tag-red", sprintf(__("%s more kills"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Pacifist"), "bg-tag-cyan", sprintf(__("%s fewer kills"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'neutralMinionsKilled':
                if ($value > 0) {
                    $returnString .= generateTag(__("Advantage"), "bg-tag-lime", sprintf(__("%s more neutral objectives killed (Scuttles, Dragons, Barons, Heralds)"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Null"), "bg-tag-navy", sprintf(__("%s more likely to neglect neutral objectives (Scuttles, Dragons, Barons, Heralds)"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalDamageDealtToChampions':
                if ($value > 0) {
                    $returnString .= generateTag(__("Chainsaw"), "bg-tag-red", sprintf(__("%s higher damage to champions"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Cautious"), "bg-tag-lime", sprintf(__("%s lower damage to champions"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalDamageShieldedOnTeammates':
                if ($value > 0) {
                    $returnString .= generateTag(__("Angel"), "bg-tag-cyan", sprintf(__("%s more damage shielded on teammates"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Neglectful"), "bg-tag-yellow", sprintf(__("%s less damage shielded on teammates"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalDamageTaken':
                if ($value > 0) {
                    $returnString .= generateTag(__("Soaker"), "bg-tag-green", sprintf(__("%s more damage taken"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Porcelain"), "bg-tag-blue", sprintf(__("%s fewer damage taken"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalHealsOnTeammates':
                if ($value > 0) {
                    $returnString .= generateTag(__("Nurse"), "bg-tag-pink", sprintf(__("%s more heals on teammates"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Mortician"), "bg-tag-navy", sprintf(__("%s less heals on teammates"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalMinionsKilled':
                if ($value > 0) {
                    $returnString .= generateTag(__("Collector"), "bg-tag-yellow", sprintf(__("%s more minion kills"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Sparing"), "bg-tag-blue", sprintf(__("%s fewer minion kills"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalTimeCCDealt':
                if ($value > 0) {
                    $returnString .= generateTag(__("Crowd Controller"), "bg-tag-cyan", sprintf(__("%s more crowd controlling"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Crowd Avoider"), "bg-tag-navy", sprintf(__("%s less crowd controlling"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'totalTimeSpentDead':
                if ($value > 0) {
                    $returnString .= generateTag(__("AFK"), "bg-tag-red", sprintf(__("Time spent dead is %s longer/higher"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Undead"), "bg-tag-cyan", sprintf(__("Time spent dead is %s shorter/lower"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'visionScore':
                if ($value > 0) {
                    $returnString .= generateTag(__("Visionary"), "bg-tag-yellow", sprintf(__("Vision score is %s higher"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Blind"), "bg-tag-red", sprintf(__("Vision score is %s lower"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            case 'wardsPlaced':
                if ($value > 0) {
                    $returnString .= generateTag(__("Planter"), "bg-tag-lime", sprintf(__("Places %s more wards"), number_format($value * 100) . '%'), "positive");
                } else {
                    $returnString .= generateTag(__("Lazy"), "bg-tag-orange", sprintf(__("Places %s fewer wards"), number_format(($value*-1) * 100) . '%'), "negative");
                }
                break;
            default:
                break;
            // @codeCoverageIgnoreEnd
        }
    }
    return $returnString;
}

/**
 * Generates a CSRF Token to be used in forms
 *
 * @return string Returns the CSRF Token and saves it in the current session
 *
 */
function generateCSRFToken() {
    $token = bin2hex(random_bytes(32)); // Generiere einen zufälligen Token-Wert
    $_SESSION['csrf_token'] = $token; // Speichere den Token in der Sitzungsvariablen
    return $token; // Gib den Token-Wert zurück
}

/**
 * Converts an object to an array
 *
 * @param object $object The full path of the file as a string, just as file_exists would need
 *
 * @return array|boolean Returns either the converted array as an object or false if it fails
 *
 */
function objectToArray($object) {
    if (is_object($object) || is_array($object)) {
        $result = [];
        foreach ($object as $key => $value) {
            $result[$key] = is_object($value) || is_array($value) ? objectToArray($value) : $value;
        }
        return $result;
    } else {
        return false;
    }
}

/**
 * Caches the I/O extensive file_exists request for subsequent calls or in other words
 * checks if a file existence check was already performed and returns the result if so
 *
 * @param string $filePath The full path of the file as a string, just as file_exists would need
 *
 * @return boolean Returns the cached existence query result (true/false)
 *
 */
function fileExistsWithCache($filePath)
{
    global $fileExistsCache;

    // Check if already cached
    if (isset($fileExistsCache[$filePath])) {
        return $fileExistsCache[$filePath];
    }

    // If not, perform check and cache
    $exists = file_exists($filePath);
    $fileExistsCache[$filePath] = $exists;

    return $exists;
}

function weightedRand($range1, $range2, $power = 1.5) {
	$min = round(min($range1, $range2));
	$max = round(max($range1, $range2)) + 1;
	$random = floor($min + ($max - $min) * pow((rand(0,10000)/10000), $power));
	
	return $random;
}

/**
 * Adds the contents of the specified matchdata ($inhalt) to the global match data cache for subsequent player requests
 *
 * @param object $inhalt An object with the matchdata of a specific match, e.g. EUW_XXXXXXX 
 *
 * @return void
 *
 */
function addToGlobalMatchDataCache($inhalt){
    global $matchDataCache;

    if (!isset($matchDataCache[$inhalt->metadata->matchId])) {
        $matchDataCache[$inhalt->metadata->matchId] = $inhalt;
    }
    return;
}

/**
 * Necessary helper function to sort the matchdata in correct order after merging database and cache matchdata
 *
 * @param array $matchDataArray An array containing the merged cached and database requested matchdata
 *
 * @return array $matchDataArray the sorted array
 *
 */
function sortByMatchIds($matchDataArray) {
    usort($matchDataArray, function ($a, $b) {
        $matchIdA = isset($a->metadata->matchId) ? $a->metadata->matchId : '';
        $matchIdB = isset($b->metadata->matchId) ? $b->metadata->matchId : '';
        $numericIdA = intval(substr($matchIdA, strrpos($matchIdA, '_') + 1));
        $numericIdB = intval(substr($matchIdB, strrpos($matchIdB, '_') + 1));
        return $numericIdB - $numericIdA;
    });
    return $matchDataArray;
}

/**
 * @codeCoverageIgnore
 */
function generatePlayerColumnData($requestIterator, $sumid, $teamID, $queuedAs, $reload, $csrf) {
    static $scriptLoaded = false;
    $script = '';
    if (!$scriptLoaded) {
        $script = "<script type='text/javascript' src='/clashapp/ajax.min.js?version=".md5_file("/hdd1/clashapp/js/ajax.min.js")."'></script>";
        $scriptLoaded = true;
    }

    return $script . "
    <script>
        generatePlayerColumnData('{$requestIterator}', '{$sumid}', '{$teamID}', '{$queuedAs}', '{$reload}', '{$csrf}');
    </script>";
}

/**
 * @codeCoverageIgnore
 */
function generateSinglePlayerData($playerName, $playerTag, $reload) {
    static $scriptLoaded = false;

    $script = '';
    if (!$scriptLoaded) {
        $script = "<script type='text/javascript' src='/clashapp/ajax.min.js?version=".md5_file("/hdd1/clashapp/js/ajax.min.js")."'></script>";
        $scriptLoaded = true;
    }

    return $script . "
    <script>
        generateSinglePlayerData('{$playerName}', '{$playerTag}', '{$reload}', '{$_SESSION['csrf_token']}');
    </script>";
}

/**
 * Match pattern: The string contains only characters '0' to '9' and 'a' to 'f' (or 'A' to 'F').
 *
 * @param string $csrf A string containing the to-be-checked csrfToken
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidCSRF($csrf) {
    return preg_match('/^[0-9a-fA-F]{64}$/', $csrf) === 1;
}

/**
 * Match pattern: letters, numbers, one underscore, length between 4 and 20
 *
 * @param string $matchID A string containing the to-be-checked matchID
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidMatchID($matchID) {
    $pattern = '/^[a-zA-Z0-9]+(?:_[a-zA-Z0-9]+)?$/';
    return preg_match($pattern, $matchID) && strlen($matchID) > 3 && strlen($matchID) < 21;
}

/**
 * Match pattern: numbers between 0 and 9
 *
 * @param int $matchID A number containing the to-be-checked iterator
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidIterator($iterator) {
    return is_numeric($iterator) && $iterator >= 0 && $iterator < 10;
}

/**
 * Match pattern: letters, numbers, underscores and hyphens
 *
 * @param string $id A string containing the to-be-checked sumid, puuid, accountid, etc.
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidID($id) {
    $pattern = '/^[a-zA-Z0-9_\-]+$/';
    return preg_match($pattern, $id) === 1;
}

/**
 * Match pattern: upper or lowercase specific word for a lane
 *
 * @param string $position A string containing the to-be-checked lane position
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidPosition($position) {
    $validPositions = array("BOT", "BOTTOM", "BOTLANE", "MID", "MIDDLE", "MIDLANE", "TOP", "TOPLANE", "JUNGLE", "SUPPORT", "UTILITY", "FILL", "UNSELECTED");
    $position = strtoupper($position); // for case-insensitive comparison
    return in_array($position, $validPositions);
}

/**
 * Match pattern: letters from any alphabet, length between 7 and 22
 * 
 * @param string $playerName A string containing the to-be-checked playerName
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidPlayerName($playerName) {
    // Match pattern: letters from any alphabet, numbers, underscore, hyphen, any whitespace character, length between 3 and 22
    return preg_match('/^[\p{L}0-9_\s\-#]{3,22}$/u', $playerName) === 1;
}

/**
 * Match pattern: letters from any alphabet, length between 3 and 5
 * 
 * @param string $playerTag A string containing the to-be-checked playerTag
 *
 * @return boolean True | False depending on if the format is correct
 *
 */
function isValidPlayerTag($playerTag) {
    // Match pattern: letters from any alphabet, length between 3 and 5
    return preg_match('/^[\p{L}0-9_\s-]{3,5}$/u', $playerTag) === 1;
}

function doesChampionExist($input, $lang) {
    global $currentPatch;
    $returnArray = array(
        "success" => false,
        "data" => []
    );
    $formattedChampion = strtolower(preg_replace('/[.\'\-\s]+/', '', $input));
    if($lang != null){
        $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/'.$lang.'/champion.json');
    } else { // Fallback in case no lang cookie exists
        $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/en_US/champion.json');
    }
    $json = json_decode($data);
    foreach($json->data as $champion){
        $formattedChampID = strtolower(preg_replace('/[.\'\-\s]+/', '', $champion->name));
        if($formattedChampion == $formattedChampID){
            if($lang != null){
                $champData = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/'.$lang.'/champion/'.$champion->id.'.json');
            } else { // Fallback in case no lang cookie exists
                $champData = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/en_US/champion/'.$champion->id.'.json');
            }
            $returnArray["success"] = true;
            $returnArray["data"] = reset(json_decode($champData, true)["data"]);
            break;
        }
    }
    return $returnArray;
}
?>