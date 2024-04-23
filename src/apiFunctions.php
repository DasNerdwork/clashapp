<?php
require_once '/hdd1/clashapp/db/mongo-db.php';

/** Main functions.php containing overall used functions throughout different php files
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 *
 * Initializing of global variables used throughout all functions below
 *
 * @global mixed $apiKey The API Key necessary to communicate with the Riot API, to edit: nano /etc/nginx/fastcgi_params then service nginx restart
 * @global int $counter Necessary counter variable for the getMatchByID Function
 * @global array $headers The headers required or at least recommended for the CURL request
 * @global int $currenttimestam The current time stamp usable as a global variable
 */

$apiKey = getenv('API_KEY');
global $mdb;
$mdb = new MongoDBHelper();
$counter = 0;
global $headers; // Necessary for PHPUnit
$headers = array(
    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36",
    "Accept-Language: de-DE,de;q=0.9,en-US;q=0.8,en;q=0.7",
    "Accept-Charset: application/x-www-form-urlencoded; charset=UTF-8",
    "Origin: https://clashscout.com/",
    "X-Riot-Token: ".$apiKey
);
global $apiRequests; // Necessary for PHPUnit
$apiRequests = array(
    "total" => 0,
    "API::getPlayerData" => 0,
    "API::getMatchIDs" => 0,
    "API::getMasteryScores" => 0,
    "API::getCurrentRank" => 0,
    "API::downloadMatchesByID" => 0,
    "API::getTeamByTeamID" => 0,
    "postSubmit" => 0
);

class API {
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
    * @return array $playerDataArray with keys "Icon", "GameName", "Tag", "Level", "PUUID", "SumID", "AccountID" and "LastChange" of the summoners profile
    */
    public static function getPlayerData($type, $id){
        global $headers, $apiRequests;
        $playerDataArray = array();
        $retryAttempts = 0;

        do {
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
            curl_setopt($ch, CURLOPT_HEADER, 1); // Include headers in the response
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeader = substr($response, 0, $header_size);
            $type == "sumid" ? $outputSummoners = substr($response, $header_size) : $output = substr($response, $header_size);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $apiRequests["API::getPlayerData"]++; // Increment API requests count

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Check for 429 (Rate Limit Exceeded)
            if ($httpCode == 429) {
                // @codeCoverageIgnoreStart
                // Extract the Retry-After header value
                preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present

                // Uncomment the line below if you want to keep track of the API requests count
                // $apiRequests["API::getPlayerData"]++;

                // Implement additional handling if needed
                // For example, you may want to wait for a specific duration and then retry the request.
                sleep($retryAfterValue);

                // Retry the request
                $retryAttempts++;
                // @codeCoverageIgnoreEnd
            } else {
                $retryAttempts = 0; // Reset retry attempts if successful response is received
            }

            curl_close($ch);

            // 403 Access forbidden -> Outdated API Key
            if ($httpCode == 403) {
                // @codeCoverageIgnoreStart
                echo "<h2>403 Forbidden API::getPlayerData</h2>";
                die;
                // @codeCoverageIgnoreEnd
            }

        } while ($retryAttempts < 3 && $httpCode == 429 && isset($retryAfterValue));

        if ($httpCode == 200) {
            if($type != "sumid"){
                $requestUrlVar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/";
                
                // Additional API requests with the same 429 treatment
                do {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $requestUrlVar . json_decode($output)->puuid);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_HEADER, 1); // Include headers in the response
                    $response = curl_exec($ch);
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $responseHeader = substr($response, 0, $header_size);
                    $outputSummoners = substr($response, $header_size);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $apiRequests["API::getPlayerData"]++; // Increment API requests count

                    // Check for 429 (Rate Limit Exceeded)
                    if ($httpCode == 429) {
                        // @codeCoverageIgnoreStart
                        // Extract the Retry-After header value
                        preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                        $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present

                        // Uncomment the line below if you want to keep track of the API requests count
                        // $apiRequests["API::getPlayerData"]++;

                        // Implement additional handling if needed
                        // For example, you may want to wait for a specific duration and then retry the request.
                        sleep($retryAfterValue);

                        // Retry the request
                        $retryAttempts++;
                        // @codeCoverageIgnoreEnd
                    } else {
                        $retryAttempts = 0; // Reset retry attempts if successful response is received
                    }

                    curl_close($ch);

                } while ($retryAttempts < 3 && $httpCode == 429 && isset($retryAfterValue));
            } else {
                $requestUrlVar = "https://europe.api.riotgames.com/riot/account/v1/accounts/by-puuid/";
                
                // Additional API requests with the same 429 treatment
                do {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $requestUrlVar . json_decode($outputSummoners)->puuid);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_HEADER, 1); // Include headers in the response
                    $response = curl_exec($ch);
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $responseHeader = substr($response, 0, $header_size);
                    $output = substr($response, $header_size);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $apiRequests["API::getPlayerData"]++; // Increment API requests count

