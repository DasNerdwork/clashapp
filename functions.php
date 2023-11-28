<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/hdd1/clashapp/mongo-db.php';

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

// putenv('API_KEY=RGAPI-9b672bbc-d75e-49e6-a112-4c2660f4d5de'); // TODO: FIXME: TODO: FIXME: --- ONLY FOR TESTING --- TODO: FIXME: TODO: FIXME:
$apiKey = getenv('API_KEY');
$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");
$counter = 0;
$headers = array(
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
    "Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7",
    "Accept-Charset: application/x-www-form-urlencoded; charset=UTF-8",
    "Origin: https://clashscout.com/",
    "X-Riot-Token: ".$apiKey
);
$apiRequests = array(
    "total" => 0,
    "getPlayerData" => 0,
    "getMatchIDs" => 0,
    "getMasteryScores" => 0,
    "getCurrentRank" => 0,
    "downloadMatchesByID" => 0,
    "getTeamByTeamID" => 0,
    "postSubmit" => 0
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
    global $headers, $apiRequests;
    $playerDataArray = array();

    switch ($type) {
        case "riot-id":
            $id = str_replace("#","/",$id);
            $requestUrlVar = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-riot-id/";
            break;
        case "puuid":
            $requestUrlVar = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-puuid/";
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
    $output = curl_exec($ch); $apiRequests["getPlayerData"]++;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 403 Access forbidden -> Outdated API Key
    if($httpCode == "403"){
        echo "<h2>403 Forbidden GetPlayerData</h2>";
        // print_r(curl_getinfo($ch));
    }

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(10);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrlVar . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch); $apiRequests["getPlayerData"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    if($httpCode == "200" && ($type == "riot-id" || $type == "puuid")){
        $type == "puuid" ? $gameName = json_decode($output)->gameName : $gameName = "";
        $type == "puuid" ? $tag = json_decode($output)->tagLine : $tag = "";
        $requestUrlVar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrlVar . json_decode($output)->puuid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch); $apiRequests["getPlayerData"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // fetch puuid with sumid and then playername (because standard name is deprecated -> riotid)
    if($httpCode == "200" && $type == "sumid") {
        $requestUrlVar = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-puuid/";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrlVar . json_decode($output)->puuid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $riotIdSumidOutput = curl_exec($ch); $apiRequests["getPlayerData"]++;
        $gameName = json_decode($riotIdSumidOutput)->gameName;
        $tag = json_decode($riotIdSumidOutput)->tagLine;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // Collect requested values in returnarray
    $playerDataArray["Icon"] = json_decode($output)->profileIconId;
    $playerDataArray["Name"] = json_decode($output)->name;
    if($type == "riot-id"){
        $playerDataArray["GameName"] = explode("/", $id)[0];
        $playerDataArray["Tag"] = explode("/", $id)[1];
    } else {
        $playerDataArray["GameName"] = $gameName;
        $playerDataArray["Tag"] = $tag;
    }
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
 * @param string $puuid The players encrypted PUUID necessary to perform the API request
 * @var array $masteryDataArray The temporary array to fetch a single champions mastery data
 * @var array $output Contains the output of the curl request as string which we later convert using json_decode
 * @var string $httpCode Contains the returncode of the curl request (e.g. 404 not found)
 *
 * Returnvalue:
 * @return array $masteryReturnArray The full return array including all single champion arrays
 */
function getMasteryScores($puuid){
    global $headers, $apiRequests;
    $masteryDataArray = array();
    $masteryReturnArray = array();

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-puuid/".$puuid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch); $apiRequests["getMasteryScores"]++;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 403 Forbidden
    if($httpCode == "403"){
        echo "<h2>403 Forbidden MasteryScores</h2>";
    }
    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(10);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$puuid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch); $apiRequests["getMasteryScores"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
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
    global $headers, $apiRequests;
    $rankDataArray = array();
    $rankReturnArray = array();

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch); $apiRequests["getCurrentRank"]++;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

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
        $output = curl_exec($ch); $apiRequests["getCurrentRank"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // Resolving return values
    foreach(json_decode($output, true) as $requestArray){
        if($requestArray["queueType"] == "RANKED_SOLO_5x5" || $requestArray["queueType"] == "RANKED_FLEX_SR"){
            $rankDataArray["Queue"] = $requestArray["queueType"];
            $rankDataArray["Tier"] = $requestArray["tier"];
            $rankDataArray["Rank"] = $requestArray["rank"];
            $rankDataArray["LP"] = $requestArray["leaguePoints"];
            $rankDataArray["Wins"] = $requestArray["wins"];
            $rankDataArray["Losses"] = $requestArray["losses"];
            $rankReturnArray[] = $rankDataArray;
        }
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
    global $headers, $apiRequests;
    $soloduoIDArray = array();
    $flexIDArray = array();
    $clashIDArray = array();
    $gameType = "ranked";
    $start = 0;
    $matchCount = "100";

    while ($start < $maxMatchIDs) {
        // If next iterations would exceed the max
        if(($start + 100) > $maxMatchIDs){
            $matchCount = 100 - (($start + 100) - $maxMatchIDs);
        }

        // Curl API request block for clash matches
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=700&type=normal&start=" . $start . "&count=" . $matchCount);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $clashidOutput = curl_exec($ch); $apiRequests["getMatchIDs"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 429 Too Many Requests
        if($httpCode == "429"){ /** TODO: fetch function with switch to handle and log every httpcode error */
            sleep(5);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=700&type=normal&start=".$start."&count=" . $matchCount);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $clashidOutput = curl_exec($ch); $apiRequests["getMatchIDs"]++;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        // -------------------------------------------------------------------------------------------------------------------------------------------------------------------

        // Curl API request for flex matches
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=440&type=" . $gameType . "&start=" . $start . "&count=" . $matchCount);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $flexidOutput = curl_exec($ch); $apiRequests["getMatchIDs"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 429 Too Many Requests
        if($httpCode == "429"){ /** TODO: fetch function with switch to handle and log every httpcode error */
            sleep(5);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=440&type=" . $gameType . "&start=".$start."&count=" . $matchCount);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $flexidOutput = curl_exec($ch); $apiRequests["getMatchIDs"]++;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "<script>matchIdCalls++;</script>";
        }

        // -------------------------------------------------------------------------------------------------------------------------------------------------------------------

        // Curl API request for solo duo matches
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=420&type=" . $gameType . "&start=" . $start . "&count=" . $matchCount);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $soloduoidOutput = curl_exec($ch); $apiRequests["getMatchIDs"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 429 Too Many Requests
        if($httpCode == "429"){ /** TODO: fetch function with switch to handle and log every httpcode error */
            sleep(5);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=420&type=" . $gameType . "&start=".$start."&count=" . $matchCount);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $soloduoidOutput = curl_exec($ch); $apiRequests["getMatchIDs"]++;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo "<script>matchIdCalls++;</script>";
        }

        // Add each matchID to return array
        foreach (json_decode($soloduoidOutput) as $soloduoMatch) {
            $soloduoIDArray[] = $soloduoMatch;
        }
        foreach (json_decode($flexidOutput) as $flexMatch) {
            $flexIDArray[] = $flexMatch;
        }
        foreach (json_decode($clashidOutput) as $clashMatch) {
            $clashIDArray[] = $clashMatch;
        }
        $start += 100;
    }

    // Merge and sort clash matchids and ranked match ids
    $returnArray = array_merge($flexIDArray, $soloduoIDArray, $clashIDArray);
    rsort($returnArray);
    $returnArray = array_slice($returnArray,0 ,$maxMatchIDs);

    return $returnArray;
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
 * @return boolean N/A, file saving & logging instead
 */
function downloadMatchesByID($matchids, $username = null){
    $mdb = new MongoDBHelper();
    global $headers, $counter, $apiRequests;
    $logPath = '/hdd1/clashapp/data/logs/matchDownloader.log';

    foreach($matchids as $matchid){

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
        if(!$mdb->findDocumentByField("matches", 'metadata.matchId', $matchid)["success"]){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $matchOutput = curl_exec($ch); $apiRequests["downloadMatchesByID"]++;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);


            // 429 Too Many Requests -> HITTING LOWER RATE LIMIT OF --- 20 requests every 1 seconds ---
            if($httpCode == "429"){
                sleep(1);
                $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                $limit = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Lower Rate limit got exceeded -> Now sleeping for 1 second - Status: " . $httpCode . " Too Many Requests";
                file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
                curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $matchOutput = curl_exec($ch); $apiRequests["downloadMatchesByID"]++;
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                // 429 Too Many Requests -> HITTING HIGHER RATE LIMIT OF --- 100 requests every 2 minutes ---
                if($httpCode == "429"){
                    sleep(10);
                    $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                    $limit = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Upper Rate limit got exceeded -> Now sleeping for 10 seconds - Status: " . $httpCode . " Too Many Requests";
                    file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
                    curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $matchOutput = curl_exec($ch); $apiRequests["downloadMatchesByID"]++;
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                }
            }

            // Write to log and save the matchid.json, else skip
            clearstatcache(true, $logPath);
            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $answer = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: Downloading new matchdata from \"" . $username . "\" via " . $matchid . ".json - Status: " . $httpCode;
            file_put_contents($logPath, $answer.PHP_EOL , FILE_APPEND | LOCK_EX);
            if($httpCode == "200"){
                // if(($matchOutput->info->gameDuration != "0")){ // && (isset($matchOutput->info->participants[0]->killParticipation)
                    $mdb->insertDocument('matches', json_decode($matchOutput, true));
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
    }
    // return array("Status" => "Success", "ErrorFile" => $errorFile);
    return true;
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
    $mdb = new MongoDBHelper();
    $startMemory = memory_get_usage();
    $matchData = array();
    global $cleanAttributeArray;

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
 * @return string|void Depending on switch case as seen below, but string sentence
 */
function secondsToTime($seconds) {
    switch ($seconds) {
        case ($seconds<0):
            return __("1 minute ago");
        case ($seconds==0):
            return __("1 minute ago");
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
        case ($seconds>=172800 && $seconds<2592000):
            return sprintf(__("%d days ago"), floor($seconds / 86400));
        case ($seconds>=2592000 && $seconds<5260000):
            return __("1 month ago");
        case ($seconds>=5260000 && $seconds<31104000):
            return sprintf(__("%d months ago"), floor($seconds / 2592000));
        case ($seconds>=31104000 && $seconds<62208000):
            return __("1 year ago");
        case ($seconds>=62208000):
            return sprintf(__("%d years ago"), floor($seconds / 31104000));
        }
    return;
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
 * @return string N/A, displaying on page via table
 *
 * @todo possibility to make more beautiful and/or write a testcase?
 */
function printTeamMatchDetailsByPUUID($matchIDArray, $puuid, $matchRankingArray){
    $mdb = new MongoDBHelper();
    global $currentPatch;
    global $currentTimestamp;
    $count = 0;
    $totalTeamTakedowns = 0;
    $returnString = "";

    // Initiating Matchdetail Table
    $returnString .= "<button type='button' class='collapsible bg-dark cursor-pointer h-6 w-full'
            @click='open = !open'
            x-text='open ? \"&#11167;\" : \"&#11165;\" '></button>";
    $returnString .= "<div class='smooth-transition w-full overflow-hidden twok:min-h-[2300px] fullhd:min-h-[1868.75px]' x-show='open' x-transition x-cloak>";
    foreach ($matchIDArray as $i => $matchIDJSON) {
        if(isset($mdb->findDocumentByField("matches", 'metadata.matchId', $matchIDJSON)["document"])){
            $inhalt = $mdb->findDocumentByField("matches", 'metadata.matchId', $matchIDJSON)["document"];
        } else {
            // print_r($mdb->findDocumentByField("matches", 'metadata.matchId', $matchIDJSON));
            // echo "<br>".$matchIDJSON."<br>";
            return "";
        }
        if(isset($inhalt->metadata->participants) && $inhalt->info->gameDuration != 0) {
            if(in_array($puuid, (array) $inhalt->metadata->participants)){
                $count++;
                for($in = 0; $in < 10; $in++){
                    if($inhalt->info->participants[$in]->puuid == $puuid) {
                        $teamID = $inhalt->info->participants[$in]->teamId;
                        if($inhalt->info->participants[$in]->gameEndedInEarlySurrender){
                            $returnString .= '<div class="w-full bg-gray-800 border-b border-[4px] border-dark" x-data="{ advanced: false }" @page-advanced="advanced = true" style="content-visibility: auto;" data-matchid='.$inhalt->metadata->matchId.'>';
                        } elseif ($inhalt->info->participants[$in]->win == false){
                            $returnString .= '<div class="w-full bg-lose border-b border-[4px] border-dark" x-data="{ advanced: false }" @page-advanced="advanced = true" style="content-visibility: auto;" data-matchid='.$inhalt->metadata->matchId.'>';
                        } else {
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
                                $returnString .= gmdate("i:s", $inhalt->info->gameDuration)."</span>";
                                $returnString .= "</div>";

                                // $returnString .= "<div class='match-id hidden'>".$matchIDJSON."</div>";

                                $returnString .= '<div id="match-time-ago" class="ml-auto">';

                                // Display when the game date was, if > than 23h -> day format, if > than 30d -> month format, etc.
                                $returnString .= "<span>".secondsToTime(strtotime('now')-intdiv($inhalt->info->gameEndTimestamp, 1000))."</span></div>";
                                $returnString .= '</div>';

                                // Display of the played champions icon
                                $returnString .= '<div class="champion-data flex gap-2 twok:h-[68px] fullhd:h-[56px] justify-between px-2"><div class="champion-data-left inline-flex gap-2"><div class="champion-icon">';
                                if ($inhalt->info->participants[$in])
                                $champion = $inhalt->info->participants[$in]->championName;
                                if($champion == "FiddleSticks"){$champion = "Fiddlesticks";} /** TODO: One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ */
                                if($champion == "Kayn"){
                                    if($inhalt->info->participants[$in]->championTransform == "1"){
                                        if(file_exists('/hdd1/clashapp/data/misc/webp/kayn_rhaast_darkin.webp')){
                                            $returnString .= '<img src="/clashapp/data/misc/webp/kayn_rhaast_darkin.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        }
                                    } else if($inhalt->info->participants[$in]->championTransform == "2") {
                                        if(file_exists('/hdd1/clashapp/data/misc/webp/kayn_shadow_assassin.webp')){
                                            $returnString .= '<img src="/clashapp/data/misc/webp/kayn_shadow_assassin.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        }
                                    } else {
                                        if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp')){
                                            $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                            $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                        }
                                    }
                                } else {
                                    if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp')){
                                        $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative z-0 rounded" loading="lazy" alt="Main icon of the league of legends champion '.$champion.'">';
                                        $returnString .= '<img src="/clashapp/data/misc/LevelAndLaneOverlay.webp" width="68" height="68" class="twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] flex align-middle relative twok:bottom-16 fullhd:bottom-[3.5rem] -mb-16 z-10 rounded" loading="lazy" alt="Overlay image as background for level and lane icon">';
                                    } else {
                                        $returnString .= '<img src="/clashapp/data/misc/na.webp" width="68" height="68" class="align-middle twok:max-w-[68px] twok:min-w-[68px] fullhd:max-w-[56px] fullhd:min-w-[56px] rounded" loading="lazy" alt="This icon represents a value not being available">';
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
                                

                        // Display of champion level at end of game
                        $returnString .= '<div class="champion-level flex relative w-4 h-4 max-w-[16px] min-w-[16px] z-20 -ml-4 twok:bottom-[17px] twok:-right-[17px] twok:text-[13px] fullhd:bottom-[8px] fullhd:-right-[15px] fullhd:text-[12px] justify-center items-center">';
                        $returnString .= $inhalt->info->participants[$in]->champLevel;
                        $returnString .= '</div>';

                        // Display of played Position
                        $returnString .= "<div class='champion-lane flex relative w-4 h-4 twok:max-w-[16px] twok:min-w-[16px] z-20 -ml-4 twok:bottom-[33px] twok:-right-[66px] fullhd:max-w-[14px] fullhd:min-w-[14px] fullhd:bottom-[25px] fullhd:-right-[56px] justify-center items-center'>";
                        $matchLane = $inhalt->info->participants[$in]->teamPosition;
                        if(file_exists('/hdd1/clashapp/data/misc/lanes/'.$matchLane.'.webp')){
                            $returnString .= '<img src="/clashapp/data/misc/lanes/'.$matchLane.'.webp" width="16" height="16"  loading="lazy" class="max-w-[16px] min-w-[16px] saturate-0 brightness-150" alt="Icon of a league of legends position for '.$matchLane.'">';
                        }
                        $returnString .= "</div>";
                        $returnString .= "</div>";

                        // Display summoner spells
                        $returnString .= '<div class="summoner-spells grid grid-rows-2 gap-1 twok:max-w-[32px] fullhd:max-w-[26px]">';
                        $summoner1Id = $inhalt->info->participants[$in]->summoner1Id;
                        $summoner2Id = $inhalt->info->participants[$in]->summoner2Id;
                        if(file_exists('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).".webp")){
                            $returnString .= '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).'.webp" width="32" height="32" class="rounded" loading="lazy" alt="Icon of a players first selected summoner spell">';
                        }
                        if(file_exists('/hdd1/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).".webp")){
                            $returnString .= '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).'.webp" width="32" height="32" class="rounded" loading="lazy" alt="Icon of a players second selected summoner spell">';
                        }
                        $returnString .= "</div>";



                        // Display of the equipped keyrune + secondary tree
                        $returnString .= '<div class="rune-container grid grid-cols-2 grid-rows-2 gap-y-1">';
                        $returnString .= "<div class='flex col-span-2 row-span-1 justify-start items-center gap-1'>";
                        $keyRune = $inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk;
                        $secRune = $inhalt->info->participants[$in]->perks->styles[1]->style;
                        if(file_exists('/hdd1/clashapp/data/patch/img/'.substr(runeIconFetcher($keyRune), 0, -4).'.webp')){
                            $returnString .= '<img src="/clashapp/data/patch/img/'.substr(runeIconFetcher($keyRune), 0, -4).'.webp" width="32" height="32" loading="lazy" alt="Icon of a players first selected rune" class="fullhd:max-w-[26px] twok:max-w-[32px]">';
                        } else {
                            $returnString .= '<img src="/clashapp/data/misc/na.webp" width="32" height="32" loading="lazy" alt="This icon represents a value not being available" class="fullhd:max-w-[26px] twok:max-w-[32px]">';
                        }
                        if(file_exists('/hdd1/clashapp/data/patch/img/'.substr(runeTreeIconFetcher($secRune), 0, -4).'.webp')){
                            $returnString .= '<img src="/clashapp/data/patch/img/'.substr(runeTreeIconFetcher($secRune), 0, -4).'.webp" height="18" width="18" class="m-auto" loading="lazy" alt="Icon of a players second selected rune" class="fullhd:max-w-[14.625px] twok:max-w-[18px]">';
                        } else {
                            $returnString .= '<img src="/clashapp/data/misc/na.webp" width="18" height="18" loading="lazy" alt="This icon represents a value not being available" class="fullhd:max-w-[14.625px] twok:max-w-[18px]">';
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
                        if($deaths != 0){
                            $returnString .= __("KDA").": ".number_format(($kills+$assists)/$deaths, 2)."</div>";
                        } else {
                            $returnString .= __("KDA").": ".number_format(($kills+$assists)/1, 2)."</div>";
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
                            //         $returnString .= '<img src="/clashapp/data/misc/0.webp" width="32" loading="lazy">';
                            //         $returnString .= '</div>';
                            //         $lastItemSlot++;
                            //     }
                            //     $returnString .= '<div class="trinket">';
                            // }
                            $allItems = "item".$b;
                            $itemId = $inhalt->info->participants[$in]->$allItems;
                            if($itemId == 0){
                                $noItemCounter += 1;
                            } else {
                                $returnString .= '<div class="item'.($b - $noItemCounter).'">';
                                if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/item/'.$itemId.'.webp')){
                                    $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/' . $itemId . '.webp" width="32" height="32" loading="lazy" class="rounded" alt="This icon represents an equipped item at the end of a game">';
                                } else if(file_exists('/hdd1/clashapp/data/misc/'.$itemId.'.webp')){
                                    $returnString .= '<img src="/clashapp/data/misc/'.$itemId.'.webp" width="32" height="32" loading="lazy" class="rounded" alt="This icon represents an equipped special ornn item at the end of the game or other exceptions">';
                                } else {
                                    $returnString .= '<img src="/clashapp/data/misc/0.webp" width="32" height="32" loading="lazy" class="rounded" alt="This icon will only be visible of neither the data dragon nor the local files contain the corresponding image">';
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
                        if($enemyChamp == "FiddleSticks"){$enemyChamp = "Fiddlesticks";} /** @todo One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ */
                        if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.webp')){
                            $returnString .= '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.webp" width="32" height="32" class="twok:max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="This icon represents the champion '.$enemyChamp.', but tinier as a normal champion icon as it shows the enemy laner"></div>';
                        } else {
                            $returnString .= '<img src="/clashapp/data/misc/na.webp" width="32" height="32" class="twok:max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="This icon represents a value not being available"></div>';
                        }
                        }
                        if ($inhalt->info->participants[$i]->teamId == $teamID){
                            $totalTeamTakedowns += $inhalt->info->participants[$i]->kills;
                        }
                    }

                    $returnString .= '<div class="damage-dealt col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/Dealt.webp" width="24" height="26" class="twok:max-w-[24px] fullhd:max-w-[19.5px]" loading="lazy" alt="An icon of a sword clashing through a bone">';
                    $returnString .= '<span>'.$dealt.'</span>';
                    $returnString .= '</div>';


                    $returnString .= '<div class="kill-participation col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/KillParticipation.webp" width="32" height="26" class="max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="An icon of two swords clashing with each other">';
                        if($totalTeamTakedowns != 0){
                            $returnString .= "<span>".number_format(($ownTakedowns/$totalTeamTakedowns)*100, 0). "%</span>";
                        } else {
                            $returnString .= "<span>0%</span>";
                        }
                    $returnString .= '</div>';

                    $returnString .= '<div class="visionscore col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/VisionScore.webp" width="36" height="23" class="max-w-[36px] fullhd:max-w-[29.25px]" loading="lazy" alt="An icon of a vision ward from League of Legends">';
                    $returnString .= '<span>'.$visionScore.'</span>';
                    $returnString .= "</div>";

                    $returnString .= '<div class="col-span-1 row-span-2 h-full flex justify-center items-center">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/Turret.webp" width="36" height="76" class="twok:max-w-[36px] fullhd:max-w-[29.25px]" loading="lazy" alt="An icon of a tower from League of Legends">';
                    $returnString .= '</div>';

                    $returnString .= '<div class="damage-to-objectives col-span-1 row-span-1 h-full flex justify-center items-center">';
                    $returnString .= __("Objs").": ".$objs;
                    $returnString .= "</div>";

                    $returnString .= '<div class="creepscore col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/Creepscore.webp" width="32" height="19" class="twok:max-w-[32px] fullhd:max-w-[26px]" loading="lazy" alt="An icon of two coins">';
                    $returnString .= '<span>'.$creepScore.'</span>';
                    $returnString .= "</div>";

                    $returnString .= '<div class="damage-tanked col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/Tanked.webp" width="20.5" height="26" class="twok:max-w-[20.5px] fullhd:max-w-[16.65625px]" loading="lazy" alt="An icon of a shield with two cracks">';
                    $returnString .= '<span>'.$tanked.'</span>';
                    $returnString .= '</div>';

                    $returnString .= '<div class="damage-healed-and-shielded col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/Shealed.webp" width="27" height="28" class="twok:max-w-[27px] fullhd:max-w-[21.9375px]" loading="lazy" alt="An icon of a plus symbol converging into a shield">';
                    $returnString .= '<span>'.$shealed.'</span>';
                    $returnString .= "</div>";

                    $returnString .= '<div class="control-wards col-span-1 row-span-1 h-full flex justify-start items-center gap-1">';
                    $returnString .= '<img src="/clashapp/data/misc/icons/ControlWard.webp" width="36" height="25" class="twok:max-w-[36px] fullhd:max-w-[29.25px]" loading="lazy" alt="An icon of a control ward from League of Legends">';
                    $returnString .= '<span>'.$visionWards.'</span>';
                    $returnString .= "</div>";

                    $returnString .= '<div class="turret-platings col-span-1 row-span-1 h-full flex justify-center items-center">';
                    $returnString .= __("Platings").': '.$turretPlatings;
                    $returnString .= "</div>";

                    $returnString .= '<div class="match-tag-container col-span-6 row-span-1 h-full flex justify-start items-center gap-4 ">';
                    // $returnString .= '<div class="list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help">Tag 1</div>';
                    // $returnString .= '<div class="list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help">Testtag 2</div>';
                    // $returnString .= '<div class="list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help">Verspeisen Sie Arsch?</div>';
                    $returnString .= "</div>";

                    $returnString .= '</div></div>';
                    $returnString .= '<button type="button" class="collapsible bg-[#0e0f18] cursor-pointer h-6 w-full opacity-50 mt-4" @click="advanced = !advanced" x-text="advanced ? \'&#11165;\' : \'&#11167;\'"></button>';
                    $returnString .= '</div>';

                $totalTeamTakedowns = 0; // Necessary to reset Kill Participation
            }
        }
    }

    $returnString .= "</div>";
    return $returnString;
    // End of Matchdetail Table & Counttext of local specific amount
    // $returnString += "<br>Es wurden " . $count ." lokale Matchdaten gefunden<br>";
}

/** Followup function to print getMasteryScores(); returninfo
 * This function is only printing collected values, also possible to shove into profile.php
 *
 * @param array $masteryArray Inputarray of all MasteryScores
 * @param int $index Index of the masterychamp (0 = first & highest mastery champ, 1 = second, etc.)
 *
 * Returnvalue:
 * @return void N/A, just printing values to page TODO: Write possible testcase for this
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
    return;
}

/** Fetching rune icon ID to image path
 * This function iterates through the current patches runesReforged.json and returns the folder of the rune icons
 *
 * @param string $id The passed rune ID corresponding to Riot's data found in the runesReforged.json
 * @var array $data Content of the runesReforged.json containing any image path for any rune ID
 *
 * Returnvalue:
 * @return string|void $rune->icon Path of Iconimage
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
                        return $rune->icon;
                    }
                }
            }
        }
    }
    return;
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
 * @return string|void $summoner->id Path of Iconimage
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
    return;
}

/** Fetching runetree icon ID to image path
 * This function iterates through the current patches runesReforged.json and returns the folder of the runetree icons
 *
 * @param string $id The passed runetree ID corresponding to Riot's data found in the runesReforged.json
 * @var array $data Content of the runesReforged.json containing any image path for any rune icon ID
 *
 * Returnvalue:
 * @return string|void $runetree->icon Path of Iconimage
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
    return;
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
 * @return array $mostCommonReturn Array containing the sorted most common of specific attributes TODO: Testcases for this function and/or implement from profile into team
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


/**
 * This function fetches the tags of a player generated by comparing them with our calculated average stats
 * @param array $matchDaten The compacted information of all matches of a user in a single array (performance reasons)
 * @param string $puuid The personal users ID set by Riot Games and fetched either from the players own json file or via an API request
 * @param array $playerLanes The two most played lanes of a player
 *
 * Returnvalue:
 * @return array $tagReturnArray An array containing all fitting tags of a player
 */
function getPlayerTags($matchDaten, $puuid, $playerLanes){
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
                $generalKey[$key] += $value;
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
 * @var array $averageArray The returnvalue array but not printed
 *
 * Returnvalue:
 * @return void N/A, only direct printing to page TODO: testfunction for printing function
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

        echo "<td>".$averageStatsJson['FILL'][$key]."</td>";
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
                if($lane == "FILL" || $lane == $myLane){
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
                $returnArray[$matchID] = "N/A";
            }
        }
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
        global $headers, $apiRequests;
        $teamDataArray = array();
        $logPath = '/hdd1/clashapp/data/logs/teamDownloader.log';

        // Curl API request block
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $teamOutput = curl_exec($ch); $apiRequests["getTeamByTeamID"]++;
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
            $teamOutput = curl_exec($ch); $apiRequests["getTeamByTeamID"]++;
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
 * @return void $teamDataArray with keys "TeamID", "TournamentID", "Name", "Tag", "Icon", "Tier", "Captain" and the array itself of "Players"
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
            } else if($rankedQueue["Tier"] == "EMERALD" && $rankVal < 6){
                $rankVal = 6;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "IRON" && $rankVal < 1){
                $rankVal = 1;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "DIAMOND" && $rankVal < 7){
                $rankVal = 7;
                $rankNumber = $rankedQueue["Rank"];
                $highestRank = $rankedQueue["Tier"];
            } else if($rankedQueue["Tier"] == "MASTER" && $rankVal < 8){
                $rankVal = 8;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
            } else if($rankedQueue["Tier"] == "GRANDMASTER" && $rankVal < 9){
                $rankVal = 9;
                $rankNumber = "";
                $highestRank = $rankedQueue["Tier"];
                $highEloLP = $rankedQueue["LP"];
            } else if($rankedQueue["Tier"] == "CHALLENGER" && $rankVal < 10){
                $rankVal = 10;
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
 * @return string|void A hexadecimal color code
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
    } else {
        return;
    }
}

function generateCSRFToken() {
    $token = bin2hex(random_bytes(32)); // Generiere einen zufälligen Token-Wert
    $_SESSION['csrf_token'] = $token; // Speichere den Token in der Sitzungsvariablen
    return $token; // Gib den Token-Wert zurück
}

function calculateSmurfProbability($playerData, $rankData, $masteryData) {
    $resultArray = array();

    // Detect suspicion about last profile change (the longer no change the higher the suspicion)
    $timestamp = intval($playerData["LastChange"] / 1000); // summoner name change, summoner level change, or profile icon change will trigger a reset of this timestamp/suspicion
    switch ($timestamp){
        case $timestamp < strtotime("-1 year"): // Über ein Jahr her
            $resultArray["LastChangeSus"] = 1;
            break;
        case $timestamp < strtotime("-6 months"): // Über 6 Monate unter 1 Jahr
            $resultArray["LastChangeSus"] = 0.8;
            break;
        case $timestamp < strtotime("-3 months"): // Über 3 Monate unter 6 Monate
            $resultArray["LastChangeSus"] = 0.6;
            break;
        case $timestamp < strtotime("-1 months"): // Über einen Monat unter 3 Monate
            $resultArray["LastChangeSus"] = 0.4;
            break;
        case $timestamp < strtotime("-2 weeks"): // Über zwei Wochen unter 1 Monat
            $resultArray["LastChangeSus"] = 0.2;
            break;
        case $timestamp > strtotime("-2 weeks"): // Unter zwei Wochen her
            $resultArray["LastChangeSus"] = 0;
            break;
    }

    // Level suspicion detection
    switch ($playerData["Level"]){
        case $playerData["Level"] <= 30: // Level 30 oder niedriger
            $resultArray["LevelSus"] = 1;
            break;
        case $playerData["Level"] <= 50: // Level 50 oder niedriger
            $resultArray["LevelSus"] = 0.8;
            break;
        case $playerData["Level"] <= 70: // Level 70 oder niedriger
            $resultArray["LevelSus"] = 0.6;
            break;
        case $playerData["Level"] <= 90: // Level 90 oder niedriger
            $resultArray["LevelSus"] = 0.4;
            break;
        case $playerData["Level"] <= 110: // Level 110 oder niedriger
            $resultArray["LevelSus"] = 0.2;
            break;
        case $playerData["Level"] > 110: // Level 111 oder höher
            $resultArray["LevelSus"] = 0;
            break;
    }

    // Ranked Game Count suspicion detection
    $totalRankedMatches = 0;
    if(empty($rankData) || empty(array_intersect(array("RANKED_SOLO_5x5", "RANKED_FLEX_SR"), array_column($rankData,"Queue")))){
        $resultArray["LevelSus"] = 1;
    } else {
        foreach($rankData as $rankQueue){
            if($rankQueue["Queue"] == "RANKED_SOLO_5x5"){
                $totalRankedMatches += $rankQueue["Wins"] + $rankQueue["Losses"];
            } else if($rankQueue["Queue"] == "RANKED_FLEX_SR"){
                $totalRankedMatches += $rankQueue["Wins"] + $rankQueue["Losses"];
            }
        }
    }
    switch ($totalRankedMatches){
        case $totalRankedMatches == 0: // Keine Ranked Games gespielt
            $resultArray["RankedGameCountSus"] = 1;
            break;
        case $totalRankedMatches <= 20: // 20 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.8;
            break;
        case $totalRankedMatches <= 40: // 40 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.6;
            break;
        case $totalRankedMatches <= 60: // 60 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.4;
            break;
        case $totalRankedMatches <= 80: // 80 oder weniger gespielt
            $resultArray["RankedGameCountSus"] = 0.2;
            break;
        case $totalRankedMatches > 80: // 81 oder mehr gespielt
            $resultArray["RankedGameCountSus"] = 0;
            break;
    }

    // Mastery Data Point suspicion detection
    $totalMastery = 0;
    if(empty($masteryData)){
        $resultArray["MasteryDataSus"] = 1;
    } else {
        foreach($masteryData as $champMastery){
            $totalMastery += str_replace(',', '.', $champMastery["Points"]);
        }
    }
    if ($totalMastery == 0) { // Keine Champion Mastery
        $resultArray["MasteryDataSus"] = 1;
    } elseif ($totalMastery <= 40) { // weniger als 40k Punkte
        $resultArray["MasteryDataSus"] = 0.8;
    } elseif ($totalMastery <= 80) { // weniger als 80k Punkte
        $resultArray["MasteryDataSus"] = 0.6;
    } elseif ($totalMastery <= 120) { // weniger als 120k Punkte
        $resultArray["MasteryDataSus"] = 0.4;
    } elseif ($totalMastery <= 160) { // weniger als 160k Punkte
        $resultArray["MasteryDataSus"] = 0.2;
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
function generateTag($tagText, $bgColor, $tooltipText, $additionalData = "") {
    $translatedTagText = __($tagText);
    $translatedTooltipText = __($tooltipText);
    if(isset($_COOKIE["tagOptions"], $additionalData)){ 
        if($_COOKIE["tagOptions"] == "two-colored"){
            $bgClass = ($additionalData == "positive") ? "bg-tag-lime" : "bg-tag-red";
            return "<div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] $bgClass cursor-help'
                    onmouseenter='showTooltip(this, \"$translatedTooltipText\", 500, \"top-right\")'
                    onmouseleave='hideTooltip(this)' data-type=\"$additionalData\" data-color=\"$bgColor\">
                    $translatedTagText
                    </div>";
        } else {
            return "Unknown tag option";
        }
    } else {
        return "<div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] $bgColor cursor-help'
                onmouseenter='showTooltip(this, \"$translatedTooltipText\", 500, \"top-right\")'
                onmouseleave='hideTooltip(this)' data-type=\"$additionalData\" data-color=\"$bgColor\">
                $translatedTagText
                </div>";
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
        }
    }
    return $returnString;
}

function objectToArray($object) {
    if (is_object($object) || is_array($object)) {
        $result = [];
        foreach ($object as $key => $value) {
            $result[$key] = objectToArray($value);
        }
        return $result;
    } else {
        return $object;
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
    global $headers, $apiRequests;
    $playerName = preg_replace('/\s+/', '', $_POST['sumname']);
    $playerName = str_replace("#","/",$playerName);
    $playerData = getPlayerData("riot-id",$playerName);

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/tournaments");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $tournamentsOutput = curl_exec($ch); $apiRequests["postSubmit"]++;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($httpCode == "200"){
        
        if(empty(json_decode($tournamentsOutput, true))){
            echo $playerName;
        } else {
            // Curl API request block
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/players/by-summoner/" . $playerData["SumID"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $clashOutput = curl_exec($ch); $apiRequests["postSubmit"]++;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        
            // 429 Too Many Requests
            if($httpCode == "429"){
                sleep(5);
                curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/players/by-summoner/" . $playerData["SumID"]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $clashOutput = curl_exec($ch); $apiRequests["postSubmit"]++;
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
    }
}
?>