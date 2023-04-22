<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);


/** Main functions.php containing overall used functions throughout different php files
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 * 
 * Initializing of global variables used throughout all functions below
 *
 * @global mixed $apiKey The API Key necessary to communicate with the Riot API, to edit: nano /etc/nginx/fastcgi_params then service nginx restart
 * @global string $currentPatch For example "12.4.1", gets fetched from the version.txt which itself gets daily updated by the patcher.py script
 * @global int $counter Necessary counter variable for the getMatchByID Function
 * @global array $headers The headers required or at least recommended for the CURL request
 * @global int $currenttimestam The current time stamp usable as a global variable
 */
$apiKey = getenv('API_KEY');
$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");
$counter = 0;
$headers = array(
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
    "Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7",
    "Accept-Charset: application/x-www-form-urlencoded; charset=UTF-8",
    "Origin: https://dasnerdwork.net/",
    "X-Riot-Token: ".$apiKey
 );
$currentTimestamp = time();
$rankingAttributeArray = array("Kills", "Deaths", "Assists", "KDA", "KillParticipation", "CS", "Gold", "VisionScore", "WardTakedowns", "WardsPlaced", "WardsGuarded", "VisionWards", "Consumables", "TurretPlates", "TotalTakedowns", "TurretTakedowns", 
"InhibitorTakedowns", "DragonTakedowns", "HeraldTakedowns", "DamageToBuildings", "DamageToObjectives", "DamageMitigated", "DamageDealtToChampions", "DamageTaken", "TeamShielded", "TeamHealed", "TimeCC", "DeathTime", "SkillshotsDodged", "SkillshotsHit");
$cleanAttributeArray = array("kills", "deaths", "assists", "kda", "killParticipation", "totalMinionsKilled", "goldEarned", "visionScore", "wardTakedowns", "wardsPlaced", "wardsGuarded", "detectorWardsPlaced", "consumablesPurchased", "turretPlatesTaken",
"takedowns", "turretTakedowns", "inhibitorTakedowns", "dragonTakedowns", "riftHeraldTakedowns", "damageDealtToBuildings", "damageDealtToObjectives", "damageSelfMitigated", "totalDamageDealtToChampions", "totalDamageTaken", "totalDamageShieldedOnTeammates",
"totalHealsOnTeammates", "totalTimeCCDealt", "totalTimeSpentDead", "skillshotsDodged", "skillshotsHit", "championName", "championTransform", "individualPosition", "teamPosition", "lane", "puuid", "summonerId","summonerName", "win", "neutralMinionsKilled");

/** General Summoner Info
 * This function retrieves all general playerdata of a given username or PUUID
 * Eq. to https://developer.riotgames.com/apis#summoner-v4/GET_getBySummonerName
 *
 * @param string $type Determines if the request gets sent to the API with a username or a PUUID
 * @param mixed $id Is the given username, SumID or PUUID
 * @var array $output Contains the output of the curl request as string which we later convert using json_decode
 * @var string $httpCode Contains the returncode of the curl request (e.g. 404 not found)
 *
 * Returnvalue:
 * @return array $playerDataArray with keys "Icon", "Name", "Level", "PUUID", "SumID", "AccountID" and "LastChange" of the summoners profile
 */
function getPlayerData($type, $id){
    global $headers;
    $playerDataArray = array();

    switch ($type) {
        case "name":
            $requestUrlVar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/";
            break;
        case "puuid":
            $requestUrlVar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/";
            break;
        case "sumid":
            $requestUrlVar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/";
            break;
    }

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requestUrlVar . $id);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // echo "<script>playerDataCalls++;</script>";

    // 403 Access forbidden -> Outdated API Key
    if($httpCode == "403"){
        echo "<h2>403 Forbidden GetPlayerData</h2>";
    }

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(10);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrlVar . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        // echo "<script>playerDataCalls++;</script>";
    }

    // Collect requested values in returnarray
    $playerDataArray["Icon"] = json_decode($output)->profileIconId;
    $playerDataArray["Name"] = json_decode($output)->name;
    $playerDataArray["Level"] = json_decode($output)->summonerLevel;
    $playerDataArray["PUUID"] = json_decode($output)->puuid;
    $playerDataArray["SumID"] = json_decode($output)->id;
    $playerDataArray["AccountID"] = json_decode($output)->accountId;
    $playerDataArray["LastChange"] = json_decode($output)->revisionDate;

    return $playerDataArray;
}

/** Get info about summoners mastery scores
 * This function retrieves the all available mastery score info about a summoner
 *
 * Eq. to https://developer.riotgames.com/apis#champion-mastery-v4/GET_getAllChampionMasteries
 * Also possible for Total Mastery score or masterdata only about a single champion
 *
 * @param string $sumid The summoners encrypted summoner ID necessary to perform the API request
 * @var array $masteryDataArray The temporary array to fetch a single champions mastery data
 * @var array $output Contains the output of the curl request as string which we later convert using json_decode
 * @var string $httpCode Contains the returncode of the curl request (e.g. 404 not found)
 *
 * Returnvalue:
 * @return array $masteryReturnArray The full return array including all single champion arrays
 */
function getMasteryScores($sumid){
    $masteryDataArray = array();
    $masteryReturnArray = array();
    global $headers;

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$sumid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "<script>masteryScoresCalls++;</script>";

    // 403 Forbidden
    if($httpCode == "403"){
        echo "<h2>403 Forbidden MasteryScores</h2>";
    }
    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(10);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$sumid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "<script>masteryScoresCalls++;</script>";
    }

    

    // Resolving return values
    foreach(json_decode($output, true) as $masteryArray){
        if($masteryArray["championLevel"] > 4 || $masteryArray["championPoints"] > 19999){
            $masteryDataArray["Champion"] = championIdToName($masteryArray["championId"]);
            $masteryDataArray["Filename"] = championIdToFilename($masteryArray["championId"]);
            $masteryDataArray["Lvl"] = $masteryArray["championLevel"];
            $masteryDataArray["Points"] = number_format($masteryArray["championPoints"]);
            $masteryDataArray["LastPlayed"] = $masteryArray["lastPlayTime"]/1000; // to get human-readable one -> date('d.m.Y H:i:s', $masteryData["LastPlayed"]);
            // in case tokens for lvl 6 or 7 in inventory add them too
            if($masteryArray["tokensEarned"] > 0){
                $masteryDataArray["LvlUpTokens"] = $masteryArray["tokensEarned"];
            }
            $masteryReturnArray[] = $masteryDataArray;
        }
    }

    return $masteryReturnArray;
}

/** Fetch ranked info of user via sumid
 * This function retrieves the all available ranked info about a summoner
 *
 * Eq. to https://developer.riotgames.com/apis#league-v4/GET_getLeagueEntriesForSummoner
 *
 * @param string $sumid The summoners encrypted summoner ID necessary to perform the API request
 * @var array $rankDataArray Just a rename and rearrange of the API request return values
 * @var array $output Contains the output of the curl request as string which we later convert using json_decode
 * @var string $httpCode Contains the returncode of the curl request (e.g. 404 not found)
 * 
 * Returnvalue:
 * @return array $rankReturnArray Just a rename of the $rankDataArray
 */
