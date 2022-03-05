<?php
/** Global Variables
 * Initializing of global variables used throughout all functions below
 * 
 * $api_key => The API Key necessary to communicate with the Riot API, to edit: nano /etc/nginx/fastcgi_params then service nginx restart
 * $currentpatch => For example "12.4.1", gets fetched from the version.txt which itself gets daily updated by the patcher.py script
 * $counter => Necessary counter variable for the getMatchByID Function
 * $headers => The headers required or at least recommended for the CURL request
 */  
$api_key = getenv('API_KEY');
$currentpatch = file_get_contents("/var/www/html/wordpress/clashapp/data/patch/version.txt");
$counter = 0;
$headers = array(
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
    "Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7",
    "Accept-Charset: application/x-www-form-urlencoded; charset=UTF-8",
    "Origin: https://dasnerdwork.net/",
    "X-Riot-Token: ".$api_key
 );

/** General Summoner Info
 * This function retrieves all general playerdata of a given username or PUUID
 * Eq. to https://developer.riotgames.com/apis#summoner-v4/GET_getBySummonerName
 * 
 * $type => Determines if the request gets sent to the API with a username or a PUUID
 * $username => Is the given username or PUUID
 * $output => Contains the output of the curl request as string which we later convert using json_decode
 * $httpcode => Contains the returncode of the curl request (e.g. 404 not found)
 * 
 * Returnvalue:
 * $playerDataArray with keys "Icon", "Name", "Level", "PUUID", "SumID", "AccountID" and "LastChange" of the summoners profile
 */
