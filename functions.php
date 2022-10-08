<?php
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
$currentPatch = file_get_contents("/var/www/html/wordpress/clashapp/data/patch/version.txt");
$counter = 0;
$headers = array(
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
    "Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7",
    "Accept-Charset: application/x-www-form-urlencoded; charset=UTF-8",
    "Origin: https://dasnerdwork.net/",
    "X-Riot-Token: ".$apiKey
 );
 $currentTimestamp = time();

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
    global $currentPatch, $headers;
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

    // 403 Access forbidden -> Outdated API Key
    if($httpCode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestUrlVar . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
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

    // 403 Forbidden
    if($httpCode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }
    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$sumid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
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

    // 403 Forbidden
    if($httpCode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
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

        // 429 Too Many Requests
        if($httpCode == "429"){ /** @todo fetch function with switch to handle and log every httpcode error */
            sleep(121);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gameType . "&start=".$start."&count=" . $matchCount);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $matchidOutput = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
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
    $logPath = '/var/www/html/wordpress/clashapp/data/logs/matchDownloader.log';

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
    if(!file_exists('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . ".json")){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $matchOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 429 Too Many Requests
        if($httpCode == "429"){
            sleep(121);
            $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $limit = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Rate limit got exceeded -> Now sleeping for 121 seconds - Status: " . $httpCode . " Too Many Requests";
            file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $matchOutput = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        // Write to log and save the matchid.json, else skip
        clearstatcache(true, $logPath);
        $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $answer = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: Got new matchdata from \"" . $username . "\" via " . $matchid . ".json - Status: " . $httpCode . " (Size: ".number_format((filesize($logPath)/1048576), 3)." MB)";
        file_put_contents($logPath, $answer.PHP_EOL , FILE_APPEND | LOCK_EX);
        if($httpCode == "200"){
            $fp = fopen('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . '.json', 'w');
            fwrite($fp, $matchOutput);
            fclose($fp);
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

    // Loop through each matchID.json
    foreach ($matchIDArray as $key => $matchIDJSON) {
        if(memory_get_usage() - $startMemory > "268435456" || $key == 500)return $matchData; // If matchData array bigger than 256MB size or more than 500 matches -> stop and return
        if(file_exists('/var/www/html/wordpress/clashapp/data/matches/'.$matchIDJSON.'.json')){
           $matchData[$matchIDJSON] = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/matches/'.$matchIDJSON.'.json')); 
        }        
    }
    return $matchData;
}

/** Display detailed Information about specific matches via PUUID
 * Prints all locally stored information about all matchIDs stored in the players playerdata.json (also stored locally)
 * But accessed through the players PUUID, hence only PUUID required and no API request necessary
 *
 * @param array $matchIDArray This input parameter array contains all matchIDs of a specific user
 * @param string $puuid The players PUUID
 * @var string $username Is the given username or PUUID
 * @var int $count the countervalue to display the amount of locally stored files in which the player (PUUID) is part of
 *
 * Returnvalue:
 * @return void N/A, displaying on page via table
 */
function getMatchDetailsByPUUID($matchIDArray, $puuid){
    global $currentPatch;
    $count = 0;

    // Initiating Matchdetail Table
    echo "<table class='table'>";
    $startFileIterator = microtime(true);
    foreach ($matchIDArray as $i => $matchIDJSON) {
        $handle = file_get_contents("/var/www/html/wordpress/clashapp/data/matches/".$matchIDJSON.".json");
        $inhalt = json_decode($handle);
        if(isset($inhalt->metadata->participants) && $inhalt->info->gameDuration != 0) {
            if(in_array($puuid, (array) $inhalt->metadata->participants)){
                $count++;
                for($in = 0; $in < 10; $in++){
                    if($inhalt->info->participants[$in]->puuid == $puuid) {
                        echo "<tr>";

                        // Display of W(in) or L(ose)
                        if($inhalt->info->participants[$in]->win == true) {
                            echo '<td class="online" style="color:#1aa23a"><b>W</b></td>';
                        } else {
                            echo '<td class="offline" style="color:#b31414"><b>L</b></td>';
                        }

                        // Display of the corresponding matchID
                        echo "<td>ID: ".$inhalt->metadata->matchId;

                        // Display of the played champions icon + name
                        echo "<td>Champion: ";
                        $champion = $inhalt->info->participants[$in]->championName;
                        if($champion == "FiddleSticks"){$champion = "Fiddlesticks";} /** @todo One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ, see*/
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.png')){
                            echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.png" width="32" style="vertical-align:middle" loading="lazy">';
                            echo " ".$inhalt->info->participants[$in]->championName . "</td>";
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                            echo " N/A</td>";
                        }

                        // Display of the equipped keyrune + secondary tree
                        echo "<td>Runes: ";
                        $keyRune = $inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk;
                        $secRune = $inhalt->info->participants[$in]->perks->styles[1]->style;
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/img/'.runeIconFetcher($keyRune))){
                            echo '<img src="/clashapp/data/patch/img/'.runeIconFetcher($keyRune).'" width="32" style="vertical-align:middle" loading="lazy">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                        }
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune))){
                            echo '<img src="/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune).'" width="16" style="vertical-align:middle" loading="lazy">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                        }
                        echo "</td>";

                        // Display of the played position
                        /** @todo Add individualPosition and role as else-options */
                        if($inhalt->info->participants[$in]->teamPosition != "") {
                            if($inhalt->info->participants[$in]->teamPosition == "UTILITY") {
                                echo "<td>Position: SUPPORT</td>";
                            } else {
                                echo "<td>Position: ".$inhalt->info->participants[$in]->teamPosition . "</td>";
                            }
                        } else {
                            echo "<td>Position: N/A</td>";
                        }

                        // Display of the players Kills/Deaths/Assists
                        echo "<td>KDA: ".$inhalt->info->participants[$in]->kills . "/";
                        echo $inhalt->info->participants[$in]->deaths . "/";
                        echo $inhalt->info->participants[$in]->assists . "</td>";

                        // Display of the last items the user had at the end of the game in his inventory
                        echo "<td>Items: ";
                        for($b=0; $b<7; $b++){
                            $allItems = "item".$b;
                            $itemId = $inhalt->info->participants[$in]->$allItems;
                            if($itemId == 0){
                                echo '<img src="/clashapp/data/misc/0.png" width="32" style="vertical-align:middle" loading="lazy">';
                            } else {
                                if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/item/'.$itemId.'.png')){
                                    echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/' . $itemId . '.png" width="32" style="vertical-align:middle" loading="lazy">';
                                } else if(file_exists('/var/www/html/wordpress/clashapp/data/misc/'.$itemId.'.png')){
                                    echo '<img src="/clashapp/data/misc/'.$itemId.'.png" width="32" style="vertical-align:middle" loading="lazy">';
                                } else {
                                    echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                                }
                            }
                        }
                        echo '</td>';

                        // Display of the user Vision and Wardscore
                        echo '<td>Vision Score: ';
                        echo $inhalt->info->participants[$in]->visionScore . " Wards: ";
                        echo $inhalt->info->participants[$in]->wardsPlaced . "x ";
                        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/3340.png" width="16" style="vertical-align:middle" loading="lazy"> Control Wards: ';
                        if(isset($inhalt->info->participants[$in]->challenges->controlWardsPlaced)){
                            echo $inhalt->info->participants[$in]->challenges->controlWardsPlaced . "x ";
                        } else if(isset($inhalt->info->participants[$in]->visionWardsBoughtInGame)){
                            echo $inhalt->info->participants[$in]->visionWardsBoughtInGame . "x ";
                        }
                        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/2055.png" width="16" style="vertical-align:middle" loading="lazy"></td>';

                        // Display of the Total Values
                        echo "<td>Totals: ";
                        echo $inhalt->info->participants[$in]->totalDamageDealt . " Damage, ";
                        echo $inhalt->info->participants[$in]->totalDamageDealtToChampions . " to Champions";
                        echo '</td><td>';
                        echo $inhalt->info->participants[$in]->totalDamageShieldedOnTeammates . " Shielded, ";
                        echo $inhalt->info->participants[$in]->totalHealsOnTeammates . " Healed";
                        echo '</td><td>';
                        echo $inhalt->info->participants[$in]->totalHeal . " Selfhealed, ";
                        echo $inhalt->info->participants[$in]->totalDamageTaken . " Tanked";
                        echo '</td><td>';
                        echo $inhalt->info->participants[$in]->timeCCingOthers . " Time CCing Others, ";
                        echo $inhalt->info->participants[$in]->totalTimeCCDealt . " Time CC dealt";
                        echo '</td>';

                        // Display of Date and Time
                        if(isset($inhalt->info->gameEndTimestamp)) {
                            $matchdate = date('d.m.Y H:i:s', $inhalt->info->gameEndTimestamp/1000);
                            echo "<td>Datum: " . $matchdate . "</td>";
                        } else if(isset($inhalt->info->gameStartTimestamp)) {
                            $matchdate = date('d.m.Y H:i:s', $inhalt->info->gameStartTimestamp/1000);
                            echo "<td>Datum: " . $matchdate . "</td>";
                        } else if(isset($inhalt->info->gameCreation)) {
                            $matchdate = date('d.m.Y H:i:s', $inhalt->info->gameCreation/1000);
                            echo "<td>Datum: " . $matchdate . "</td>";
                        }
                    }
                }

                // Display of Ranked Queuetype
                switch ($inhalt->info->queueId) {
                    case 420:
                        $matchType = "Solo/Duo";
                        break;
                    case 440:
                        $matchType = "Flex 5v5";
                        break;
                    case 700:
                        $matchType = "Clash";
                        break;
                }
                echo "<td>Matchtyp: ".$matchType . "</td></tr>";
            }
        }
    }

    echo "</table>";
    // End of Matchdetail Table & Counttext of local specific amount
    // echo "<br>Es wurden " . $count ." lokale Matchdaten gefunden<br>";
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
            return "A minute ago";
        case ($seconds>=120 && $seconds<3600):
            return floor($seconds / 60)." minutes ago";
        case ($seconds>=3600 && $seconds<7200):
            return "1 hour ago";
        case ($seconds>=7200 && $seconds<86400):
            return floor($seconds / 3600)." hours ago";
        case ($seconds>=86400 && $seconds<172800):
            return "1 day ago";
        case ($seconds>=172800 && $seconds<2630000):
            return floor($seconds / 86400)." days ago";
        case ($seconds>=2630000 && $seconds<5260000):
            return "1 month ago";
        case ($seconds>=5260000 && $seconds<31536000):
            return floor($seconds / 2630000)." months ago";
        case ($seconds>=31536000 && $seconds<63072000):
            return "1 years ago";
        case ($seconds>=63072000):
            return floor($seconds / 31536000)." years ago";
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

    // Initiating Matchdetail Table
    echo "<button type='button' class='collapsible'>Open Collapsible</button>";
    echo "<div class='content' style='border-collapse: collapse;'>";
    foreach ($matchIDArray as $i => $matchIDJSON) {
        $handle = file_get_contents("/var/www/html/wordpress/clashapp/data/matches/".$matchIDJSON.".json");
        $inhalt = json_decode($handle);
        if(isset($inhalt->metadata->participants) && $inhalt->info->gameDuration != 0) {
            if(in_array($puuid, (array) $inhalt->metadata->participants)){
                $count++;
                for($in = 0; $in < 10; $in++){
                    if($inhalt->info->participants[$in]->puuid == $puuid) {
                        $teamID = $inhalt->info->participants[$in]->teamId;
                        echo '<div class="match">';
                            echo '<div class="grid-container">';
                                echo '<div class="match-result">';
                                // Display of W(in) or L(ose)
                                if($inhalt->info->participants[$in]->win == true) {
                                    echo '<div class="online" style="color:#1aa23a"><b>W</b></div></div>';
                                } else {
                                    echo '<div class="offline" style="color:#b31414"><b>L</b></div></div>';
                                }

                                echo '<div class="match-type-and-time">';
                                // Display of Ranked Queuetype & Gamelength
                                switch ($inhalt->info->queueId) {
                                    case 420:
                                        $matchType = "Solo/Duo";
                                        echo "<div style='text-align: left; position: relative; left: -42px;'> Solo/Duo ";
                                        break;
                                    case 440:
                                        $matchType = "Flex 5v5";
                                        echo "<div style='text-align: left; position: relative; left: -42px;'> Flex ";
                                        break;
                                    case 700:
                                        $matchType = "Clash";
                                        echo "<div style='text-align: left; position: relative; left: -42px;'> Clash ";
                                        break;
                                }
                                echo gmdate("i:s", $inhalt->info->gameDuration)."</div>";
                                echo "</div>";

                                echo "<div class='match-id' style='display: none;'>".$matchIDJSON."</div>";

                                echo '<div class="match-time-ago">';
                                // Display when the game date was, if > than 23h -> day format, if > than 30d -> month format, etc.
                                echo "<div>".secondsToTime(strtotime('now')-intdiv($inhalt->info->gameEndTimestamp, 1000))."</div></div>";

                                // Calculate own Takedowns of Kill Participation
                                $ownTakedowns = 0;
                                $ownTakedowns += $inhalt->info->participants[$in]->kills;
                                $ownTakedowns += $inhalt->info->participants[$in]->assists;

                                echo '<div class="damage-dealt">';
                                echo "Dealt: ".number_format($inhalt->info->participants[$in]->totalDamageDealtToChampions, 0);
                                echo "</div>";

                                echo '<div class="damage-tanked">';
                                echo "Taken: ".number_format($inhalt->info->participants[$in]->totalDamageTaken, 0);
                                echo "</div>";


                                echo '<div class="damage-healed-and-shielded">';
                                echo "Shealed: ".number_format($inhalt->info->participants[$in]->challenges->effectiveHealAndShielding, 0);
                                echo "</div>";

                                echo '<div class="damage-to-objectives">';
                                echo "Objs: ".number_format($inhalt->info->participants[$in]->damageDealtToObjectives, 0);
                                echo "</div>";

                                echo '<div class="vision-wards" style="position: relative;">';
                                echo '<img class="parent-trinket-icon" style="height: auto;" src="/clashapp/data/patch/'.$currentPatch.'/img/item/2055.png" width="32" loading="lazy">';
                                echo '<div class="vision-wards-count-icon">'.$inhalt->info->participants[$in]->detectorWardsPlaced.'</div>';
                                echo "</div>";

                                echo '<div class="creepscore">';
                                echo '<div class="creepscore-count">CS: '.$inhalt->info->participants[$in]->totalMinionsKilled.'</div>';
                                echo "</div>";

                                echo '<div class="visionscore">';
                                echo '<div class="visionscore-count">V-Score: '.$inhalt->info->participants[$in]->visionScore.'</div>';
                                echo "</div>";

                                echo '<div class="collapser">&#8964;</div>';


                        // Display of the played champions icon
                        echo '<div class="champion-icon" style="margin-bottom: -17px;"><div>';
                        if ($inhalt->info->participants[$in])
                        $champion = $inhalt->info->participants[$in]->championName;
                        if($champion == "FiddleSticks"){$champion = "Fiddlesticks";} /** @todo One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ */
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.png')){
                            echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$champion.'.png" width="64" style="vertical-align:middle; position:relative; z-index:1;" loading="lazy">';
                            echo '<img src="/clashapp/data/misc/LevelAndLaneOverlay.png" width="64" style="vertical-align:middle; position:relative; bottom:64px; margin-bottom: -64px; z-index:2;" loading="lazy"></div>';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy"></div>';
                        }

                        // Display of champion level at end of game
                        echo '<div class="champion-level" style="z-index:3; bottom: 17px; right: 23px; font-size: 13px;">';
                        echo $inhalt->info->participants[$in]->champLevel;
                        echo '</div>';

                        // Display of played Position
                        echo "<div class='champion-lane' style='z-index:3; position:relative; bottom: 32px; left: 23px;'>";
                        $matchLane = $inhalt->info->participants[$in]->teamPosition;
                        if(file_exists('/var/www/html/wordpress/clashapp/data/misc/lanes/'.$matchLane.'.png')){
                            echo '<img src="/clashapp/data/misc/lanes/'.$matchLane.'.png" width="14" loading="lazy">';
                        }
                        echo "</div></div>";


                        // Display of Match Score 1-10
                        echo '<div class="matchscore">';
                        foreach ($matchRankingArray as $matchID => $rankingValue){
                            // print_r($matchID."<br>");
                            // print_r($inhalt->metadata->matchId."<br>");
                            if($matchID == $inhalt->metadata->matchId){
                                echo "Score: ".$matchRankingArray[$matchID];
                            }
                        }
                        echo "</div>";

                        // Display summoner spells
                        echo '<div class="summoner-spell-1">';
                        $summoner1Id = $inhalt->info->participants[$in]->summoner1Id;
                        $summoner2Id = $inhalt->info->participants[$in]->summoner2Id;
                        if(file_exists('/var/www/html/wordpress/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).".png")){
                            echo '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner1Id).'.png" width="32" style="vertical-align:middle" loading="lazy">';
                        }
                        echo '</div><div class="summoner-spell-2">';
                        if(file_exists('/var/www/html/wordpress/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).".png")){
                            echo '<img src="/clashapp/data/misc/summoners/'.summonerSpellFetcher($summoner2Id).'.png" width="32" style="vertical-align:middle" loading="lazy">';
                        }
                        echo "</div>";


                        // Display of the equipped keyrune + secondary tree
                        echo '<div class="rune-1">';
                        $keyRune = $inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk;
                        $secRune = $inhalt->info->participants[$in]->perks->styles[1]->style;
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/img/'.runeIconFetcher($keyRune))){
                            echo '<img src="/clashapp/data/patch/img/'.runeIconFetcher($keyRune).'" width="32" style="vertical-align:middle" loading="lazy">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                        }
                        echo '</div><div class="rune-2">';
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune))){
                            echo '<img src="/clashapp/data/patch/img/'.runeTreeIconFetcher($secRune).'" width="16" style="vertical-align:middle" loading="lazy">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                        }
                        echo "</div>";

                        // Display of the players Kills/Deaths/Assists
                        echo '<div class="kda-stats">';
                        $kills = $inhalt->info->participants[$in]->kills;
                        $deaths = $inhalt->info->participants[$in]->deaths;
                        $assists = $inhalt->info->participants[$in]->assists;
                        echo $kills . "/";
                        echo $deaths . "/";
                        echo $assists;
                        echo '</div><div class="kda">';
                        if($deaths != 0){
                            echo " KDA: ".number_format(($kills+$assists)/$deaths, 2)."</div>";
                        } else {
                            echo " KDA: ".number_format(($kills+$assists)/1, 2)."</div>";
                        }

                        // Display of the last items the user had at the end of the game in his inventory
                        $noItemCounter = 0;
                        unset($lastItemSlot);
                        for($b=0; $b<7; $b++){
                            if($b == 6){
                                for($c=0; $c<$noItemCounter; $c++){
                                    echo '<div class="item'.($lastItemSlot+1).'">';
                                    echo '<img src="/clashapp/data/misc/0.png" width="32" style="vertical-align:middle" loading="lazy">';
                                    echo '</div>';
                                    $lastItemSlot++;
                                }
                                echo '<div class="trinket">';
                            }
                            $allItems = "item".$b;
                            $itemId = $inhalt->info->participants[$in]->$allItems;
                            if($itemId == 0){
                                // echo '<img src="/clashapp/data/misc/0.png" width="32" style="vertical-align:middle" loading="lazy">';
                                $noItemCounter += 1;
                            } else {
                                echo '<div class="item'.($b - $noItemCounter).'">';
                                if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/item/'.$itemId.'.png')){
                                    echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/item/' . $itemId . '.png" width="32" style="vertical-align:middle" loading="lazy">';
                                } else if(file_exists('/var/www/html/wordpress/clashapp/data/misc/'.$itemId.'.png')){
                                    echo '<img src="/clashapp/data/misc/'.$itemId.'.png" width="32" style="vertical-align:middle" loading="lazy">';
                                } else {
                                    echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy">';
                                }
                                $lastItemSlot = $b;
                                echo "</div>";
                            }

                        }
                        echo "</div>";
                    }
                }
                for($i = 0; $i < 10; $i++){
                    // Display of enemy champions icon in lane
                    if (($inhalt->info->participants[$i]->teamPosition == $matchLane) && ($inhalt->info->participants[$i]->championName != $champion)){
                    echo '<div class="lane-opponent">vs. ';
                    $enemyChamp = $inhalt->info->participants[$i]->championName;
                    if($enemyChamp == "FiddleSticks"){$enemyChamp = "Fiddlesticks";} /** @todo One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ */
                    if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.png')){
                        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$enemyChamp.'.png" width="32" style="vertical-align:middle" loading="lazy"></div>';
                    } else {
                        echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle" loading="lazy"></div>';
                    }
                    }
                    if ($inhalt->info->participants[$i]->teamId == $teamID){
                        $totalTeamTakedowns += $inhalt->info->participants[$i]->kills;
                    }
                }
                echo '<div class="kill-participation">';
                if($totalTeamTakedowns != 0){
                    echo "KP: ".number_format(($ownTakedowns/$totalTeamTakedowns)*100, 0). "%";
                } else {
                    echo "KP: 0%";
                }
                $totalTeamTakedowns = 0;
                echo '</div>';
                echo '</div>';
                echo '</div>';
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
    if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.png')){
        echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.png" width="64" loading="lazy"><br>';
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
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json');
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
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/summoner.json');
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
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json');
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
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
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
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
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
            $mostCommonReturn[$attribute][$values[$attribute][$i]] = $count[$attribute][$i];
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
        $secondaryLane = "UNKNOWN";
    } else if (array_values($laneCountArray)[0] <= 40){
        $mainLane = "FILL";
        $secondaryLane = "UNKNOWN";
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
    $averageStatsJson = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/misc/averageStats.json'), true);

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
    echo '<input type="text" id="statTableSearch" onkeyup="searchStatTable()" placeholder="Statname.." style="margin-left: 55.6em; margin-bottom: 1em; height: 3em; font-size: 1em;">';
    echo "<table class='table' id='stattable' vertical-align:top;'><tr>";
    echo "<th>Statname</th><th>My Average</th><th>Average in General</th><th>As Bottom</th><th>As Support</th><th>As Middle</th><th>As Jungle</th><th>As Top</th></tr>";

    // Count & Round to retrieve printable data
    foreach ($averageArray as $key => $arrayElement){
        echo "<tr><td style='text-align: center;'>" . $key . ": </td>";
        if(($arrayElement / $counterArray[$key]) < 10){
            if(round($arrayElement / $counterArray[$key],2)>$averageStatsJson[$lane][$key]*2){
                echo "<td style='color:#1aa23a'>";
            } else if(round($arrayElement / $counterArray[$key],2)!=0&&round($arrayElement / $counterArray[$key],2)<$averageStatsJson[$lane][$key]/2){
                echo "<td style='color:#b31414'>";
            } else {
                echo "<td>";
            }
            echo $averageArray[$key] = round($arrayElement / $counterArray[$key],2)."</td>";
        } else if(($arrayElement / $counterArray[$key]) < 100){
            if(round($arrayElement / $counterArray[$key],1)>$averageStatsJson[$lane][$key]*2){
                echo "<td style='color:#1aa23a'>";
            } else if(round($arrayElement / $counterArray[$key],1)!=0&&round($arrayElement / $counterArray[$key],1)<$averageStatsJson[$lane][$key]/2){
                echo "<td style='color:#b31414'>";
            } else {
                echo "<td>";
            }
            echo $averageArray[$key] = round($arrayElement / $counterArray[$key],1)."</td>";
        } else {
            if(round($arrayElement / $counterArray[$key])>$averageStatsJson[$lane][$key]*2){
                echo "<td style='color:#1aa23a'>";
            } else if(round($arrayElement / $counterArray[$key])!=0&&round($arrayElement / $counterArray[$key])<$averageStatsJson[$lane][$key]/2){
                echo "<td style='color:#b31414'>";
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
        uasort($champArray, function($a, $b) use($key){
            return $b['winrate'] <=> $a['winrate'];
        });
    // Sort ascending, from lowest to highest if first element should be of type "mostLosses"
    } else if($type == "mostLosses"){
        uasort($champArray, function($a, $b) use($key){
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
    uasort($highestWinrateArray, function($a, $b) use($key){
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
    $rankingAttributeArray = array("Kills", "Deaths", "Assists", "KDA", "KillParticipation", "CS", "Gold", "VisionScore", "WardTakedowns", "WardsPlaced", "WardsGuarded", "VisionWards", "Consumables", "TurretPlates", "TotalTakedowns", "TurretTakedowns", 
    "InhibitorTakedowns", "DragonTakedowns", "HeraldTakedowns", "DamageToBuildings", "DamageToObjectives", "DamageMitigated", "DamageDealtToChampions", "DamageTaken", "TeamShielded", "TeamHealed", "TimeCC", "DeathTime", "SkillshotsDodged", "SkillshotsHit");
    $maxRankScore = 0;
    $returnArray = array();
    // $matchIDArray = array_slice($matchIDArray, 0, 15);
    foreach ($matchIDArray as $matchID) {
        unset($maxRankScore);
        unset($mainArray);
        //going through all matches to save all data in array per sumid
        foreach ($matchData[$matchID]->info as $player) {
            for ($i = 0; $i < 10; $i++){
                if (isset($player[$i]->summonerId)) {
                    //mainArray[SpielerSumid1-10][Attribut] = Wert vom Attribut;
                    $mainArray[$player[$i]->summonerId]["Kills"] = $player[$i]->kills;
                    $mainArray[$player[$i]->summonerId]["Deaths"] = $player[$i]->deaths;
                    $mainArray[$player[$i]->summonerId]["Assists"] = $player[$i]->assists;
                    $mainArray[$player[$i]->summonerId]["KDA"] = $player[$i]->challenges->kda;
                    $mainArray[$player[$i]->summonerId]["KillParticipation"] = $player[$i]->challenges->killParticipation;
                    $mainArray[$player[$i]->summonerId]["CS"] = $player[$i]->totalMinionsKilled;
                    $mainArray[$player[$i]->summonerId]["Gold"] = $player[$i]->goldEarned;
                    $mainArray[$player[$i]->summonerId]["VisionScore"] = $player[$i]->visionScore;
                    $mainArray[$player[$i]->summonerId]["WardTakedowns"] = $player[$i]->challenges->wardTakedowns;
                    $mainArray[$player[$i]->summonerId]["WardsPlaced"] = $player[$i]->wardsPlaced;
                    $mainArray[$player[$i]->summonerId]["WardsGuarded"] = $player[$i]->challenges->wardsGuarded;
                    $mainArray[$player[$i]->summonerId]["VisionWards"] = $player[$i]->detectorWardsPlaced;
                    $mainArray[$player[$i]->summonerId]["Consumables"] = $player[$i]->consumablesPurchased;
                    $mainArray[$player[$i]->summonerId]["TurretPlates"] = $player[$i]->challenges->turretPlatesTaken;
                    $mainArray[$player[$i]->summonerId]["TotalTakedowns"] = $player[$i]->challenges->takedowns;
                    $mainArray[$player[$i]->summonerId]["TurretTakedowns"] = $player[$i]->turretTakedowns;
                    $mainArray[$player[$i]->summonerId]["InhibitorTakedowns"] = $player[$i]->inhibitorTakedowns;
                    $mainArray[$player[$i]->summonerId]["DragonTakedowns"] = $player[$i]->challenges->dragonTakedowns;
                    $mainArray[$player[$i]->summonerId]["HeraldTakedowns"] = $player[$i]->challenges->riftHeraldTakedowns;
                    $mainArray[$player[$i]->summonerId]["DamageToBuildings"] = $player[$i]->damageDealtToBuildings;
                    $mainArray[$player[$i]->summonerId]["DamageToObjectives"] = $player[$i]->damageDealtToObjectives;
                    $mainArray[$player[$i]->summonerId]["DamageMitigated"] = $player[$i]->damageSelfMitigated;
                    $mainArray[$player[$i]->summonerId]["DamageDealtToChampions"] = $player[$i]->totalDamageDealtToChampions;
                    $mainArray[$player[$i]->summonerId]["DamageTaken"] = $player[$i]->totalDamageTaken;
                    $mainArray[$player[$i]->summonerId]["TeamShielded"] = $player[$i]->totalDamageShieldedOnTeammates;
                    $mainArray[$player[$i]->summonerId]["TeamHealed"] = $player[$i]->totalHealsOnTeammates;
                    $mainArray[$player[$i]->summonerId]["TimeCC"] = $player[$i]->totalTimeCCDealt;
                    $mainArray[$player[$i]->summonerId]["DeathTime"] = $player[$i]->totalTimeSpentDead;
                    $mainArray[$player[$i]->summonerId]["SkillshotsDodged"] = $player[$i]->challenges->skillshotsDodged;
                    $mainArray[$player[$i]->summonerId]["SkillshotsHit"] = $player[$i]->challenges->skillshotsHit;
                }
            }
        }
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
            foreach($tempArray as $rank => $value){
                if ($value["SumID"] == $sumid){
                    switch ($attribute){
                        case "Kills":
                            $maxRankScore += (($rank+1)*7);
                            break;
                        case "Deaths":
                            $maxRankScore += (($rank+1)*10);
                            break;
                        case "Assists":
                            $maxRankScore += (($rank+1)*7);
                            break;
                        case "KDA":
                            $maxRankScore += (($rank+1)*20);
                            break;
                        case "CS":
                            $maxRankScore += (($rank+1)*5);
                            break;
                        case "Gold":
                            $maxRankScore += (($rank+1)*6);
                            break;
                        case "VisionScore":
                            $maxRankScore += (($rank+1)*20);
                            break;
                        case "WardTakedowns":
                            $maxRankScore += (($rank+1)*4);
                            break;
                        case "WardsPlaced":
                            $maxRankScore += (($rank+1)*2);
                            break;
                        case "WardsGuarded":
                            $maxRankScore += (($rank+1)*4);
                            break;
                        case "VisionWards":
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "Consumables":
                            $maxRankScore += (($rank+1)*1);
                            break;
                        case "TurretPlates":
                            $maxRankScore += (($rank+1)*5);
                            break;
                        case "TotalTakedowns":
                            $maxRankScore += (($rank+1)*20);
                            break;
                        case "TurretTakedowns":
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "InhibitorTakedowns":
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "DragonTakedowns":
                            $maxRankScore += (($rank+1)*7);
                            break;
                        case "HeraldTakedowns":
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "DamageToBuildings":
                            $maxRankScore += (($rank+1)*3);
                            break;
                        case "DamageToObjectives":
                            $maxRankScore += (($rank+1)*4);
                            break;
                        case "DamageMitigated":
                            $maxRankScore += (($rank+1)*3);
                            break;
                        case "DamageDealtToChampions":
                            $maxRankScore += (($rank+1)*15);
                            break;
                        case "DamageTaken":      
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "TeamShielded":                 
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "TeamHealed":                   
                            $maxRankScore += (($rank+1)*7);
                            break;
                        case "TimeCC":
                            $maxRankScore += (($rank+1)*8);
                            break;
                        case "DeathTime":                   
                            $maxRankScore += (($rank+1)*20);
                            break;
                        case "SkillshotsDodged":                      
                            $maxRankScore += (($rank+1)*20);
                            break;
                        case "SkillshotsHit":                   
                            $maxRankScore += (($rank+1)*1);
                            break;
                    }
                }
            }
            unset($tempArray);
        }
        $returnArray[$matchID] = number_format(($maxRankScore/247), 2);
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
    global $headers;
    $teamDataArray = array();
    $logPath = '/var/www/html/wordpress/clashapp/data/logs/teamDownloader.log';

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
        echo "<h2>API Key outdated!</h2>";
    }

    // 429 Too Many Requests
    if($httpCode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $teamOutput = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // Temporär change

    $teamOutput = file_get_contents('/hdd1/clashapp/misc/team.by-teamid.json');
    
    // $teamOutput = file_get_contents('/hdd1/clashapp/misc/clashTeam2.json');

    // Collect requested values in returnarray
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
    $championNamingData = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json');
    $championNamingFile = json_decode($championNamingData);
    foreach($championNamingFile->data as $champData){
        $champName = $champData->name;
        $i++;
        $imgPath = $champData->image->full;
        $dataId = $champData->id;
        if($i<11){
            if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath)){
                echo "<div class='champ-select-champion'>";
                    echo '<div class="ban-hoverer" onclick="">';
                        echo '<img class="champ-select-icon" style="height: auto; z-index: 1;" data-id="' . $dataId . '" data-abbr="' . abbreviationFetcher($champName) . '" src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'" width="48" loading="lazy">';
                        echo '<img class="ban-overlay" src="/clashapp/data/misc/icon-ban.png" width="48" loading="lazy">';
                        echo '<img class="ban-overlay-red" src="/clashapp/data/misc/icon-ban-red.png" width="48" loading="lazy"></div>';
                        echo "<span class='caption' style='display: block;'>".$champName."</span>";
                echo "</div>";
            }
        } else {
            if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath)){
                echo "<div class='champ-select-champion'>";
                    echo '<div class="ban-hoverer" onclick="">';
                        echo '<img class="champ-select-icon" style="height: auto; z-index: 1;" data-id="' . $dataId . '" data-abbr="' . abbreviationFetcher($champName) . '" src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$imgPath.'" width="48" loading="lazy">';
                        echo '<img class="ban-overlay" src="/clashapp/data/misc/icon-ban.png" width="48" loading="lazy">';
                        echo '<img class="ban-overlay-red" src="/clashapp/data/misc/icon-ban-red.png" width="48" loading="lazy"></div>';
                        echo "<span class='caption' style='display: block;'>".$champName."</span>";
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
    $abbrArray = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/misc/abbreviations.json'));
    foreach($abbrArray as $champFileName => $element){
        if($champFileName === $champName){
            $abbreviations = str_replace('_', ' ', str_replace(' ', '', $element->abbr));
        }
    }
  return $abbreviations;
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
    // $matchscoreArray = array();
    // $returnArray = array();
    $tempArray = array();
    $sortedMasteryArray = array();
    $countArray = array();
    // $counter=0;
    // $counter2=0;

    foreach($masterDataArray as $singleMasteryData){
        $sortedMasteryArray = array_merge($sortedMasteryArray, $singleMasteryData);
    }

    usort($sortedMasteryArray, function($a, $b){
        $a["Points"] = str_replace(',', '', $a["Points"]);
        $b["Points"] = str_replace(',', '', $b["Points"]);
        return $b["Points"] <=> $a["Points"];
    });

    foreach($masterDataArray as $sumid => $playersMasteryData){
        foreach($playersMasteryData as $data){
            $countArray[$data["Champion"]][] = $sumid;
        }
    }

    foreach($countArray as $champion => $players){
        if(count($players)<2){
            unset($countArray[$champion]);
        }
    }

    uasort($countArray, function($a, $b){
        return count($b) <=> count($a);
    });

    foreach($sortedMasteryArray as $key1 => $champData1){
        foreach($sortedMasteryArray as $key2 => $champData2){
            if(($champData1 != $champData2) && ($champData1["Champion"] == $champData2["Champion"])){
                $sortedMasteryArray[$key1]["TotalTeamPoints"] = number_format(str_replace(',', '', $champData1["Points"])+str_replace(',', '', $champData2["Points"]));
            }
        }
    }

    foreach(array_keys($sortedMasteryArray) as $championData){ // Remove unnecessary information
        // unset($sortedMasteryArray[$championData]["Filename"]); 
        unset($sortedMasteryArray[$championData]["Lvl"]); 
        unset($sortedMasteryArray[$championData]["LvlUpTokens"]); 
        $sortedMasteryArray[$championData]["MatchingLanersPrio"] = 0;
    }

    foreach($countArray as $champion => $players){
        foreach($players as $comparePlayer1){
            foreach($players as $comparePlayer2){
                if($comparePlayer1 != $comparePlayer2){
                    if($playerLanesTeamArray[$comparePlayer1]["Mainrole"] != "UNKNOWN"){
                        if(($playerLanesTeamArray[$comparePlayer1]["Mainrole"] == $playerLanesTeamArray[$comparePlayer2]["Mainrole"]) || ($playerLanesTeamArray[$comparePlayer1]["Mainrole"] == $playerLanesTeamArray[$comparePlayer2]["Secrole"])){
                            if($playerLanesTeamArray[$comparePlayer1]["Mainrole"] != "UNKNOWN"){
                                if($playerLanesTeamArray[$comparePlayer1]["Mainrole"] == "FILL"){
                                    foreach($sortedMasteryArray as $key => $championData){
                                        if($championData["Champion"] == $champion){
                                            $sortedMasteryArray[$key]["MatchingLanersPrio"] += 0.5;
                                        }
                                    }
                                    // echo "Low Prio Match found: ".$playerLanesTeamArray[$comparePlayer1]["Mainrole"]." to ".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                                } else {
                                    foreach($sortedMasteryArray as $key => $championData){
                                        if($championData["Champion"] == $champion){
                                            $sortedMasteryArray[$key]["MatchingLanersPrio"]++;
                                        }
                                    }
                                    // echo "High Prio Match found: ".$playerLanesTeamArray[$comparePlayer1]["Mainrole"]." to ".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                                }
                            }
                        }
                    }
                    if($playerLanesTeamArray[$comparePlayer1]["Secrole"] != "UNKNOWN"){
                        if(($playerLanesTeamArray[$comparePlayer1]["Secrole"] == $playerLanesTeamArray[$comparePlayer2]["Mainrole"]) || ($playerLanesTeamArray[$comparePlayer1]["Secrole"] == $playerLanesTeamArray[$comparePlayer2]["Secrole"])){
                            if($playerLanesTeamArray[$comparePlayer1]["Secrole"] == "FILL"){
                                foreach($sortedMasteryArray as $key => $championData){
                                    if($championData["Champion"] == $champion){
                                        $sortedMasteryArray[$key]["MatchingLanersPrio"] += 0.5;
                                    }
                                }
                                // echo "Low Prio Match found: ".$playerLanesTeamArray[$comparePlayer1]["Secrole"]." to ".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                            } else {
                                foreach($sortedMasteryArray as $key => $championData){
                                    if($championData["Champion"] == $champion){
                                        $sortedMasteryArray[$key]["MatchingLanersPrio"]++;
                                    }
                                }
                                // echo "High Prio Match found: ".$playerLanesTeamArray[$comparePlayer1]["Secrole"]." to ".$playerLanesTeamArray[$comparePlayer2]["Mainrole"]."/".$playerLanesTeamArray[$comparePlayer2]["Secrole"]." on ".$champion."<br>";
                            }
                        }
                    }
                }
            }
        }
    }

    $playerCountOfChampionArray = array_count_values(array_column($sortedMasteryArray, "Champion"));
    foreach($sortedMasteryArray as $key => $championData){
        foreach($playerCountOfChampionArray as $championName => $countData){
            if($championData["Champion"] == $championName){
                $sortedMasteryArray[$key]["CapablePlayers"] = $countData;
            }
        }
    }

    // echo "<pre>";
    // print_r($countArray); 
    // echo "</pre>";

    // echo "<pre>";
    // print_r($playerLanesTeamArray); 
    // echo "</pre>";

    foreach($matchIDArray as $matchID){
        foreach($matchData[$matchID]->info->participants as $player){
            foreach($sumidArray as $sumid){
                if($player->summonerId == $sumid){
                    foreach($sortedMasteryArray as $key => $championData){
                        if($championData["Champion"] == $player->championName){
                            $sortedMasteryArray[$key]["OccurencesInLastGames"]++;
                        }
                    }
                }
            }
        }
    }

    foreach($matchData as $inhalt){
        foreach($inhalt->info->participants as $player){
            if(in_array($player->summonerId, $sumidArray)){
                foreach($sortedMasteryArray as $key => $championData){
                    if($championData["Champion"] == $player->championName){
                        $sortedMasteryArray[$key]["AverageMatchScore"] = number_format(($sortedMasteryArray[$key]["AverageMatchScore"]+implode("",getMatchRanking(array($inhalt->metadata->matchId), $matchData, $player->summonerId)))/2, 2, '.', '');
                    }
                }
            }
        }
    }

    foreach($sortedMasteryArray as $key => $championData){
        $sortedMasteryArray[$key]["FinalScore"] = str_replace(',', '', $championData["Points"]/200); // MAX: Unlimited, e.g. 100k mastery -> 1, bei 1mio mastery -> 10, usw.        => 367,165 Points   -> FinalScore: 3.67
        $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["CapablePlayers"]*0.15; // MAX: 5 capable Players -> add 1 to final score                               => 3 Capable Player -> Finalscore: +0.45
        $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["MatchingLanersPrio"]*0.4; // MAX: 5 matching laners -> add 2 to final score                           => 4 Matching Laners-> Finalscore: +1.6
        $sortedMasteryArray[$key]["FinalScore"] += str_replace(',', '', $sortedMasteryArray[$key]["TotalTeamPoints"]/400); // MAX: Unlimited, e.g. 800k mastery -> 2                => 521,734 Total    -> Finalscore: +1.3
        switch ($sortedMasteryArray[$key]["LastPlayed"]){                                                                  // MAX: 1, e.g. Yesterday played                         => 3 weeks ago      -> Finalscore: +0.9
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-1 year"): // Über ein Jahr her
                $sortedMasteryArray[$key]["FinalScore"] += 0.1;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-6 months"): // Über 6 Monate unter 1 Jahr
                $sortedMasteryArray[$key]["FinalScore"] += 0.3;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-3 months"): // Über 3 Monate unter 6 Monate
                $sortedMasteryArray[$key]["FinalScore"] += 0.5;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-1 months"): // Über einen Monat unter 3 Monate
                $sortedMasteryArray[$key]["FinalScore"] += 0.7;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] < strtotime("-2 weeks"): // Über zwei Wochen unter 1 Monat
                $sortedMasteryArray[$key]["FinalScore"] += 0.9;
                break;
            case $sortedMasteryArray[$key]["LastPlayed"] > strtotime("-2 weeks"): // Unter zwei Wochen her
                $sortedMasteryArray[$key]["FinalScore"] += 1;
                break;
        }
        if($sortedMasteryArray[$key]["OccurencesInLastGames"] > 0 && $sortedMasteryArray[$key]["OccurencesInLastGames"] < 5){
            $sortedMasteryArray[$key]["FinalScore"] += 0.2;
        } else if($sortedMasteryArray[$key]["OccurencesInLastGames"] >= 5 && $sortedMasteryArray[$key]["OccurencesInLastGames"] < 10){
            $sortedMasteryArray[$key]["FinalScore"] += 0.4;
        } else if($sortedMasteryArray[$key]["OccurencesInLastGames"] >= 10 && $sortedMasteryArray[$key]["OccurencesInLastGames"] < 15){
            $sortedMasteryArray[$key]["FinalScore"] += 0.6;
        } else if($sortedMasteryArray[$key]["OccurencesInLastGames"] >= 15 && $sortedMasteryArray[$key]["OccurencesInLastGames"] < 20){
            $sortedMasteryArray[$key]["FinalScore"] += 0.8;
        } else if($sortedMasteryArray[$key]["OccurencesInLastGames"] >= 20){
            $sortedMasteryArray[$key]["FinalScore"] += 1;
        }
        if($sortedMasteryArray[$key]["AverageMatchScore"] > 0 && $sortedMasteryArray[$key]["AverageMatchScore"] < 3){
            $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["AverageMatchScore"]*0.15;
        } else if($sortedMasteryArray[$key]["AverageMatchScore"] >= 3 && $sortedMasteryArray[$key]["AverageMatchScore"] < 5){
            $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["AverageMatchScore"]*0.2; 
        } else if($sortedMasteryArray[$key]["AverageMatchScore"] >= 5 && $sortedMasteryArray[$key]["AverageMatchScore"] < 7){
            $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["AverageMatchScore"]*0.25; 
        } else if($sortedMasteryArray[$key]["AverageMatchScore"] >= 7){
            $sortedMasteryArray[$key]["FinalScore"] += $sortedMasteryArray[$key]["AverageMatchScore"]*0.3; 
        }
    }

    $returnArray = unique_multidim_array($sortedMasteryArray, "Champion");

    usort($returnArray, function($a, $b){
        return $b["FinalScore"] <=> $a["FinalScore"];
    });

    $returnArray = array_slice($returnArray, 0, 10);

    return $returnArray; 
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
        sleep(121);
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