function getCurrentRank($sumid){
    $rankDataArray = array();
    $rankReturnArray = array();
    global $headers;

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "<script>currentRankCalls++;</script>";

    // 403 Forbidden
    if($httpCode == "403"){
        echo "<h2>403 Forbidden CurrentRank</h2>";
    }

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(10);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "<script>currentRankCalls++;</script>";
    }

    // Resolving return values
    foreach(json_decode($output, true) as $requestArray){
        $rankDataArray["Queue"] = $requestArray["queueType"];
        $rankDataArray["Tier"] = $requestArray["tier"];
        $rankDataArray["Rank"] = $requestArray["rank"];
        $rankDataArray["LP"] = $requestArray["leaguePoints"];
        $rankDataArray["Wins"] = $requestArray["wins"];
        $rankDataArray["Losses"] = $requestArray["losses"];
        $rankReturnArray[] = $rankDataArray;
    }
    return $rankReturnArray;
}

/** Array of MatchIDs
 * This function retrieves all match IDs of a given PUUID up to a specified maximum
 * Eq. to https://developer.riotgames.com/apis#match-v5/GET_getMatchIdsByPUUID
 *
 * @param string $puuid Necessary PUUID of the summoner (Obtainable either through getPlayerData or via local stored file)
 * @param int $maxMatchIDs The maximum count to which we request matchIDs
 * @var string $gameType Set to the queue type of league "ranked", "normal", "tourney" or "tutorial"
 * @var int $start Starting at 0 and iterating by +100 every request (100 is the maximum of matchIDs you can request at once)
 * @var mixed $matchCount Always equals 100 except if it exceeds maxMatchIDs in it's next iteration, then set to max available
 *                             E.g. maxMatchIDs = 219, 1. Iteration = 100, 2. Iteration = 100, 3. Iteration = 19
 *
 * Returnvalue:
 * @return array $matchIDArray with all MatchIDs as separate entries
 */
function getMatchIDs($puuid, $maxMatchIDs){
    global $headers;
    $matchIDArray = array();
    $gameType = "ranked";
    $start = 0;
    $matchCount = "100";

    while ($start < $maxMatchIDs) {
        // If next iterations would exceed the max
        if(($start + 100) > $maxMatchIDs){
            $matchCount = 100 - (($start + 100) - $maxMatchIDs);
        }

        // Curl API request block
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gameType . "&start=" . $start . "&count=" . $matchCount);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $matchidOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "<script>matchIdCalls++;</script>";

        // 429 Too Many Requests
        if($httpCode == "429"){ /** @todo fetch function with switch to handle and log every httpcode error */
            sleep(5);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gameType . "&start=".$start."&count=" . $matchCount);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $matchidOutput = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "<script>matchIdCalls++;</script>";
        }

        // Add each matchID to return array
        foreach (json_decode($matchidOutput) as $match) {
            $matchIDArray[] = $match;
        }
        $start += 100;
    }
    return $matchIDArray;
}

/** Download and local storing of matchid.json
 * This function downloads, stores and logs anything about a given matchid
 * Eq. to https://developer.riotgames.com/apis#match-v5/GET_getMatch
 *
 * @param string $matchid The single matchID of the game this function is supposed to download the information about
 * @param mixed $username OPTIONAL Is the given username, as this value is only used for the logging message and not necessary to perform anything
 * @var string $logPath The path where the log should be saved to
 *
 * INFO: clearstatcache(); necessary for correct filesize statements as filesize() is a cached function
 *
 * Returnvalue:
 * @return void N/A, file saving & logging instead
 */
function downloadMatchByID($matchid, $username = null){
    global $headers, $counter;
    $logPath = '/hdd1/clashapp/data/logs/matchDownloader.log';
    $errorFile = null;

    // Halving of matchDownloader.log in case the logfile exceeds 10 MB
    if(filesize($logPath) > 10000000 && $counter == 0){
        $counter++;
        $file = file($logPath);
        $file = array_chunk($file, ceil(count($file)/2))[1];
        file_put_contents($logPath, $file, LOCK_EX);
        clearstatcache(true, $logPath);
        $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $slimmed = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Maximum filesize exceeded, removed first half of logfile - Status: OK (Size ".number_format((filesize($logPath)/1048576), 3)." MB)";
        file_put_contents($logPath, $slimmed.PHP_EOL , FILE_APPEND | LOCK_EX);
        $counter = 0;
    }

    // Only download if file doesn't exist yet
    if(!file_exists('/hdd1/clashapp/data/matches/' . $matchid . ".json")){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $matchOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        echo "<script>matchDownloadCalls++;</script>";


        // 429 Too Many Requests
        if($httpCode == "429"){
            sleep(10);
            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $limit = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Rate limit got exceeded -> Now sleeping for 5 seconds - Status: " . $httpCode . " Too Many Requests";
            file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $matchOutput = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "<script>matchDownloadCalls++;</script>";

        }

        // Write to log and save the matchid.json, else skip
        clearstatcache(true, $logPath);
        $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $answer = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: Got new matchdata from \"" . $username . "\" via " . $matchid . ".json - Status: " . $httpCode . " (Size: ".number_format((filesize($logPath)/1048576), 3)." MB)";
        file_put_contents($logPath, $answer.PHP_EOL , FILE_APPEND | LOCK_EX);
        if($httpCode == "200"){
            // if(($matchOutput->info->gameDuration != "0")){ // && (isset($matchOutput->info->participants[0]->killParticipation)
                $fp = fopen('/hdd1/clashapp/data/matches/' . $matchid . '.json', 'w');
                fwrite($fp, $matchOutput);
                fclose($fp);
            // } else {
            //     $errorFile = $matchid;
            //     $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            //     $warning = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: " . $matchid . " is empty or a remake - Skipping";
            //     file_put_contents($logPath, $warning.PHP_EOL , FILE_APPEND | LOCK_EX);
            // }
        } else {
            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $warning = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: " . $matchid . " received HTTP-Code: " . $httpCode . " - Skipping";
            file_put_contents($logPath, $warning.PHP_EOL , FILE_APPEND | LOCK_EX);
        }
    }else{
        $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $noAnswer = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: " . $matchid . ".json already existing - Skipping";
        file_put_contents($logPath, $noAnswer.PHP_EOL , FILE_APPEND | LOCK_EX);
    }
    // return array("Status" => "Success", "ErrorFile" => $errorFile);
    return;
}

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
    $startMemory = memory_get_usage();
    $matchData = array();
    global $cleanAttributeArray;

    // Loop through each matchID.json
    foreach ($matchIDArray as $key => $matchIDJSON) {
        if(memory_get_usage() - $startMemory > "268435456" || $key == 500)return $matchData; // If matchData array bigger than 256MB size or more than 500 matches -> stop and return
        if(file_exists('/hdd1/clashapp/data/matches/'.$matchIDJSON.'.json')){
           $matchData[$matchIDJSON] = json_decode(file_get_contents('/hdd1/clashapp/data/matches/'.$matchIDJSON.'.json')); 
           unset($matchData[$matchIDJSON]->metadata);
           unset($matchData[$matchIDJSON]->info->gameId);
           unset($matchData[$matchIDJSON]->info->gameMode);
           unset($matchData[$matchIDJSON]->info->gameName);
           unset($matchData[$matchIDJSON]->info->gameType);
           unset($matchData[$matchIDJSON]->info->mapId);
           $matchData[$matchIDJSON]->info->gameVersion = explode(".",$matchData[$matchIDJSON]->info->gameVersion)[0].".".explode(".",$matchData[$matchIDJSON]->info->gameVersion)[1];
           foreach($matchData[$matchIDJSON]->info->participants as $player){
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
            unset($matchData[$matchIDJSON]->info->platformId); // e.g. EUW
            unset($matchData[$matchIDJSON]->info->queueId); // E.g. 440 / Solo_Duo_Queue
            unset($matchData[$matchIDJSON]->info->teams);
            unset($matchData[$matchIDJSON]->info->tournamentCode);
        }        
    }
    return $matchData;
}