function getPlayerData($type, $username){
    global $currentpatch, $headers;
    $playerDataArray = array();
    
    switch ($type) {
        case "name":
            $requesturlvar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/";
            break;
        case "puuid":
            $requesturlvar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/";
            break;
    }

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $requesturlvar . $username);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 403 Access forbidden -> Outdated API Key
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }
    
    // 429 Too Many Requests 
    if($httpcode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requesturlvar . $username);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
 * $sumid => The summoners encrypted summoner ID necessary to perform the API request
 * $rankDataArray => Just a rename and rearrange of the API request return values
 * 
 * Returnvalue:
 * $rankReturnArray => Just a rename of the $rankDataArray
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
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 403 Forbidden
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }  
    // 429 Too Many Requests 
    if($httpcode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$sumid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
 * $sumid => The summoners encrypted summoner ID necessary to perform the API request
 * $rankDataArray => Just a rename and rearrange of the API request return values
 * 
 * Returnvalue:
 * $rankReturnArray => Just a rename of the $rankDataArray
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
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 403 Forbidden
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }  

    // 429 Too Many Requests 
    if($httpcode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
 * $puuid => Necessary PUUID of the summoner (Obtainable either through getPlayerData or via local stored file)
 * $maxMatchIDs => The maximum count to which we request matchIDs
 * $gameType => Set to the queue type of league "ranked", "normal", "tourney" or "tutorial"
 * $start => Starting at 0 and iterating by +100 every request (100 is the maximum of matchIDs you can request at once)
 * $matchcount => Always equals 100 except if it exceeds maxMatchIDs in it's next iteration, then set to max available
 *                E.g. maxMatchIDs = 219, 1. Iteration = 100, 2. Iteration = 100, 3. Iteration = 19
 * 
 * Returnvalue:
 * $matchIDArray with all MatchIDs as separate entries
 */
function getMatchIDs($puuid, $maxMatchIDs){
    global $headers;
    $matchIDArray = array();
    $gameType = "ranked";
    $start = 0;
    $matchcount = "100";

    while ($start < $maxMatchIDs) {
        // If next iterations would exceed the max
        if(($start + 100) > $maxMatchIDs){
            $matchcount = 100 - (($start + 100) - $maxMatchIDs);
        }

        // Curl API request block
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gameType . "&start=" . $start . "&count=" . $matchcount);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $matchid_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 429 Too Many Requests 
        if($httpcode == "429"){
            sleep(121);        
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gameType . "&start=".$start."&count=" . $matchcount);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $matchid_output = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        // Add each matchID to return array
        foreach (json_decode($matchid_output) as $match) {
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
 * $matchid => The single matchID of the game this function is supposed to download the information about
 * $username => OPTIONAL Is the given username, as this value is only used for the logging message and not necessary to perform anything
 * $logPath => The path where the log should be saved to
 * 
 * INFO: clearstatcache(); necessary for correct filesize statements as filesize() is a cached function
 * 
 * Returnvalue:
 * N/A, file saving & logging instead
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
        $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $slimmed = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Maximum filesize exceeded, removed first half of logfile - Status: OK (Size ".number_format((filesize($logPath)/1048576), 3)." MB)";
        file_put_contents($logPath, $slimmed.PHP_EOL , FILE_APPEND | LOCK_EX);
        $counter = 0;
    }

    // Only download if file doesn't exist yet
    if(!file_exists('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . ".json")){
        $ch = curl_init(); 
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $match_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);      
    
        // 429 Too Many Requests
        if($httpcode == "429"){
            sleep(121);
            $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $limit = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Rate limit got exceeded -> Now sleeping for 121 seconds - Status: " . $httpcode . " Too Many Requests";
            file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $match_output = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
        }

        // Write to log and save the matchid.json, else skip
        clearstatcache(true, $logPath);
        $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $answer = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: Got new matchdata from \"" . $username . "\" via " . $matchid . ".json - Status: " . $httpcode . " (Size: ".number_format((filesize($logPath)/1048576), 3)." MB)";
        file_put_contents($logPath, $answer.PHP_EOL , FILE_APPEND | LOCK_EX);
        $fp = fopen('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . '.json', 'w');
        fwrite($fp, $match_output);
        fclose($fp);
    }else{
        $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
        $noanswer = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: " . $matchid . ".json already existing - Skipping";
        file_put_contents($logPath, $noanswer.PHP_EOL , FILE_APPEND | LOCK_EX);
    }
}

/** Important little function to collect locally stored matchdata into array
 * This function loops through every given matchID's matchID.json and adds the data to a single $matchData array
 * At the same time collecting the necessary memory amount and limiting the returnvalue to 500 matchIDs or 256MB of RAM at once
 * 
 * $matchIDArray => Inputarray of all MatchIDs of the user
 * $startMemory => The necessary value to retrieve information about current stored memory amount of the array
 * 
 * Returnvalue:
 * $matchData => Array full of all given MatchID.json file contents up to the below maximum
 */
function getMatchData($matchIDArray){
    $startMemory = memory_get_usage();
    $matchData = array();

    // Loop through each matchID.json
    foreach ($matchIDArray as $key => $matchIDJSON) {
        if(memory_get_usage() - $startMemory > "268435456" || $key == 500)return $matchData; // If matchData array bigger than 256MB size or more than 500 matches -> stop and return
            $matchData[$key] = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/matches/'.$matchIDJSON.'.json'));
        }
    return $matchData;
}

/** Detailed Information about specific matches via PUUID
 * Prints all locally stored information about all matchIDs stored in the players playerdata.json (also stored locally)
 * But accessed through the players PUUID, hence only PUUID required and no API request necessary
 * 
 * $puuid => The players PUUID
 * $username => Is the given username or PUUID
 * $matches_count => The full count of all locally stored matchid.json files 
 * $count => the countervalue to display the amount of locally stored files in which the player (PUUID) is part of
 * 
 * Returnvalue:
 * N/A, displaying on page via table
 */
function getMatchDetailsByPUUID($matchIDArray, $puuid){
    global $currentpatch;
    $matches_count = scandir("/var/www/html/wordpress/clashapp/data/matches/");
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
                        if($champion == "FiddleSticks"){$champion = "Fiddlesticks";} // TODO One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ 
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/img/champion/'.$champion.'.png')){
                            echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/champion/'.$champion.'.png" width="32" style="vertical-align:middle">';
                            echo " ".$inhalt->info->participants[$in]->championName . "</td>";
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle">';
                            echo " N/A</td>";
                        }

                        // Display of the equipped keyrune + secondary tree
                        echo "<td>Runes: ";
                        $keyrune = $inhalt->info->participants[$in]->perks->styles[0]->selections[0]->perk;
                        $secrune = $inhalt->info->participants[$in]->perks->styles[1]->style;
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/img/'.runeIconFetcher($keyrune))){
                            echo '<img src="/clashapp/data/patch/img/'.runeIconFetcher($keyrune).'" width="32" style="vertical-align:middle">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle">';
                        }
                        if(file_exists('/var/www/html/wordpress/clashapp/data/patch/img/'.runeTreeIconFetcher($secrune))){
                            echo '<img src="/clashapp/data/patch/img/'.runeTreeIconFetcher($secrune).'" width="16" style="vertical-align:middle">';
                        } else {
                            echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle">';
                        }
                        echo "</td>";

                        // Display of the played position
                        // ToDo: Add individualPosition and role as else-options
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
                            $allitems = "item".$b;
                            $itemid = $inhalt->info->participants[$in]->$allitems;
                            if($itemid == 0){
                                echo '<img src="/clashapp/data/misc/0.png" width="32" style="vertical-align:middle">';
                            } else {
                                if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/img/item/'.$itemid.'.png')){
                                    echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/item/' . $itemid . '.png" width="32" style="vertical-align:middle">';
                                } else if(file_exists('/var/www/html/wordpress/clashapp/data/misc/'.$itemid.'.png')){
                                    echo '<img src="/clashapp/data/misc/'.$itemid.'.png" width="32" style="vertical-align:middle">';
                                } else {
                                    echo '<img src="/clashapp/data/misc/na.png" width="32" style="vertical-align:middle">';
                                }
                            }
                        }
                        echo '</td>';

                        // Display of the user Vision and Wardscore
                        echo '<td>Vision Score: ';
                        echo $inhalt->info->participants[$in]->visionScore . " Wards: ";
                        echo $inhalt->info->participants[$in]->wardsPlaced . "x ";
                        echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/item/3340.png" width="16" style="vertical-align:middle"> Control Wards: ';
                        echo $inhalt->info->participants[$in]->challenges->controlWardsPlaced . "x ";
                        echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/item/2055.png" width="16" style="vertical-align:middle"></td>';

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
                        $matchtype = "Solo/Duo";
                        break;
                    case 440:
                        $matchtype = "Flex 5v5";
                        break;
                    case 700:
                        $matchtype = "Clash";
                        break;
                }
                echo "<td>Matchtyp: ".$matchtype . "</td></tr>"; 
            }
        }
    }

    echo "</table>";
    // End of Matchdetail Table & Counttext of local specific amount
    echo "<br>Es wurden " . $count ." lokale Matchdaten gefunden<br>";
    echo "<pre>";
    print_r($ladezeiten);
    echo "</pre>";
}