                    // Check for 429 (Rate Limit Exceeded)
                    if ($httpCode == 429) {
                        // @codeCoverageIgnoreStart
                        // Extract the Retry-After header value
                        preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                        $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present

                        // Uncomment the line below if you want to keep track of the API requests count
                        // $apiRequests["API::getPlayerData"]++;

                        // Implement additional handling if needed
                        // For example, you may want to wait for a specific duration and then retry the request.
                        sleep($retryAfterValue);

                        // Retry the request
                        $retryAttempts++;
                        // @codeCoverageIgnoreEnd
                    } else {
                        $retryAttempts = 0; // Reset retry attempts if successful response is received
                    }

                    curl_close($ch);

                } while ($retryAttempts < 3 && $httpCode == 429 && isset($retryAfterValue));
            }

            // Collect requested values in returnarray
            $playerDataArray["Icon"] = json_decode($outputSummoners)->profileIconId;
            isset($playerDataArray["Name"]) ? json_decode($outputSummoners)->name : NULL; // DEPRECATED
            $playerDataArray["GameName"] = json_decode($output)->gameName;
            $playerDataArray["Tag"] = json_decode($output)->tagLine;
            $playerDataArray["Level"] = json_decode($outputSummoners)->summonerLevel;
            $playerDataArray["PUUID"] = json_decode($outputSummoners)->puuid;
            $playerDataArray["SumID"] = json_decode($outputSummoners)->id;
            $playerDataArray["AccountID"] = json_decode($outputSummoners)->accountId;
            $playerDataArray["LastChange"] = json_decode($outputSummoners)->revisionDate;
        }

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
    public static function getMasteryScores($puuid){
        global $headers, $apiRequests;
        $masteryDataArray = array();
        $masteryReturnArray = array();
        $retryAttempts = 0;

        do {
            // Curl API request block
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-puuid/".$puuid);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 1); // Include headers in the response

            $response = curl_exec($ch); $apiRequests["API::getMasteryScores"]++;
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeader = substr($response, 0, $header_size);
            $output = substr($response, $header_size);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Check for 429 (Rate Limit Exceeded)
            if ($httpCode == 429) {
                // @codeCoverageIgnoreStart
                preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present

                sleep($retryAfterValue);

                $retryAttempts++;
                // @codeCoverageIgnoreEnd
            } else {
                $retryAttempts = 0; // Reset retry attempts if successful response is received
            }

            curl_close($ch);

            // 403 Forbidden
            if ($httpCode == 403) {
                // @codeCoverageIgnoreStart
                echo "<h2>403 Forbidden MasteryScores</h2>";
                die;
                // @codeCoverageIgnoreEnd
            }

        } while ($retryAttempts < 3 && $httpCode == 429 && isset($retryAfterValue));

        if ($retryAttempts < 3 && $httpCode == 200) {
            $jsonOutput = json_decode($output, true);
            foreach ($jsonOutput as $masteryArray){
                if ($masteryArray["championLevel"] > 4 || $masteryArray["championPoints"] > 19999){
                    $masteryDataArray["Champion"] = championIdToName($masteryArray["championId"]);
                    $masteryDataArray["Filename"] = championIdToFilename($masteryArray["championId"]);
                    $masteryDataArray["Lvl"] = $masteryArray["championLevel"];
                    $masteryDataArray["Points"] = number_format($masteryArray["championPoints"]);
                    $masteryDataArray["LastPlayed"] = $masteryArray["lastPlayTime"]/1000; // to get human-readable one -> date('d.m.Y H:i:s', $masteryData["LastPlayed"]);
                    // in case tokens for lvl 6 or 7 in inventory add them too
                    if ($masteryArray["tokensEarned"] > 0){
                        $masteryDataArray["LvlUpTokens"] = $masteryArray["tokensEarned"];
                    }
                    $masteryReturnArray[] = $masteryDataArray;
                }
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
    public static function getCurrentRank($sumid) {
        global $headers, $apiRequests;
        $rankDataArray = array();
        $rankReturnArray = array();
        $retryAttempts = 0;

        do {
            // Curl API request block
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $response = curl_exec($ch); $apiRequests["API::getCurrentRank"]++;
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeader = substr($response, 0, $header_size);
            $output = substr($response, $header_size);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Check for 429 (Rate Limit Exceeded)
            if ($httpCode == 429) {
                // @codeCoverageIgnoreStart
                // Extract the Retry-After header value
                preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present

                // Rate limit exceeded. Retry after {$retryAfterValue} seconds

                sleep($retryAfterValue);

                // Retry the request
                $retryAttempts++;
                // @codeCoverageIgnoreEnd
            } else {
                $retryAttempts = 0; // Reset retry attempts if successful response is received
            }

            curl_close($ch);

            // 403 Forbidden
            if ($httpCode == 403) {
                // @codeCoverageIgnoreStart
                echo "<h2>403 Forbidden CurrentRank</h2>";
                die;
                // @codeCoverageIgnoreEnd
            }

        } while ($retryAttempts < 3 && $httpCode == 429 && isset($retryAfterValue));

        if ($retryAttempts < 3 && $httpCode == 200) {
            $jsonOutput = json_decode($output, true);
            if (is_array($jsonOutput)) {
                foreach ($jsonOutput as $requestArray) {
                    if (isset($requestArray["queueType"]) && ($requestArray["queueType"] == "RANKED_SOLO_5x5" || $requestArray["queueType"] == "RANKED_FLEX_SR")) {
                        $rankDataArray["Queue"] = $requestArray["queueType"];
                        $rankDataArray["Tier"] = $requestArray["tier"];
                        $rankDataArray["Rank"] = $requestArray["rank"];
                        $rankDataArray["LP"] = $requestArray["leaguePoints"];
                        $rankDataArray["Wins"] = $requestArray["wins"];
                        $rankDataArray["Losses"] = $requestArray["losses"];
                        $rankReturnArray[] = $rankDataArray;
                    }
                }
            }
        }
        return $rankReturnArray;
    }

    /** Array of MatchIDs TODO: Fix Max exection timeout error?
     * This function retrieves all match IDs of a given PUUID up to a specified maximum
    * Eq. to https://developer.riotgames.com/apis#match-v5/GET_API::getMatchIDsByPUUID
    *
    * @param string $puuid Necessary PUUID of the summoner (Obtainable either through API::getPlayerData or via local stored file)
    * @param int $maxMatchIDs The maximum count to which we request matchIDs
    * @var string $gameType Set to the queue type of league "ranked", "normal", "tourney" or "tutorial"
    * @var int $start Starting at 0 and iterating by +100 every request (100 is the maximum of matchIDs you can request at once)
    * @var mixed $matchCount Always equals 100 except if it exceeds maxMatchIDs in it's next iteration, then set to max available
    *                             E.g. maxMatchIDs = 219, 1. Iteration = 100, 2. Iteration = 100, 3. Iteration = 19
    *
    * Returnvalue:
    * @return array $matchIDArray with all MatchIDs as separate entries
    */
    public static function getMatchIDs($puuid, $maxMatchIDs){
        global $headers, $apiRequests;
        $soloduoIDArray = array();
        $flexIDArray = array();
        $clashIDArray = array();
        $gameType = "ranked";
        $start = 0;
        $matchCount = "100";
        $retryAttempts = 0;
        $clashFinished = false;
        $soloDuoFinished = false;
        $flexFinished = false;

        do {
            while ($start < $maxMatchIDs) {
                // If next iterations would exceed the max
                if(($start + 100) > $maxMatchIDs){
                    $matchCount = 100 - (($start + 100) - $maxMatchIDs);
                }

                if(!$clashFinished){
                    // Curl API request block for clash matches
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=700&type=normal&start=" . $start . "&count=" . $matchCount);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                    $response = curl_exec($ch); $apiRequests["API::getMatchIDs"]++;
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $responseHeader = substr($response, 0, $header_size);
                    $clashidOutput = substr($response, $header_size);
                    $httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $clashidOutputArray = json_decode($clashidOutput);

                    // 429 Too Many Requests
                    if ($httpCode1 == 429) {
                        // @codeCoverageIgnoreStart
                        // Extract the Retry-After header value
                        preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                        $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present
            
                        // Rate limit exceeded. Retry after {$retryAfterValue} seconds
            
                        sleep($retryAfterValue);
            
                        // Retry the request
                        $retryAttempts++;
                        // @codeCoverageIgnoreEnd
                    } else {
                        if($httpCode1 == 200 && count($clashidOutputArray) >= $maxMatchIDs){
                            $clashFinished = true;
                        }
                        $retryAttempts = 0; // Reset retry attempts if successful response is received
                    }

                    foreach ($clashidOutputArray as $clashMatch) {
                        $clashIDArray[] = $clashMatch;
                    }
                }

                // -------------------------------------------------------------------------------------------------------------------------------------------------------------------

                if(!$flexFinished){
                    // Curl API request for flex matches
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=440&type=" . $gameType . "&start=" . $start . "&count=" . $matchCount);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                    $response = curl_exec($ch); $apiRequests["API::getMatchIDs"]++;
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $responseHeader = substr($response, 0, $header_size);
                    $flexidOutput = substr($response, $header_size);
                    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $flexidOutputArray = json_decode($flexidOutput);

                    // 429 Too Many Requests
                    if ($httpCode2 == 429) {
                        // @codeCoverageIgnoreStart
                        // Extract the Retry-After header value
                        preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                        $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present
            
                        // Rate limit exceeded. Retry after {$retryAfterValue} seconds
            
                        sleep($retryAfterValue);
            
                        // Retry the request
                        $retryAttempts++;
                        // @codeCoverageIgnoreEnd
                    } else {
                        if($httpCode2 == 200 && count($flexidOutputArray) >= $maxMatchIDs){
                            $flexFinished = true;
                        }
                        $retryAttempts = 0; // Reset retry attempts if successful response is received
                    }
                    
                    foreach ($flexidOutputArray as $flexMatch) {
                        $flexIDArray[] = $flexMatch;
                    }
                }

                // -------------------------------------------------------------------------------------------------------------------------------------------------------------------
                
                if(!$soloDuoFinished){
                    // Curl API request for solo duo matches
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?queue=420&type=" . $gameType . "&start=" . $start . "&count=" . $matchCount);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_HEADER, 1);
                    $response = curl_exec($ch); $apiRequests["API::getMatchIDs"]++;
                    $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                    $responseHeader = substr($response, 0, $header_size);
                    $soloduoidOutput = substr($response, $header_size);
                    $httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    $soloduoidOutputArray = json_decode($soloduoidOutput);

                    // 429 Too Many Requests
                    if ($httpCode3 == 429) {
                        // @codeCoverageIgnoreStart
                        // Extract the Retry-After header value
                        preg_match('/Retry-After: (\d+)/', $responseHeader, $matches);
                        $retryAfterValue = isset($matches[1]) ? (int)$matches[1] : 10; // Default to 10 seconds if Retry-After is not present
            
                        // Rate limit exceeded. Retry after {$retryAfterValue} seconds
            
                        sleep($retryAfterValue);
            
                        // Retry the request
                        $retryAttempts++;
                        // @codeCoverageIgnoreEnd
                    } else {
                        if($httpCode3 == 200 && count($soloduoidOutputArray) >= $maxMatchIDs){
                            $soloDuoFinished = true;
                        }
                        $retryAttempts = 0; // Reset retry attempts if successful response is received
                    }

                    foreach ($soloduoidOutputArray as $soloduoMatch) {
                        $soloduoIDArray[] = $soloduoMatch;
                    }
                }
                $start += 100;
            }
        } while ($retryAttempts < 3 && ($httpCode1 == 429 || $httpCode2 == 429 || $httpCode3 == 429) && isset($retryAfterValue));

        // Merge and sort clash matchids and ranked match ids
        $returnArray = array_merge($flexIDArray, $soloduoIDArray, $clashIDArray);
        rsort($returnArray);
        $returnArray = array_slice($returnArray, 0, $maxMatchIDs);

        return $returnArray;
    }



    /** Same as API::getPlayerData but with all the team info available
     * This function is collected any values of a clash team by a given teamID
    *
    * @param string $teamID The necessary ID of the team, received beforehand via if(isset($_POST['sumname']))
    * @var array teamDataArray Just the $teamOutput content but rearranged and renamed
    * @var int $httpCode Contains the returncode of the curl request (e.g. 404 not found)
    *
    * Returnvalue:
    * @return array $teamDataArray with keys "TeamID", "TournamentID", "Name", "Tag", "Icon", "Tier", "Captain" and the array itself of "Players"
    */
    public static function getTeamByTeamID($teamID){
        if($teamID != "test"){
            // @codeCoverageIgnoreStart
            global $headers, $apiRequests;
            $teamDataArray = array();
            $logPath = '/hdd1/clashapp/data/logs/teamDownloader.log';

            // Curl API request block
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $teamOutput = curl_exec($ch); $apiRequests["API::getTeamByTeamID"]++;
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            // 403 Access forbidden -> Outdated API Key
            if($httpCode == "403"){
                echo "<h2>403 Forbidden TeamByTeamID</h2>";
                die;
            }

            // 429 Too Many Requests
            if($httpCode == "429"){
                sleep(5);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/teams/" . $teamID);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $teamOutput = curl_exec($ch); $apiRequests["API::getTeamByTeamID"]++;
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                // @codeCoverageIgnoreEnd
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
    public static function downloadMatchesByID($matchids, $username = null){
        global $mdb;
        global $headers, $counter, $apiRequests;
        $logPath = '/hdd1/clashapp/data/logs/matchDownloader.log';

        foreach($matchids as $matchid){

            // Halving of matchDownloader.log in case the logfile exceeds 10 MB
            if(filesize($logPath) > 10000000 && $counter == 0){
                // @codeCoverageIgnoreStart
                $counter++;
                $file = file($logPath);
                $file = array_chunk($file, ceil(count($file)/2))[1];
                file_put_contents($logPath, $file, LOCK_EX);
                clearstatcache(true, $logPath);
                $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                $slimmed = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Maximum filesize exceeded, removed first half of logfile - Status: OK (Size ".number_format((filesize($logPath)/1048576), 3)." MB)";
                file_put_contents($logPath, $slimmed.PHP_EOL , FILE_APPEND | LOCK_EX);
                $counter = 0;
                // @codeCoverageIgnoreEnd
            }

            // Only download if file doesn't exist yet

            if(!$mdb->findDocumentByField("matches", 'metadata.matchId', $matchid)["success"]){
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $matchOutput = curl_exec($ch); $apiRequests["API::downloadMatchesByID"]++;
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);


                // 429 Too Many Requests -> HITTING LOWER RATE LIMIT OF --- 20 requests every 1 seconds ---
                if($httpCode == "429"){
                    // @codeCoverageIgnoreStart
                    sleep(1);
                    $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                    $limit = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Lower Rate limit got exceeded -> Now sleeping for 1 second - Status: " . $httpCode . " Too Many Requests";
                    file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
                    curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    $matchOutput = curl_exec($ch); $apiRequests["API::downloadMatchesByID"]++;
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
                        $matchOutput = curl_exec($ch); $apiRequests["API::downloadMatchesByID"]++;
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        // @codeCoverageIgnoreEnd
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
                    // @codeCoverageIgnoreStart
                    $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                    $warning = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: " . $matchid . " received HTTP-Code: " . $httpCode . " - Skipping";
                    file_put_contents($logPath, $warning.PHP_EOL , FILE_APPEND | LOCK_EX);
                }
            }else{
                $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                $noAnswer = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: " . $matchid . ".json already existing - Skipping";
                file_put_contents($logPath, $noAnswer.PHP_EOL , FILE_APPEND | LOCK_EX);
                // @codeCoverageIgnoreEnd
            }
        }
        // return array("Status" => "Success", "ErrorFile" => $errorFile);
        return true;
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
     * following steps and e.g. the API::getTeamByTeamID($teamID) function.
     *
     * @var string $playerName A summoners ingame name, fetched by the POST (last part of URL)
     * @var array $playerData Either the API requested playerdata or the locally stored one, used to retrieve the SumID
     *
     * Returnvalue:
     * @return void None, echo'ing teamID back to javascript to open new windows with it appended
     */
    public static function handlePagePost($sumname){
        global $headers, $apiRequests;
        $playerName = preg_replace('/\s+/', '', $sumname);
        $playerName = str_replace("#","/",$playerName);
        $playerData = API::getPlayerData("riot-id",$playerName);

        // Curl API request block
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/tournaments");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $tournamentsOutput = curl_exec($ch); $apiRequests["postSubmit"]++;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if($httpCode == "200" && isset($playerData["SumID"])){

            if(empty(json_decode($tournamentsOutput, true))){
                // @codeCoverageIgnoreStart
                return false;
                // @codeCoverageIgnoreEnd
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
                    // @codeCoverageIgnoreStart
                    sleep(5);
                    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/clash/v1/players/by-summoner/" . $playerData["SumID"]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    $clashOutput = curl_exec($ch); $apiRequests["postSubmit"]++;
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    // @codeCoverageIgnoreEnd
                }

                // Decode and echo returned data, if not existent send to 404 page
                $clashData = json_decode($clashOutput, true);
                if(isset($clashData[0]["teamId"])){
                    // @codeCoverageIgnoreStart
                    return $clashData[0]["teamId"];
                } else {
                    return '404';
                    // @codeCoverageIgnoreEnd
                }
            }
        }
        return '404';
    }
}

if(isset($_POST['sumname'])){
    API::handlePagePost($_POST['sumname']);
}