/** Function to convert seconds to readable time
 * 
 * @param int $seconds The amount of seconds given that we wan't to convert to human-readable time words
 * 
 * Returnvalue:
 * @return string Depending on switch case as seen below, but string sentence
 */
function secondsToTime($seconds) {
    switch ($seconds) {
        case ($seconds<120):
            return __("1 minute ago");
        case ($seconds>=120 && $seconds<3600):
            return sprintf(__("%d minutes ago"), floor($seconds / 60));
        case ($seconds>=3600 && $seconds<7200):
            return __("1 hour ago");
        case ($seconds>=7200 && $seconds<86400):
            return sprintf(__("%d hours ago"), floor($seconds / 3600));
        case ($seconds>=86400 && $seconds<172800):
            return __("1 day ago");
        case ($seconds>=172800 && $seconds<2630000):
            return sprintf(__("%d days ago"), floor($seconds / 86400));
        case ($seconds>=2630000 && $seconds<5260000):
            return __("1 month ago");
        case ($seconds>=5260000 && $seconds<31536000):
            return sprintf(__("%d months ago"), floor($seconds / 2630000));
        case ($seconds>=31536000 && $seconds<63072000):
            return __("1 years ago");
        case ($seconds>=63072000):
            return sprintf(__("%d years ago"), floor($seconds / 31536000));
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
 * @return void N/A, displaying on page via table
 * 
 * @todo possibility to make more beautiful
 */
function printTeamMatchDetailsByPUUID($matchIDArray, $puuid, $matchRankingArray){
    global $currentPatch;
    global $currentTimestamp;
    $count = 0;
    $totalTeamTakedowns = 0;

    // Initiating Matchdetail Table
    echo "<button type='button' class='collapsible bg-dark cursor-pointer h-6 w-full' 
            @click='open = !open' 
            x-text='open ? \"&#11167;\" : \"&#11165;\" '></button>";
    echo "<div class='smooth-transition w-full overflow-hidden min-h-[2300px]' x-show='open' x-transition x-cloak>";
    foreach ($matchIDArray as $i => $matchIDJSON) {
        $handle = file_get_contents("/hdd1/clashapp/data/matches/".$matchIDJSON.".json");
        $inhalt = json_decode($handle);
        if(isset($inhalt->metadata->participants) && $inhalt->info->gameDuration != 0) {
            if(in_array($puuid, (array) $inhalt->metadata->participants)){
                $count++;
                for($in = 0; $in < 10; $in++){
                    if($inhalt->info->participants[$in]->puuid == $puuid) {
                        $teamID = $inhalt->info->participants[$in]->teamId;
                        if($inhalt->info->participants[$in]->gameEndedInEarlySurrender){
                            echo '<div class="w-full bg-gray-800 border-b border-[4px] border-dark" x-data="{ advanced: false }" style="content-visibility: auto;">';
                        } elseif ($inhalt->info->participants[$in]->win == false){
                            echo '<div class="w-full bg-lose border-b border-[4px] border-dark" x-data="{ advanced: false }" style="content-visibility: auto;">';
                        } else {
                            echo '<div class="w-full bg-win border-b border-[4px] border-dark" x-data="{ advanced: false }" style="content-visibility: auto;">';
                        }
                            echo '<div id="match-header" class="inline-flex w-full gap-2 pt-2 px-2">';
                                echo '<div class="match-result mb-2">';
                                // Display of W(in) or L(ose)
                                if($inhalt->info->participants[$in]->gameEndedInEarlySurrender){
                                    echo '<span class="text-white font-bold">'.__("R").'</span>';
                                } elseif($inhalt->info->participants[$in]->win == true) {
                                    echo '<span class="text-online font-bold">'.__("W").'</span>';
                                } else {
                                    echo '<span class="text-offline font-bold">'.__("L").'</span>';
                                }
                                echo '</div>';

                                echo '<div class="match-type-and-time">';
                                // Display of Ranked Queuetype & Gamelength
                                switch ($inhalt->info->queueId) {
                                    case 420:
                                        $matchType = "Solo/Duo";
                                        echo "<span>".__("Solo/Duo")." ";
                                        break;
                                        case 440:
                                            $matchType = "Flex 5v5";
                                            echo "<span>".__("Flex")." ";
                                            break;
                                            case 700:
                                                $matchType = "Clash";
                                                echo "<span>".__("Clash")." ";
                                                break;
                                            }
                                            echo gmdate("i:s", $inhalt->info->gameDuration)."</span>";
                                            echo "</div>";
                                            
                                            // echo "<div class='match-id hidden'>".$matchIDJSON."</div>";
                                            
                                            echo '<div id="match-time-ago" class="ml-auto">';
                                            
                                // Display when the game date was, if > than 23h -> day format, if > than 30d -> month format, etc.
                                echo "<span>".secondsToTime(strtotime('now')-intdiv($inhalt->info->gameEndTimestamp, 1000))."</span></div>";
                                echo '</div>';
                                
                                // Display of the played champions icon
                                echo '<div class="champion-data flex gap-2 h-[68px] justify-around px-2"><div class="champion-data-left inline-flex gap-2"><div class="champion-icon">';
                                if ($inhalt->info->participants[$in])
                                $champion = $inhalt->info->participants[$in]->championName;
                                if($champion == "FiddleSticks"){$champion = "Fiddlesticks";} /** TODO: One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ */
                                if($champion == "Kayn"){
                                    if($inhalt->info->participants[$in]->championTransform == "1"){
                                        if(file_exists('/hdd1/clashapp/data/misc/webp/kayn_rhaast_darkin.webp')){
                                            echo '<img src="/clashapp/data/misc/webp/kayn_rhaast_darkin.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            echo '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative bottom-16 -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        }
                                    } else if($inhalt->info->participants[$in]->championTransform == "2") {
                                        if(file_exists('/hdd1/clashapp/data/misc/webp/kayn_shadow_assassin.webp')){
                                            echo '<img src="/clashapp/data/misc/webp/kayn_shadow_assassin.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            echo '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative bottom-16 -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        }
                                    } else {
                                        if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp')){
                                            echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            echo '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative bottom-16 -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        }
                                    }
                                } else {
                                    if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp')){
                                        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                        echo '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="max-w-[68px] min-w-[68px] flex align-middle relative bottom-16 -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                    } else {
                                        echo '<img src="/clashapp/data/misc/na.webp" width="68" height="68" class="align-middle max-w-[68px] min-w-[68px] rounded" loading="lazy" alt="This icon represents a value not being available">';
                                    }
                                }
                                
                                // Save values dealt for later print below
                                $dealt = number_format($inhalt->info->participants[$in]->totalDamageDealtToChampions, 0);
                                $tanked = number_format($inhalt->info->participants[$in]->totalDamageTaken, 0);
                                $shealed = number_format($inhalt->info->participants[$in]->challenges->effectiveHealAndShielding, 0);
                                $objs = number_format($inhalt->info->participants[$in]->damageDealtToObjectives, 0);
                                $visionWards = $inhalt->info->participants[$in]->detectorWardsPlaced;
                                $creepScore = $inhalt->info->participants[$in]->totalMinionsKilled+$inhalt->info->participants[$in]->neutralMinionsKilled;
                                $visionScore = $inhalt->info->participants[$in]->visionScore;

                        // Display of champion level at end of game
                        echo '<div class="champion-level flex relative w-4 h-4 max-w-[16px] min-w-[16px] z-20 -ml-4 bottom-[17px] -right-[17px] text-[13px] justify-center items-center">';
                        echo $inhalt->info->participants[$in]->champLevel;
                        echo '</div>';

                        // Display of played Position
                        echo "<div class='champion-lane flex relative w-4 h-4 max-w-[16px] min-w-[16px] z-20 -ml-4 bottom-[33px] -right-[66px] text-[13px] justify-center items-center'>";
                        $matchLane = $inhalt->info->participants[$in]->teamPosition;
                        if(file_exists('/hdd1/clashapp/data/misc/lanes/'.$matchLane.'.webp')){
                            echo '<img src="/clashapp/data/misc/lanes/'.$matchLane.'.webp" width="16" height="16"  loading="lazy" class="max-w-[16px] min-w-[16px] saturate-0 brightness-150" alt="Icon of a league of legends position for '.$matchLane.'">';
                        }
                        echo "</div>";
                        echo "</div>";
                        
                        // Display summoner spells
                        echo '<div class="summoner-spells grid grid-rows-2 gap-1">';
                        $summoner1Id = $inhalt->info->participants[$in]->summoner1Id;
                        $summoner2Id = $inhalt->info->participants[$in]->summoner2Id;
                        if(file_exists('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).".webp")){
                            echo '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).'.webp" width="32" height="32" class="rounded" loading="lazy" alt="Icon of a players first selected summoner spell">';
                        }
                        if(file_exists('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).".webp")){
                            echo '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).'.webp" width="32" height="32" class="rounded" loading="lazy" alt="Icon of a players second selected summoner spell">';
                        }
                        echo "</div>";

                        // Display of the equipped keyrune + secondary tree
                        echo '<div class="rune-1 grid grid-rows-2 gap-1">';
                        $keyRune = $inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk;
                        $secRune = $inhalt->info->participants[$in]->perks->styles[1]->style;
                        if(file_exists('/hdd1/clashapp/data/patch/img/'.substr(runeIconFetcher($keyRune), 0, -4).'.webp')){
                            echo '<img src="/clashapp/data/patch/img/'.substr(runeIconFetcher($keyRune), 0, -4).'.webp" width="32" height="32" loading="lazy" alt="Icon of a players first selected rune">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.webp" width="32" height="32" loading="lazy" alt="This icon represents a value not being available">';
                        }
                        if(file_exists('/hdd1/clashapp/data/patch/img/'.substr(runeTreeIconFetcher($secRune), 0, -4).'.webp')){
                            echo '<img src="/clashapp/data/patch/img/'.substr(runeTreeIconFetcher($secRune), 0, -4).'.webp" class="max-w-[22px] min-w-[22px] m-auto" loading="lazy" alt="Icon of a players second selected rune">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.webp" width="32" height="32" loading="lazy" alt="This icon represents a value not being available">';
                        }
                        echo "</div></div>";
                        
                        
                        // Display of the players Kills/Deaths/Assists
                        echo '<div class="kda-stats flex flex-col justify-center items-center"><div class="stats twok:text-[1.75rem] twok:tracking-tighter fullhd:text-[1.3rem] fullhd:-tracking-[.15rem]">';
                        $kills = $inhalt->info->participants[$in]->kills;
                        $deaths = $inhalt->info->participants[$in]->deaths;
                        $assists = $inhalt->info->participants[$in]->assists;
                        echo $kills . " / ";
                        echo "<div class='inline text-threat-s'>".$deaths."</div> / ";
                        echo $assists;
                        echo '</div><div class="kda text-xs">';
                        if($deaths != 0){
                            echo __("KDA").": ".number_format(($kills+$assists)/$deaths, 2)."</div>";
                        } else {
                            echo __("KDA").": ".number_format(($kills+$assists)/1, 2)."</div>";
                        }
                        echo "</div>";
                                
                        // calculate of Match Score 1-10
                        foreach ($matchRankingArray as $matchID => $rankingValue){
                            // print_r($matchID."<br>");
                            if($matchID == $inhalt->metadata->matchId){
                                $matchScore = __("Score").": ".$matchRankingArray[$matchID];
                            }
                        }

                        // Display of the last items the user had at the end of the game in his inventory
                        echo '<div class="items grid grid-rows-2 grid-cols-3 max-w-[104px] min-w-[104px] gap-1">';
                        $noItemCounter = 0;
                        // $lastItemSlot = 0;
                        for($b=0; $b<6; $b++){
                            // if($b == 6){
                            //     for($c=0; $c<$noItemCounter; $c++){
                            //         echo '<div class="item'.($lastItemSlot+1).'">';
                            //         echo '<img src="/clashapp/data/misc/0.webp" width="32" loading="lazy">';
                            //         echo '</div>';
                            //         $lastItemSlot++;
                            //     }
                            //     echo '<div class="trinket">';
                            // }
                            $allItems = "item".$b;
                            $itemId = $inhalt->info->participants[$in]->$allItems;
                            if($itemId == 0){
                                $noItemCounter += 1;
                            } else {
                                echo '<div class="item'.($b - $noItemCounter).'">';
                                if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/item/'.$itemId.'.webp')){
                                    echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/' . $itemId . '.webp" width="32" height="32" loading="lazy" class="rounded" alt="This icon represents an equipped item at the end of a game">';
                                } else if(file_exists('/hdd1/clashapp/data/misc/'.$itemId.'.webp')){
                                    echo '<img src="/clashapp/data/misc/'.$itemId.'.webp" width="32" height="32" loading="lazy" class="rounded" alt="This icon represents an equipped special ornn item at the end of the game or other exceptions">';
                                } else {
                                    echo '<img src="/clashapp/data/misc/0.webp" width="32" height="32" loading="lazy" class="rounded" alt="This icon will only be visible of neither the data dragon nor the local files contain the corresponding image">';
                                }
                                // $lastItemSlot = $b;
                                echo "</div>";
                            }
                        }
                        for($i=0; $i<$noItemCounter; $i++){
                            echo '<div class="emptySlot block w-8 h-8 rounded bg-dark opacity-40"></div>';
                        }
                        echo "</div>";
                        // Calculate own Takedowns of Kill Participation
                        $ownTakedowns = 0;
                        $ownTakedowns += $inhalt->info->participants[$in]->kills;
                        $ownTakedowns += $inhalt->info->participants[$in]->assists;
                    }
                }

                echo '</div>';
                echo '<div class="additional-info px-2" x-cloak x-show="advanced" x-transition><div class="additional-info-1 inline-flex h-8 justify-center items-center gap-1 mt-2">';
                // Display of enemy champions icon in lane
                    for($i = 0; $i < 10; $i++){
                        if (($inhalt->info->participants[$i]->teamPosition == $matchLane) && ($inhalt->info->participants[$i]->championName != $champion)){
                        echo '<div class="lane-opponent h-8 flex justify-center items-center gap-2"><span>vs. </span>';
                        $enemyChamp = $inhalt->info->participants[$i]->championName;
                        if($enemyChamp == "FiddleSticks"){$enemyChamp = "Fiddlesticks";} /** @todo One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ */
                        if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.webp')){
                            echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.webp" width="32" height="32" class="max-w-[32px]" loading="lazy" alt="This icon represents the champion '.$enemyChamp.', but tinier as a normal champion icon as it shows the enemy laner"></div>';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.webp" width="32" height="32" class="max-w-[32px]" loading="lazy" alt="This icon represents a value not being available"></div>';
                        }
                        }
                        if ($inhalt->info->participants[$i]->teamId == $teamID){
                            $totalTeamTakedowns += $inhalt->info->participants[$i]->kills;
                        }
                    }

                    echo '<div class="kill-participation">';
                        if($totalTeamTakedowns != 0){
                            echo __("KP").": ".number_format(($ownTakedowns/$totalTeamTakedowns)*100, 0). "%";
                        } else {
                            echo __("KP").": 0%";
                        }
                    echo '</div>';

                    echo '<div class="damage-dealt">';
                    echo '<img src="/clashapp/data/misc/icons/DamageDealt.webp" width="16" height="16" loading="lazy" alt="An icon of a sword clashing through a bone">';
                    echo __('Dealt').': '.$dealt;
                    echo '</div>';

                    echo '<div class="damage-tanked">';
                    echo __('Tanked').': '.$tanked;
                    echo '</div>';

                    echo '<div class="damage-healed-and-shielded">';
                    echo __("Shealed").": ".$shealed;
                    echo "</div>";

                    echo '<div class="damage-to-objectives">';
                    echo __("Objs").": ".$objs;
                    echo "</div></div>";

                    echo '<div class="additional-info-2 inline-flex h-8 justify-center items-center gap-2 mt-2">';
                    echo '<div class="vision-wards">';
                    echo '<img class="parent-trinket-icon" src="/clashapp/data/patch/'.$currentPatch.'/img/item/2055.webp" width="32" height="32" loading="lazy" class="rounded" alt="A red vision/control ward/trinket icon">';
                    echo '<div class="vision-wards-count-icon">'.$visionWards.'</div>';
                    echo "</div>";

                    echo '<div class="creepscore">';
                    echo '<div class="creepscore-count">'.__("CS").': '.$creepScore.'</div>';
                    echo "</div>";

                    echo '<div class="visionscore">';
                    echo '<div class="visionscore-count">'.__("V-Score").': '.$visionScore.'</div>';
                    echo "</div>";
                    
                    echo '<div class="matchid">';
                    echo $inhalt->metadata->matchId;
                    echo '</div>';
                    
                    echo '<div class="matchscore">';
                    echo $matchScore;
                    echo "</div></div>";


                echo '</div>';
                echo '<button type="button" class="collapsible bg-[#0e0f18] cursor-pointer h-6 w-full opacity-50 mt-4" @click="advanced = !advanced" x-text="advanced ? \'&#11165;\' : \'&#11167;\'"></button>';
                echo '</div>';
                
                $totalTeamTakedowns = 0; // Necessary to reset Kill Participation
            }
        }
    }

    echo "</div>";
    // End of Matchdetail Table & Counttext of local specific amount
    // echo "<br>Es wurden " . $count ." lokale Matchdaten gefunden<br>";
}

/** Followup function to print getMasteryScores(); returninfo
 * This function is only printing collected values, also possible to shove into profile.php
 *
 * @param array $masteryArray Inputarray of all MasteryScores
 * @param int $index Index of the masterychamp (0 = first & highest mastery champ, 1 = second, etc.)
 *
 * Returnvalue:
 * @return void N/A, just printing values to page
 */
function printMasteryInfo($masteryArray, $index){
    global $currentPatch;

    // Print image if it exists
    if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.webp')){
        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.webp" width="64" height="64" alt="A league of legends champion icon of '.$masteryArray[$index]["Filename"].'"><br>';
    }

    // Print the additional info
    echo $masteryArray[$index]["Champion"]."<br>";
    echo "<br>Mastery Level: ".$masteryArray[$index]["Lvl"]."<br>";
    echo "Points: ".$masteryArray[$index]["Points"]."<br>";
    echo "Last played: ".date('d.m.Y', $masteryArray[$index]["LastPlayed"]);
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
    $data = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json');
    $json = json_decode($data);
    foreach($json as $runetree){
        foreach($runetree->slots as $keyRunes){
            foreach($keyRunes as $runeid){
                foreach($runeid as $rune){
                    if($id == $rune->id){
                        return $rune->icon;
                    }
                }
            }
        }
    }
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
    if($currentIconID >= 1 || $currentIconID <= 28){
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
            return $runetree->icon;
        }
    }
}