/** Followup function to print getMasteryScores(); returninfo
 * This function is only printing collected values, also possible to shove into profile.php
 * 
 * $masteryArray => Inputarray of all MasteryScores
 * $index => Index of the masterychamp (0 = first & highest mastery champ, 1 = second, etc.)
 * 
 * Returnvalue:
 * N/A, just printing values to page
 */
function printMasteryInfo($masteryArray, $index){
    global $currentpatch;

    // TODO separate Function to fix all name errors - One-Line fixes
    // if($masteryArray[$index]["Champion"] == "FiddleSticks"){$masteryArray[$index]["Champion"] = "Fiddlesticks";}
    // if($masteryArray[$index]["Champion"] == "Kha'Zix"){$masteryArray[$index]["Champion"] = "Khazix";}
    // if($masteryArray[$index]["Champion"] == "Tahm Kench"){$masteryArray[$index]["Champion"] = "TahmKench";}
    
    // Print image if it exists
    if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.png')){
        echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/champion/'.$masteryArray[$index]["Filename"].'.png" width="64"><br>';
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
 * $id => The passed rune ID corresponding to Riot's data found in the runesReforged.json
 * 
 * Returnvalue:
 * $rune->icon => Path of Iconimage
 */
function runeIconFetcher($id){
    global $currentpatch;
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/data/de_DE/runesReforged.json');
    $json = json_decode($data);
    foreach($json as $runetree){
        foreach($runetree->slots as $keyrunes){
            foreach($keyrunes as $runeid){
                foreach($runeid as $rune){
                    if($id == $rune->id){
                        return $rune->icon;
                    }
                }
            }
        }
    }
}

/** Fetching runetree icon ID to image path
 * This function iterates through the current patches runesReforged.json and returns the folder of the runetree icons 
 * 
 * $id => The passed runetree ID corresponding to Riot's data found in the runesReforged.json
 * 
 * Returnvalue:
 * $runetree->icon => Path of Iconimage
 */
function runeTreeIconFetcher($id){
    global $currentpatch;
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/data/de_DE/runesReforged.json');
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
 * $id => The passed champion ID corresponding to Riot's data found in the champion.json
 * 
 * Returnvalue:
 * $champion->name => The clean name of the champion
 */
function championIdToName($id){
    global $currentpatch;
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/data/de_DE/champion.json');
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
 * $id => The passed champion ID corresponding to Riot's data found in the champion.json
 * 
 * Returnvalue:
 * $champion->id => The filename of the champion
 */
function championIdToFilename($id){
    global $currentpatch;
    $data = file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/data/de_DE/champion.json');
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
 * $attributesArray => Array of every attribute that we want to check via this function
 * $matchDataArray => Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * $puuid => The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * 
 * Returnvalue:
 * N/A, only direct printing to page
 */
function getMostCommon($attributesArray, $matchDataArray, $puuid){
    $mostCommonArray = array();

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

    // Count, Sort and Slice to retrieve printable data
    foreach ($attributesArray as $attribute){ 
        $temp[$attribute] = array_count_values($mostCommonArray[$attribute]);
        arsort($temp[$attribute]);
        $values[$attribute] = array_slice(array_keys($temp[$attribute]), 0, 3, true);
        $count[$attribute] = array_slice(array_values($temp[$attribute]), 0, 3, true);
        echo "<pre>";
        echo "Most common " . $attribute . ": <br>";
        echo $count[$attribute][0]." mal ".$values[$attribute][0]." -> ".$count[$attribute][1]." mal ".$values[$attribute][1]." -> ".$count[$attribute][2]." mal ".$values[$attribute][2];
        echo "</pre>";
    }
}

/** Fetches the average value of specific attributes
 * This function retrieves the average value of a specific attribute by iterating through a users matches
 * It is possible that it gets executed multiple times for multiple attributes, therefore $attributes is an array();
 * 
 * $attributesArray => Array of every attribute that we want to check via this function
 * $matchDataArray => Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * $puuid => The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * $averageArray => The returnvalue array but not printed
 * 
 * Returnvalue:
 * N/A, only direct printing to page
 */
function getAverage($attributesArray, $matchDataArray, $puuid){
    $averageArray = array();
    
    // Store all values into separate array corresponding to each attribute
    foreach ($matchDataArray as $matchData) {
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid) {
                foreach ($attributesArray as $attribute){
                    $averageArray[$attribute] += $matchData->info->participants[$i]->$attribute;
                    
                }
            }
        }
    }

    // Count & Round to retrieve printable data
    foreach ($averageArray as $key => $arrayElement){
        echo "<pre>";
        echo "Average of " . $key . ": ";
        echo ($averageArray[$key] = round($arrayElement / count($matchDataArray)));
        echo "</pre>";
    }
}

/** getHighestWinrateOrMostLossesAgainst Aliase
 *  Aliase for the two getHighestWinrateOrMostLossesAgainst function possibilities to make it clearer
 */
function getMostLossesAgainst($variant, $matchDataArray, $puuid){ getHighestWinrateOrMostLossesAgainst("mostLosses", $variant, $matchDataArray, $puuid);}
function getHighestWinrateAgainst($variant, $matchDataArray, $puuid){getHighestWinrateOrMostLossesAgainst("highestWinrate", $variant, $matchDataArray, $puuid);}

/** Function to retrieve the Highest Winrate Against or Most Losses against a specific champion
 * This function is only printing collected values, also possible to shove into profile.php
 * 
 * $type => Either "mostLosses" or "highestWinrate" depending on which way the function should proceed
 * $variant => Either "lane" or "general" depending on wether you want to check for opponent laner or general disregarding if they played on the same lane
 * $matchDataArray => Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * $puuid => The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * $returnArray => The final array where we story the collected values
 * $maxCountArray => The array to collect all counts
 *                   E.g. disregarding the functions $type the highest count of a match against enemy player, like if the player played the most against Yasuo with 42 matches
 *                   Then takes this 42 matches and halves it for the maxCount to shorten the returnArray later and unsset any value with too low counts
 * $champArray => In the second half of this function the containing all the champion data from "Win", "Lose", "Count" and "Winrate"
 * 
 * Returnvalue:
 * N/A, just printing values to page
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
    $maxCount = floor(reset($maxCountArray)/2); // $maxCount => Halve of first element in array
    
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
 * $matchDataArray => Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * $puuid => The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * $mostPlayedArray => The returnvalue array but not printed
 * 
 * Returnvalue:
 * N/A, only direct printing to page
 */
function mostPlayedWith($matchDataArray, $puuid){
    $mostPlayedArray = array();

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
    echo "<pre>";
    echo "Most played with:<br>";
    echo $count[0]." mal mit ".$value[0]."<br>".$count[1]." mal mit ".$value[1]."<br>".$count[2]." mal mit ".$value[2]."<br>".$count[3]." mal mit ".$value[3]."<br>".$count[4]." mal mit ".$value[4];
    echo "</pre>";
}

/** Prints the champion and info a given player by $puuid has the highest winrate with
 * This function is only printing collected values, also possible to shove into profile.php
 * 
 * $lane => Either "TOP", "JUNGLE", "MID", "BOT" or "UTILITY", but also "GENERAL" (all lanes) possible
 * $matchDataArray => Inputarray of all MatchIDs of the user (PUUID) over which we iterate
 * $puuid => The summoners PUUID necessary to confirm that the users matches are in our local stored data
 * $highestWinrateArray => Returnarray which is not printed but contains the final data
 * 
 * Returnvalue:
 * N/A, just printing values to page
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
    $maxCount = floor(reset($maxCountArray)/2); // $maxCount => Halve of first element in array

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

/** Same as getPlayerData but with all the team info available
 * This function is collected any values of a team by a given teamID
 * 
 * $teamID => The necessary ID of the team, received beforehand via if(isset($_POST['sumname']))
 * $teamDataArray => Just the $teamOutput content but rearranged and renamed
 * $httpcode => Contains the returncode of the curl request (e.g. 404 not found)
 * 
 * Returnvalue:
 * $teamDataArray with keys "TeamID", "TournamentID", "Name", "Tag", "Icon", "Tier", "Captain" and the array itself of "Players"
 */
function getTeamByTeamID($teamID){
    global $headers;
    $teamDataArray = array();

    // Curl API request block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $teamOutput = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // 403 Access forbidden -> Outdated API Key
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }
    
    // 429 Too Many Requests 
    if($httpcode == "429"){
        sleep(121);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $teamOutput = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

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
 * following steps and e.g. the getTeamByTeamID($teamID) function above.
 * 
 * Returnvalue:
 * None, echo'ing teamID back to javascript to open new windows with it appended
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
    $clash_output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 429 Too Many Requests 
    if($httpcode == "429"){
        sleep(121);        
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/players/by-summoner/" . $playerData["SumID"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $clash_output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // Decode and echo returned data, if not existent send to 404 page
    $clashData = json_decode($clash_output, true);
    if(isset($clashData[0]["teamId"])){
        echo $clashData[0]["teamId"];
    } else {
        echo "404";
    }
}
?>