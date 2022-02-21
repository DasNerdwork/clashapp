<?php 
$api_key = "RGAPI-c58eb628-8945-4056-a569-152fbebca9a9"; // ToDo
$currentpatch = file_get_contents("/var/www/html/wordpress/clashapp/data/patch/version.txt");
$counter = 0;

function getPlayerData($type, $username){
    // initialize api_key variable
    global $api_key, $currentpatch;
    $playerData = array();
    
    if($type == "name"){
        $requesturlvar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/";
    } else if($type == "puuid"){
        $requesturlvar = "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-puuid/";
    }

    // initialize playerdata curl request by username
    $ch = curl_init();
    // set url & return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, $requesturlvar . $username . "?api_key=" . $api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    // $output contains the output string & $httpcode contains the returncode (e.g. 404 not found)
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // close curl resource to free up system resources
    curl_close($ch);
    
    // fetch if 403 Access forbidden -> outdated API Key
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }
    
    // fetch if maximum requests reached
    if($httpcode == "429"){
        sleep(121); // wait 120 seconds until max requests reset
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requesturlvar . $username . "?api_key=" . $api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // collect requested values in array
    $playerData["Icon"] = json_decode($output)->profileIconId;
    $playerData["Name"] = json_decode($output)->name;
    $playerData["Level"] = json_decode($output)->summonerLevel;
    $playerData["PUUID"] = json_decode($output)->puuid;
    $playerData["SumID"] = json_decode($output)->id;
    $playerData["AccountID"] = json_decode($output)->accountId;
    $playerData["LastChange"] = json_decode($output)->revisionDate;
  
    return $playerData;
}

// // Match ID Grabber

//     // Create Timestampf for "now"
// $date = new DateTime("now", new DateTimeZone('Europe/Berlin'));
//     //echo $date->format('Y-m-d H:i:s') . "<br>";

//     // Variables for curl request
function getMatchIDs($puuid,$maxMatchIds){
    global $api_key;
    $matchIDArray = array();
    $gametype = "ranked";
    $start = 0;
    $id_count = 100;
    $matchcount = "100";



    
    while ($start < $maxMatchIds) {
        if(($start + 100) > $maxMatchIds){
            $matchcount = 100 - (($start + 100) - $maxMatchIds);
        }

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gametype . "&start=" . $start . "&count=" . $matchcount . "&api_key=" . $api_key);
        // echo "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gametype . "&start=" . $start . "&count=" . $matchcount . "&api_key=" . $api_key;
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $matchid_output = curl_exec($ch);

        // print_r($matchid_output);

        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        // close curl resource to free up system resources
        curl_close($ch);

        if($httpcode == "429"){
            sleep(121);        
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gametype . "&start=".$start."&count=" . $matchcount . "&api_key=" . $api_key);

        
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
            // $output contains the output string
            $matchid_output = curl_exec($ch);
        
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            // close curl resource to free up system resources
            curl_close($ch);
        }
        
        // daten holen für die alle bisher geholten matches
        // print "<pre>";print_r($matchid_output);print "</pre>";
        // match_grabber($matchid_output,$api_key,$username);

        // echo("<pre style='background-color: #1f1f1f;'>");
        // echo("<center>[Matchhistory]</center>");
    
        foreach (json_decode($matchid_output) as $match) {
            array_push($matchIDArray, $match);

        }
        $start += 100;
        $id_count = count(json_decode($matchid_output, true));
    }
    return $matchIDArray;
}