/** Resolving a championid to the champions clean name
 * This function iterates through the current patches champion.json and returns the name of the champion given by id
 *
 * @param string $id The passed champion ID corresponding to Riot's data found in the champion.json
 * @var array $data Content of the champion.json containing all necessary champion data like their clear names and IDs
 *
 * Returnvalue:
 * @return string $champion->name The clean name of the champion
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
}

/** Resolving a championid to the champions filename
 * This function iterates through the current patches champion.json and returns the name of the champions image file given by id
 *
 * @param string $id The passed champion ID corresponding to Riot's data found in the champion.json
 * @var array $data Content of the champion.json containing all necessary champion data like their clear names and IDs
 *
 * Returnvalue:
 * @return string $champion->id The filename of the champion
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
    $laneReturnArray[0] = $mainLane;
    $laneReturnArray[1] = $secondaryLane;

    return $laneReturnArray;
}

/** Fetches the average value of specific attributes
 * This function retrieves the average value of a specific attribute by iterating through a users matches
 * It is possible that it gets executed multiple times for multiple attributes, therefore $attributes is an array();
 *
 * @param array $attributesArray Array of every attribute that we want to check via this function
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * @param string $puuid The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * @param string $lane The lane on where this function should fetch/get the averages from, as we collect the values for each lane separately
 * @var array $averageArray The returnvalue array but not printed
 *
 * Returnvalue:
 * @return void N/A, only direct printing to page
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
                    if(isset($matchData->info->participants[$i]->$attribute)){
                        $averageArray[$attribute] += $matchData->info->participants[$i]->$attribute;
                        $counterArray[$attribute] += 1;
                    } else if(isset($matchData->info->participants[$i]->challenges->$attribute)){
                        $averageArray[$attribute] += $matchData->info->participants[$i]->challenges->$attribute;
                        $counterArray[$attribute] += 1;
                    } else {
                        $averageArray[$attribute] += 0;
                    }
                }
            }
        }
    }
    echo '<input type="text" id="statTableSearch" onkeyup="searchStatTable()" placeholder="Statname..">';
    echo "<table class='table' id='stattable' vertical-align:top;'><tr>";
    echo "<th>Statname</th><th>My Average</th><th>Average in General</th><th>As Bottom</th><th>As Support</th><th>As Middle</th><th>As Jungle</th><th>As Top</th></tr>";

    // Count & Round to retrieve printable data
    foreach ($averageArray as $key => $arrayElement){
        echo "<tr><td class='text-center'>" . $key . ": </td>";
        if(($arrayElement / $counterArray[$key]) < 10){
            if(round($arrayElement / $counterArray[$key],2)>$averageStatsJson[$lane][$key]*2){
                echo "<td class='text-online'>";
            } else if(round($arrayElement / $counterArray[$key],2)!=0&&round($arrayElement / $counterArray[$key],2)<$averageStatsJson[$lane][$key]/2){
                echo "<td class='text-offline'>";
            } else {
                echo "<td>";
            }
            echo $averageArray[$key] = round($arrayElement / $counterArray[$key],2)."</td>";
        } else if(($arrayElement / $counterArray[$key]) < 100){
            if(round($arrayElement / $counterArray[$key],1)>$averageStatsJson[$lane][$key]*2){
                echo "<td class='text-online'>";
            } else if(round($arrayElement / $counterArray[$key],1)!=0&&round($arrayElement / $counterArray[$key],1)<$averageStatsJson[$lane][$key]/2){
                echo "<td class='text-offline'>";
            } else {
                echo "<td>";
            }
            echo $averageArray[$key] = round($arrayElement / $counterArray[$key],1)."</td>";
        } else {
            if(round($arrayElement / $counterArray[$key])>$averageStatsJson[$lane][$key]*2){
                echo "<td class='text-online'>";
            } else if(round($arrayElement / $counterArray[$key])!=0&&round($arrayElement / $counterArray[$key])<$averageStatsJson[$lane][$key]/2){
                echo "<td class='text-offline'>";
            } else {
                echo "<td>";
            }
            echo $averageArray[$key] = round($arrayElement / $counterArray[$key])."</td>";
        }

        echo "<td>".$averageStatsJson['GENERAL'][$key]."</td>";
        echo "<td>".$averageStatsJson['BOTTOM'][$key]."</td>";
        echo "<td>".$averageStatsJson['UTILITY'][$key]."</td>";
        echo "<td>".$averageStatsJson['MIDDLE'][$key]."</td>";
        echo "<td>".$averageStatsJson['JUNGLE'][$key]."</td>";
        echo "<td>".$averageStatsJson['TOP'][$key]."</td></tr>";
    }
    echo "</table>";
}

/** getHighestWinrateOrMostLossesAgainst Aliase
 *  Aliase for the two getHighestWinrateOrMostLossesAgainst function possibilities to make it clearer
 */
function getMostLossesAgainst($variant, $matchDataArray, $puuid){ getHighestWinrateOrMostLossesAgainst("mostLosses", $variant, $matchDataArray, $puuid);}
function getHighestWinrateAgainst($variant, $matchDataArray, $puuid){getHighestWinrateOrMostLossesAgainst("highestWinrate", $variant, $matchDataArray, $puuid);}

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
 * @return void N/A, just printing values to page
 */
function getHighestWinrateOrMostLossesAgainst($type, $variant, $matchDataArray, $puuid){
    $returnArray = array();
    $maxCountArray = array();
    $champArray = array();

    // Looping through all files & collecting the users data in returnArray[matchid][0]
    foreach ($matchDataArray as $matchData) {
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid){
                if($matchData->info->participants[$i]->teamPosition != ""){
                    $ourLane = $matchData->info->participants[$i]->teamPosition;
                } else if ($matchData->info->participants[$i]->individualPosition != "" && $matchData->info->participants[$i]->individualPosition != "Invalid"){
                    $ourLane = $matchData->info->participants[$i]->individualPosition;
                } else {
                    $ourLane = "N/A";
                }
                $returnArray[$matchData->metadata->matchId][] = ["lane" => $ourLane, "champion" => $matchData->info->participants[$i]->championName, "win" => $matchData->info->participants[$i]->win, "teamID" => $matchData->info->participants[$i]->teamId];
                break;
            }
        }

        // Second loop, necessary after the first one because of the if comparison below (!= $returnArray[$matchData->metadata->matchId][0]["win"])
        // Looping again through all users and collecting users data in returnArray[matchid][1-5] if in enemy team of the user (PUUID) above
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->win != $returnArray[$matchData->metadata->matchId][0]["win"] && $matchData->info->participants[$i]->teamId != $returnArray[$matchData->metadata->matchId][0]["teamID"]){
                if($matchData->info->participants[$i]->teamPosition != ""){
                    $enemyLane = $matchData->info->participants[$i]->teamPosition;
                } else if ($matchData->info->participants[$i]->individualPosition != "" && $matchData->info->participants[$i]->individualPosition != "Invalid"){
                    $enemyLane = $matchData->info->participants[$i]->individualPosition;
                } else {
                    $enemyLane = "N/A";
                }

                $returnArray[$matchData->metadata->matchId][] = ["lane" => $enemyLane, "champion" => $matchData->info->participants[$i]->championName, "win" => $matchData->info->participants[$i]->win];
            }
        }
    }

    // Get Wins, Loses, Count and Winrate on lane sorted in $champArray
    if($variant == "lane"){
        foreach ($returnArray as $Larray) {
            $lane = $Larray[0]["lane"];

            for($i = 1; $i < 6; $i++){
                if($lane == $Larray[$i]["lane"] && !$Larray[$i]["win"]){
                    $champArray[$Larray[$i]["champion"]]["win"]++;
                }else if($lane == $Larray[$i]["lane"] && $Larray[$i]["win"]){
                    $champArray[$Larray[$i]["champion"]]["lose"]++;
                }
                if($lane == $Larray[$i]["lane"]){
                    $champArray[$Larray[$i]["champion"]]["count"] = $champArray[$Larray[$i]["champion"]]["win"] + $champArray[$Larray[$i]["champion"]]["lose"];
                    $champArray[$Larray[$i]["champion"]]["winrate"] = ($champArray[$Larray[$i]["champion"]]["win"] / $champArray[$Larray[$i]["champion"]]["count"]) * 100;
                    asort($champArray[$Larray[$i]["champion"]]);
                }
            }
        }

    // Get Wins, Loses, Count and Winrate in general sorted in $champArray
    } else if ($variant == "general"){
        foreach ($returnArray as $Larray) {
            for($i = 1; $i < 6; $i++){
                if(!$Larray[$i]["win"]){
                    $champArray[$Larray[$i]["champion"]]["win"]++;
                }else if($Larray[$i]["win"]){
                    $champArray[$Larray[$i]["champion"]]["lose"]++;
                }
                $champArray[$Larray[$i]["champion"]]["count"] = $champArray[$Larray[$i]["champion"]]["win"] + $champArray[$Larray[$i]["champion"]]["lose"];
                $champArray[$Larray[$i]["champion"]]["winrate"] = ($champArray[$Larray[$i]["champion"]]["win"] / $champArray[$Larray[$i]["champion"]]["count"]) * 100;
                asort($champArray[$Larray[$i]["champion"]]);
            }
        }
    }

    // Sort descending, from highest to lowest if first element should be of type "highestWinrate"
    if($type == "highestWinrate"){
        uasort($champArray, function($a, $b){
            return $b['winrate'] <=> $a['winrate'];
        });
    // Sort ascending, from lowest to highest if first element should be of type "mostLosses"
    } else if($type == "mostLosses"){
        uasort($champArray, function($a, $b){
            return $a['winrate'] <=> $b['winrate'];
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

    // print results
    $result = number_format($champArray[array_key_first($champArray)]["winrate"], 2, ',', ' ');
    echo array_key_first($champArray) . " (" . $result . "%) in " . $champArray[array_key_first($champArray)]["count"] . " matches";
}

/** Gets the 5 most-played-with summoners
 * This function temp stores every summoner you played with in your team, sorts them and counts their occurences
 *
 * @param array $matchDataArray Inputarray of all MatchIDs of the user (PUUID) over which we iterate
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
                $mostPlayedArray[] = $matchData->info->participants[$i]->summonerName;
            }
        }
    }

    // Count, Sort & Slice to 5 to retrieve printable data
    $temp = array_count_values($mostPlayedArray);
    arsort($temp);
    $value = array_slice(array_keys($temp), 0, 5, true);
    $count = array_slice(array_values($temp), 0, 5, true);

    $returnArray[$value[0]] = $count[0];
    $returnArray[$value[1]] = $count[1];
    $returnArray[$value[2]] = $count[2];
    $returnArray[$value[3]] = $count[3];
    $returnArray[$value[4]] = $count[4];

    // echo $count[0]." mal mit ".$value[0]."<br>".$count[1]." mal mit ".$value[1]."<br>".$count[2]." mal mit ".$value[2]."<br>".$count[3]." mal mit ".$value[3]."<br>".$count[4]." mal mit ".$value[4];
    return $returnArray;
}

/** Prints the champion and info a given player by $puuid has the highest winrate with
 * This function is only printing collected values, also possible to shove into profile.php
 *
 * @param string $lane Either "TOP", "JUNGLE", "MID", "BOT" or "UTILITY", but also "GENERAL" (all lanes) possible
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
                } else if ($matchData->info->participants[$i]->individualPosition != "" && $matchData->info->participants[$i]->individualPosition != "Invalid"){
                    $myLane = $matchData->info->participants[$i]->individualPosition;
                } else {
                    $myLane = "N/A";
                }
                if($matchData->info->participants[$i]->win){
                    $highestWinrateArray[$matchData->info->participants[$i]->championName]["win"]++;
                } else {
                    $highestWinrateArray[$matchData->info->participants[$i]->championName]["lose"]++;
                }
                $count = $highestWinrateArray[$matchData->info->participants[$i]->championName]["win"]+$highestWinrateArray[$matchData->info->participants[$i]->championName]["lose"];
                $winrate = ($highestWinrateArray[$matchData->info->participants[$i]->championName]["win"]/$count)*100;
                if($lane == "GENERAL" || $lane == $myLane){
                    $highestWinrateArray[$matchData->info->participants[$i]->championName]["lane"] = $myLane;
                    $highestWinrateArray[$matchData->info->participants[$i]->championName]["count"] = $count;
                    $highestWinrateArray[$matchData->info->participants[$i]->championName]["winrate"] = $winrate;
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
    // print results
    $result = number_format($highestWinrateArray[array_key_first($highestWinrateArray)]["winrate"], 2, ',', ' ');
    if($highestWinrateArray[array_key_first($highestWinrateArray)]["count"] > 5){
        echo "Highest Winrate: (".ucfirst(strtolower($lane)).") with ". array_key_first($highestWinrateArray) . " (" . $result . "%) in " . $highestWinrateArray[array_key_first($highestWinrateArray)]["count"] . " matches<br>";
    }
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
    }
    return $returnArray;
}

/** Same as getPlayerData but with all the team info available
 * This function is collected any values of a clash team by a given teamID
 *
 * @param string $teamID The necessary ID of the team, received beforehand via if(isset($_POST['sumname']))
 * @var array teamDataArray Just the $teamOutput content but rearranged and renamed
 * @var int $httpCode Contains the returncode of the curl request (e.g. 404 not found)
 *
 * Returnvalue:
 * @return array $teamDataArray with keys "TeamID", "TournamentID", "Name", "Tag", "Icon", "Tier", "Captain" and the array itself of "Players"
 */
function getTeamByTeamID($teamID){
    if($teamID != "test"){
        global $headers;
        $teamDataArray = array();
        $logPath = '/hdd1/clashapp/data/logs/teamDownloader.log';
        
        // Curl API request block
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $teamOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // 403 Access forbidden -> Outdated API Key
        if($httpCode == "403"){
            echo "<h2>403 Forbidden TeamByTeamID</h2>";
        }
        
        // 429 Too Many Requests
        if($httpCode == "429"){
            sleep(5);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $teamOutput = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }
    } else {
        // Test Team Change
        $httpCode = "200";
        $teamOutput = file_get_contents('/hdd1/clashapp/data/misc/test.json');
    }
    
    // Collect requested values in returnarray
    $teamDataArray["Status"] = $httpCode;
    $teamDataArray["TeamID"] = json_decode($teamOutput)->id;
    $teamDataArray["TournamentID"] = json_decode($teamOutput)->tournamentId;
    $teamDataArray["Name"] = json_decode($teamOutput)->name;
    $teamDataArray["Tag"] = json_decode($teamOutput)->abbreviation;
    $teamDataArray["Icon"] = json_decode($teamOutput)->iconId;
    $teamDataArray["Tier"] = json_decode($teamOutput)->tier;
    $teamDataArray["Captain"] = json_decode($teamOutput)->captain;
    $teamDataArray["Players"] = json_decode($teamOutput, true)["players"];

    return $teamDataArray;
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
 * @return array $teamDataArray with keys "TeamID", "TournamentID", "Name", "Tag", "Icon", "Tier", "Captain" and the array itself of "Players"
 */
function showBanSelector(){
    global $currentPatch;
    $i=0;
    $championNamingData = file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
    $championNamingFile = json_decode($championNamingData);
    foreach($championNamingFile->data as $champData){
        $champName = $champData->name;
        $i++;
        $imgPath = substr($champData->image->full, 0, -4).".webp";
        $dataId = $champData->id;
        if($i<15){
            if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath)){
                echo "<div class='align-top inline-block text-center h-18 fullhd:w-[4.25rem] twok:w-[4.75rem] champ-select-champion' style='content-visibility: auto;'>";
                    echo '<div class="ban-hoverer inline-grid group" onclick="addToFile(this.parentElement);">';
                        echo '<img width="56" height="56" class="min-h-8 champ-select-icon twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11" data-id="' . $dataId . '" data-abbr="' . abbreviationFetcher($champName) . '" src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'"
                        alt="A league of legends champion icon of '.$imgPath.'">';
                        echo '<img width="56" height="56" class="min-h-8 ban-overlay twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/icon-ban.webp" alt="Prohibition overlay icon in grey">';
                        echo '<img width="56" height="56" class="min-h-8 ban-overlay-red twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-active:opacity-100" draggable="false" src="/clashapp/data/misc/icon-ban-red.webp" alt="Prohibition overlay icon in red"></div>';
                    echo "<span class='caption text-ellipsis overflow-hidden whitespace-nowrap block'>".$champName."</span>";
            echo "</div>";
            }
        } else {
            if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath)){
                echo "<div class='align-top inline-block text-center h-18 fullhd:w-[4.25rem] twok:w-[4.75rem] champ-select-champion' style='content-visibility: auto;'>";
                    echo '<div class="ban-hoverer inline-grid group" onclick="addToFile(this.parentElement);">';
                        echo '<img width="56" height="56" class="min-h-8 champ-select-icon twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11" data-id="' . $dataId . '" data-abbr="' . abbreviationFetcher($champName) . '" src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'"
                        alt="A league of legends champion icon of '.$imgPath.'" loading="lazy">';
                        echo '<img width="56" height="56" class="min-h-8 ban-overlay twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-hover:opacity-100" src="/clashapp/data/misc/icon-ban.webp" loading="lazy" alt="Prohibition overlay icon in grey">';
                        echo '<img width="56" height="56" class="min-h-8 ban-overlay-red twok:h-14 twok:w-14 fullhd:h-11 fullhd:w-11 fullhd:-mt-11 twok:-mt-14 opacity-0 group-active:opacity-100" draggable="false" src="/clashapp/data/misc/icon-ban-red.webp" loading="lazy" alt="Prohibition overlay icon in red"></div>';
                    echo "<span class='caption text-ellipsis overflow-hidden whitespace-nowrap block'>".$champName."</span>";
            echo "</div>";
            }
        }
    }
}