function getMatchDetailsByPUUID($puuid){
global $currentpatch;
$matches_count = scandir("/var/www/html/wordpress/clashapp/data/matches/");
$count = 0;
echo "<table class='table'>";

for($i = count($matches_count)-1; $i > 2; $i--){
    // echo $matches_count[$i]."<br>";
    $handle = file_get_contents("/var/www/html/wordpress/clashapp/data/matches/".$matches_count[$i]);

    $inhalt = json_decode($handle);
    // print_r($inhalt->metadata->participants);

    // echo $puuid[1]."<br>";
    // print_r($inhalt->metadata->participants);

    // $k = json_encode($inhalt->metadata->participants);

    // print_r($k);
    // echo in_array($puuid[1], $inhalt->metadata->participants);
    
    if(isset($inhalt->metadata->participants) && $inhalt->info->gameDuration != 0) {
        if(in_array($puuid, (array) $inhalt->metadata->participants)){
            $count++;
            for($in = 0; $in < 10; $in++){
                if($inhalt->info->participants[$in]->puuid == $puuid) {
                    echo "<tr>";
                    if($inhalt->info->participants[$in]->win == true) {
                        echo '<td class="online" style="color:#1aa23a"><b>W</b></td>';
                    } else {
                        echo '<td class="offline" style="color:#b31414"><b>L</b></td>';
                    }
                    echo "<td>ID: ".$inhalt->metadata->matchId; 
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

                    if($inhalt->info->participants[$in]->teamPosition != "") {
                        if($inhalt->info->participants[$in]->teamPosition == "UTILITY") {
                            echo "<td>Position: SUPPORT</td>"; 
                        } else {
                            echo "<td>Position: ".$inhalt->info->participants[$in]->teamPosition . "</td>"; 
                        }
                     } else {
                        echo "<td>Position: N/A</td>"; 
                    }
                    echo "<td>KDA: ".$inhalt->info->participants[$in]->kills . "/"; 
                    echo $inhalt->info->participants[$in]->deaths . "/"; 
                    echo $inhalt->info->participants[$in]->assists . "</td>"; 
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
                    echo "</td>";
                    echo "<td>Vision Score: ";
                    echo $inhalt->info->participants[$in]->visionScore . " Wards: ";
                    echo $inhalt->info->participants[$in]->wardsPlaced . "x ";
                    echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/item/3340.png" width="16" style="vertical-align:middle">';
                    echo "</td>";

                    echo "<td>Totals: ";
                    echo $inhalt->info->participants[$in]->totalDamageDealt . " Damage, ";
                    echo $inhalt->info->participants[$in]->totalDamageDealtToChampions . " to Champions";
                    echo '</td>';

                    echo "<td>";
                    echo $inhalt->info->participants[$in]->totalDamageShieldedOnTeammates . " Shielded, ";
                    echo $inhalt->info->participants[$in]->totalHealsOnTeammates . " Healed";    
                    echo '</td>';

                    echo "<td>";
                    echo $inhalt->info->participants[$in]->totalHeal . " Selfhealed, ";
                    echo $inhalt->info->participants[$in]->totalDamageTaken . " Tanked";
                    echo '</td>';

                    echo "<td>";
                    echo $inhalt->info->participants[$in]->timeCCingOthers . " Time CCing Others, ";
                    echo $inhalt->info->participants[$in]->totalTimeCCDealt . " Time CC dealt";
                    echo '</td>';


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
            echo "</tr>";
        }
    }
}
echo "</table>";

echo "<br>Es wurden " . $count ." lokale Matchdaten gefunden";
}


function getMatchByID($matchid, $username){
    global $api_key;
    global $counter;
    // Match Grabber
    // Start curl request
    $logPath = '/var/www/html/wordpress/clashapp/data/logs/matchDownloader.log';
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
    if(!file_exists('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . ".json")){
        $ch = curl_init(); 

        // Set curl URL
        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid . "/?api_key=" . $api_key);
    
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    
        // $output contains the output string
        $match_output = curl_exec($ch);
    
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
        // close curl resource to free up system resources
        curl_close($ch);      
    
        if($httpcode == "429"){
            sleep(121);
            $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $limit = "[" . $current_time->format('d.m.Y H:i:s') . "] [matchDownloader - WARNING]: Rate limit got exceeded -> Now sleeping for 121 seconds - Status: " . $httpcode . " Too Many Requests";
            file_put_contents($logPath, $limit.PHP_EOL , FILE_APPEND | LOCK_EX);
            curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid . "/?api_key=" . $api_key);
        
            //return the transfer as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
            // $output contains the output string
            $match_output = curl_exec($ch);
        
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
            // close curl resource to free up system resources
            curl_close($ch);
        }
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

// Testing Function to execute single elements via console
// php -r "require 'functions.php'; testing();"
function testing(){
    $matches_count = scandir("/var/www/html/wordpress/clashapp/data/matches/");
    $matches_count = array_slice($matches_count, 0, 10);
    print_r($matches_count);
}

// This function iterates through the current patches runesReforged.json and returns the folder of the rune icons 
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

function getCurrentRank($sumid){
    $rankData = array();
    $rankReturnArray = array();
    global $api_key;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid."?api_key=".$api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    // fetch if 403 Access forbidden -> outdated API Key
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }  
    // fetch if maximum requests reached
    if($httpcode == "429"){
        sleep(121); // wait 120 seconds until max requests reset
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/league/v4/entries/by-summoner/".$sumid."?api_key=".$api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    foreach(json_decode($output, true) as $rankArray){
        $rankData["Queue"] = $rankArray["queueType"];
        $rankData["Tier"] = $rankArray["tier"];
        $rankData["Rank"] = $rankArray["rank"];
        $rankData["LP"] = $rankArray["leaguePoints"];
        $rankData["Wins"] = $rankArray["wins"];
        $rankData["Losses"] = $rankArray["losses"];
        array_push($rankReturnArray, $rankData);
    }
    return $rankReturnArray;
}

function getMasteryScores($sumid){
    $masteryData = array();
    $masteryReturnArray = array();
    global $api_key;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$sumid."?api_key=".$api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if($httpcode == "403"){
        echo "<h2>API Key outdated!</h2>";
    }  
    // fetch if maximum requests reached
    if($httpcode == "429"){
        sleep(121); // wait 120 seconds until max requests reset
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/champion-mastery/v4/champion-masteries/by-summoner/".$sumid."?api_key=".$api_key);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }
    foreach(json_decode($output, true) as $masteryArray){
        if($masteryArray["championLevel"] > 4 || $masteryArray["championPoints"] > 19999){
            $masteryData["Champion"] = championIdToName($masteryArray["championId"]);
            $masteryData["Lvl"] = $masteryArray["championLevel"];
            $masteryData["Points"] = number_format($masteryArray["championPoints"]);
            $masteryData["LastPlayed"] = $masteryArray["lastPlayTime"]/1000; // to get human-readable one -> date('d.m.Y H:i:s', $masteryData["LastPlayed"]);
            if($masteryArray["tokensEarned"] > 0){
                $masteryData["LvlUpTokens"] = $masteryArray["tokensEarned"]; // in case tokens for lvl 6 or 7 in inventory add them too
            }
            array_push($masteryReturnArray, $masteryData);
        }
        
    }
    return $masteryReturnArray;
}

function getMostCommon($attributes, $matchDataArray, $puuid){
    $mostCommonArray = array();
    foreach ($matchDataArray as $matchData) { // going through all files
        for($i = 0; $i < 10; $i++){//going through all player
            if($matchData->info->participants[$i]->puuid == $puuid) {
                foreach ($attributes as $attribute){
                    if($matchData->info->participants[$i]->$attribute != ""){
                        $mostCommonArray[$attribute][] = $matchData->info->participants[$i]->$attribute;
                    }
                }
            }
        }
    }

    // 3 am häufigtsten vorkommenden  "kills", "deaths" ,"assists", "teamPosition", "championName", "detectorWardsPlaced", "visionScore"

    foreach ($attributes as $attribute){ 
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

function getAverage($attributes, $matchDataArray, $puuid){
    $averageArray = array();
    // print_r($matchDataArray);die();
    foreach ($matchDataArray as $matchData) { // going through all files
        for($i = 0; $i < 10; $i++){
            if($matchData->info->participants[$i]->puuid == $puuid) {
                foreach ($attributes as $attribute){
                    $averageArray[$attribute] += $matchData->info->participants[$i]->$attribute;
                    
                }
            }
        }
    }
    foreach ($averageArray as $key => $arrayElement){
        echo "<pre>";
        echo "Average of " . $key . ": ";
        echo ($averageArray[$key] = round($arrayElement / count($matchDataArray)));
        echo "</pre>";
    }
}

function mostPlayedWith($matchDataArray, $puuid){
    $mostPlayedArray = array();
    foreach ($matchDataArray as $matchData) { // going through all files
        for($i = 0; $i < 10; $i++){//going through all player
            if($matchData->info->participants[$i]->puuid != $puuid){
                $mostPlayedArray[] = $matchData->info->participants[$i]->summonerName;
            }
        }
    }

    $temp = array_count_values($mostPlayedArray);
    arsort($temp);
    $value = array_slice(array_keys($temp), 0, 5, true);
    $count = array_slice(array_values($temp), 0, 5, true);
    echo "<pre>";
    echo "Most played with:<br>";
    echo $count[0]." mal mit ".$value[0]."<br>".$count[1]." mal mit ".$value[1]."<br>".$count[2]." mal mit ".$value[2]."<br>".$count[3]." mal mit ".$value[3]."<br>".$count[4]." mal mit ".$value[4];
    echo "</pre>";
}

function getMatchData($matchIDArray){
    // print_r($matchIDArray);
    $startMemory = memory_get_usage();


    $matchData = array();
    foreach ($matchIDArray as $key => $matchIDJSON) { // going through all files
    
    if(memory_get_usage() - $startMemory > "268435456" || $key == 500)return $matchData; // If matchData array bigger than 256MB size or more than 500 matches -> return
        $matchData[$key] = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/matches/'.$matchIDJSON.'.json'));
    }
    // echo memory_get_usage() - $startMemory, ' bytes';
    // echo memory_get_usage() ,' bytes';
    return $matchData;
}

function printMasteryInfo($masteryArray, $index){
    global $currentpatch;
    if($masteryArray[$index]["Champion"] == "FiddleSticks"){$masteryArray[$index]["Champion"] = "Fiddlesticks";} // TODO One-Line fix for Fiddlesticks naming done, still missing renaming of every other champ 
    if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/img/champion/'.$masteryArray[$index]["Champion"].'.png')){
        echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/champion/'.$masteryArray[$index]["Champion"].'.png" width="64"><br>';
    }
    echo $masteryArray[$index]["Champion"]."<br>";
    echo "<br>Mastery Level: ".$masteryArray[$index]["Lvl"]."<br>";
    echo "Points: ".$masteryArray[$index]["Points"]."<br>";
    echo "Last played: ".date('d.m.Y', $masteryArray[$index]["LastPlayed"]);
}
?>