/** This function collects the JSON formatted data in the abbreviations.json and maps every champion to it's own abbreviations. To make the .json more readable it is allowed
 * to add spaces there, although they are filtered out below, so the javascript later on can easily split the string by the "," separator. The abbreviations are used as possible
 * alternative searchterms for a champion in a form field, in this case the #champSelector. If they are supposed to match parts of words and not only the whole word all possible
 * search terms have to be written into the abbreviations.json (like -> "abbr": "frel, frelj, freljo, freljor, freljord").
 *
 * @param string $champName The provided name of a champion, NOT the ID and has to be exactly written both as param here aswell as in the abbreviations.json
 * @var array $bbrArray This array contains the decoded (as object) contents of the abbreviations.json
 *
 * Returnvalue:
 * @return string $abbreviations is the return string that will get split by "," separator and added into the data-abbr attribute in the html code above
 */
function abbreviationFetcher($champName){
    $abbrArray = json_decode(file_get_contents('/hdd1/clashapp/data/misc/abbreviations.json'));
    foreach($abbrArray as $champFileName => $element){
        if($champFileName === $champName){
            $abbreviations = str_replace('_', ' ', str_replace(' ', '', $element->abbr));
        }
    }
  return $abbreviations;
}

function timeDiffToText($timestamp){
    switch ($timestamp){
        case $timestamp < strtotime("-1 year"): // Über ein Jahr her
            return "over a year ago";
            break;
        case $timestamp < strtotime("-6 months"): // Über 6 Monate unter 1 Jahr
            return "over 6 months ago";
            break;
        case $timestamp < strtotime("-3 months"): // Über 3 Monate unter 6 Monate
            return "over 3 months ago";
            break;
        case $timestamp < strtotime("-1 months"): // Über einen Monat unter 3 Monate
            return "over a month ago";
            break;
        case $timestamp < strtotime("-2 weeks"): // Über zwei Wochen unter 1 Monat
            return "over two weeks ago";
            break;
        case $timestamp > strtotime("-2 weeks"): // Unter zwei Wochen her
            return "under two weeks ago";
            break;
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

    foreach($matchData as $inhalt){
        foreach($inhalt->info->participants as $player){
            if(in_array($player->summonerId, $sumidArray) && $player->win == true){
                $teamId = $player->teamId;
            } else if(in_array($player->summonerId, $sumidArray) && $player->win == false) {
                $teamId = $player->teamId;
                foreach($inhalt->info->participants as $enemy){
                    if($enemy->teamId != $teamId){ // Select only enemy team
                        $tempArray[$enemy->summonerId]["Champion"] = $enemy->championName;
                        $tempArray[$enemy->summonerId]["Matchscore"] = implode("",getMatchRanking(array($inhalt->metadata->matchId), $matchData, $enemy->summonerId));
                    }
                }
                $matchscoreArray[$inhalt->metadata->matchId] = $tempArray;
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
                $sortedMasteryArray[$key1]["TotalTeamPoints"] += str_replace(',', '.', $champData2["Points"]);
                $banExplainArray[$champData1["Champion"]]["TotalTeamPoints"]["Value"] += str_replace(',', '.', $champData2["Points"]);
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
                                                $banExplainArray[$championData["Champion"]]["MatchingLanersPrio"]["Lanes"][] = $playerLanesTeamArray[$comparePlayer1]["Mainrole"];
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
    foreach($sortedMasteryArray as $key => $championData){
        if(isset($sortedMasteryArray[$key]["AverageMatchScore"])){
            $sortedMasteryArray[$key]["AverageMatchScore"] = number_format(array_sum($sortedMasteryArray[$key]["AverageMatchScore"])/count($sortedMasteryArray[$key]["AverageMatchScore"]), 2, ".", "");
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
                    $banExplainArray[$suggestedBan["Champion"]]["Points"]["Value"] = $suggestedBan["Points"];
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
    array_multisort(array_column($banExplainArray, "FinalScore"), SORT_DESC, $banExplainArray);

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

    foreach($rankData as $rankedQueue){ // Sorted after rank distribution (https://www.leagueofgraphs.com/de/rankings/rank-distribution) 
        if($rankedQueue["Queue"] == "RANKED_SOLO_5x5" || $rankedQueue["Queue"] == "RANKED_FLEX_SR" ){
            if($rankedQueue["Tier"] == "SILVER" && $rankVal < 3){
                $rankVal = 3;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "GOLD" && $rankVal < 4){
                $rankVal = 4;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "BRONZE" && $rankVal < 2){
                $rankVal = 2;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "PLATINUM" && $rankVal < 5){
                $rankVal = 5;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "IRON" && $rankVal < 1){
                $rankVal = 1;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "DIAMOND" && $rankVal < 6){
                $rankVal = 6;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "MASTER" && $rankVal < 7){
                $rankVal = 7;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
            } else if($rankedQueue["Tier"] == "GRANDMASTER" && $rankVal < 8){
                $rankVal = 8;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
            } else if($rankedQueue["Tier"] == "CHALLENGER" && $rankVal < 9){
                $rankVal = 9;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
            }
        }
    }
    if($rankVal != 0){
        return array("Type" => "Rank", "HighestRank" => $highestRank, "HighEloLP" => $highEloLP, "RankNumber" => $rankNumber);
    } else {
        if($playerData["Level"] < 30){
            $levelFileName = "001";
        } else if($playerData["Level"] < 50){
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
 * 
 * function getRankColor($currentRank){
 *     switch ($currentRank){ // Sorted after rank distribution (https://www.leagueofgraphs.com/de/rankings/rank-distribution)
 *         case "SILVER":
 *             return "99a0b5";
 *         case "GOLD":
 *             return "d79c5d";
 *         case "BRONZE":
 *             return "cd8d7f";
 *         case "PLATINUM":
 *             return "23af88";
 *         case "IRON":
 *             return "392b28";
 *         case "DIAMOND":
 *             return "617ecb";
 *         case "MASTER":
 *             return "b160f3";
 *         case "GRANDMASTER":
 *             return "cd423a";
 *         case "CHALLENGER":
 *             return "52cfff";
 *     }
 * }
 */

/** This function simply returns a color code corresponding to a textual rank input, e.g. "PLATINUM"
 * @param $currentRank The current rank as capslocked string
 * 
 * Returnvalue:
 * @return string A hexadecimal color code
 */
function getMasteryColor($masteryPoints){
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
}

/** Always active "function" to collect the teamID of a given Summoner Name
 * This function calls an API request as soon as the sumname gets posted from the team.php
 * through the javascript sanitize(text) function.
 *
 * As we need none of the info received below except for the teamId to proceed, only that one is echo'd back to javascript
 * and back to the team.php
 * There we then open a new page with the teamId in it's URL and grab it through a $_GET to proceed and execute functions
 * where the teamID is needed. If there is no data found we instead redirect to a 404 page, hence echo "404".
 *
 * No other data has to be saved or transferred as all the data we get from this API request is also received during the
 * following steps and e.g. the getTeamByTeamID($teamID) function.
 * 
 * @var string $playerName A summoners ingame name, fetched by the POST (last part of URL)
 * @var array $playerData Either the API requested playerdata or the locally stored one, used to retrieve the SumID
 *
 * Returnvalue:
 * @return void None, echo'ing teamID back to javascript to open new windows with it appended
 */
if(isset($_POST['sumname'])){
    global $headers;
    $playerName = preg_replace('/\s+/', '+', $_POST['sumname']);
    $playerData = getPlayerData("name",$playerName);

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/players/by-summoner/" . $playerData["SumID"]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $clashOutput = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(5);
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/players/by-summoner/" . $playerData["SumID"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $clashOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // Decode and echo returned data, if not existent send to 404 page
    $clashData = json_decode($clashOutput, true);
    if(isset($clashData[0]["teamId"])){
        echo $clashData[0]["teamId"];
    } else {
        echo "404";
    }
}
?>