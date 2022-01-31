<?php 
// TODO add following code after finishing: if (strstr($_SERVER['HTTP_REFERER'],"dasnerdwork.net/clash")) {
$api_key = "RGAPI-334c99a9-1271-4ea8-8007-2f3cc9df9342";
$currentpatch = file_get_contents("/var/www/html/wordpress/clashapp/data/patch/version.txt");

function getPlayerData($username){

    // initialize api_key variable
    global $api_key, $currentpatch;
    $playerData = array();
    
    // initialize playerdata curl request by username
    $ch = curl_init();
    // set url & return the transfer as a string
    curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/" . $username . "?api_key=" . $api_key);
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
        curl_setopt($ch, CURLOPT_URL, "https://euw1.api.riotgames.com/lol/summoner/v4/summoners/by-name/" . $username . "?api_key=" . $api_key);
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

    // print collected values
    if(file_exists('/var/www/html/wordpress/clashapp/data/patch/'.$currentpatch.'/img/profileicon/'.$playerData["Icon"].'.png')){
        echo '<img src="/clashapp/data/patch/'.$currentpatch.'/img/profileicon/'.$playerData["Icon"].'.png" width="64"><br>';
    }
    echo "Name: " . $playerData["Name"] . "<br>";
    echo "Level: " . $playerData["Level"] . "<br>";
    $rankedinfo = getCurrentRank($playerData["SumID"]);
    echo "Rank: " . $rankedinfo["Tier"] . " " . $rankedinfo["Rank"] . " mit " . $rankedinfo["LP"] . "LP in " . $rankedinfo["Queue"] . "<br>";
    echo "Wins: " . $rankedinfo["Wins"] . " / Losses: " . $rankedinfo["Losses"] . " - Winrate: " . round((($rankedinfo["Wins"]/($rankedinfo["Wins"]+$rankedinfo["Losses"]))*100),2) . "%<br><br>";
    echo "<b>! For Testing Purposes Only !</b><br>";
    echo "PUUID: " . $playerData["PUUID"] . "<br>";
    echo "SumID: " . $playerData["SumID"] . "<br>";
    echo "AccountID: " . $playerData["AccountID"] . "<br>";
    echo "LastChange: " . $playerData["LastChange"] . "<br><br>";
  
    return $playerData;
}

// // Match ID Grabber

//     // Create Timestampf for "now"
// $date = new DateTime("now", new DateTimeZone('Europe/Berlin'));
//     //echo $date->format('Y-m-d H:i:s') . "<br>";

//     // Variables for curl request
function grabMatches($puuid, $username){
    global $api_key;
    $starttime = "1640991600"; //01.01.22 0:00h
    $gametype = "ranked";
    $matchcount = "100";
    $start = 0;
    $id_count = 100;
    $i = 0;
    
    while ($id_count == 100) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gametype . "&start=" . $start . "&count=" . $matchcount . "&api_key=" . $api_key);

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // $output contains the output string
        $matchid_output = curl_exec($ch);

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
        
        //daten holen für die alle bisher geholten matches
        // print "<pre>";print_r($matchid_output);print "</pre>";
        match_grabber($matchid_output,$api_key,$username);

        // echo("<pre style='background-color: #1f1f1f;'>");
        // echo("<center>[Matchhistory]</center>");
        
        // foreach (json_decode($matchid_output) as $match) {
        //     echo("ID" . $i . ": " . $match . "<br>");
        //     $i++;
        // }
        // echo("</pre>");


        $start += 100;
        $id_count = count(json_decode($matchid_output, true));
        $i += count(json_decode($matchid_output, true));
    }


//     $i = 0;
    echo "Gefundene Matchdaten: $i";

}

function getMatchesByPUUID($puuid){
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
    
    if(isset($inhalt->metadata->participants)) {
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


function match_grabber($matchid_output, $api_key, $username){
    // Match Grabber
    foreach (json_decode($matchid_output) as $matchid) {

        // Start curl request
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
                curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/" . $matchid . "/?api_key=" . $api_key);
            
                //return the transfer as a string
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            
                // $output contains the output string
                $match_output = curl_exec($ch);
            
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
                // close curl resource to free up system resources
                curl_close($ch);
            }
            // echo("<pre style='background-color: #1f1f1f; height: 1000px; width: 100%;'>");
            // echo json_encode(json_decode($match_output), JSON_PRETTY_PRINT);

            $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $answer = "[" . $current_time->format('H:i:s') . "] Got new matchdata from " . $username . ": " . $matchid . ".json - Status: " . $httpcode . "\n<br>";
            echo $answer;
            $myfile = file_put_contents('/var/www/html/wordpress/clashapp/data/matches/log.txt', $answer.PHP_EOL , FILE_APPEND | LOCK_EX);
            // echo("</pre>");
            // $i++;
            
            $fp = fopen('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . '.json', 'w');
            fwrite($fp, $match_output);
            fclose($fp);
        }else{
            $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $noanswer = "[" . $current_time->format('H:i:s') . "] " . $matchid . ".json existiert bereits\n<br>";
            echo $noanswer;
            $myfile = file_put_contents('/var/www/html/wordpress/clashapp/data/matches/log.txt', $noanswer.PHP_EOL , FILE_APPEND | LOCK_EX);
        }
    }
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
    $rankData["Queue"] = json_decode($output)[0]->queueType;
    $rankData["Tier"] = json_decode($output)[0]->tier;
    $rankData["Rank"] = json_decode($output)[0]->rank;
    $rankData["LP"] = json_decode($output)[0]->leaguePoints;
    $rankData["Wins"] = json_decode($output)[0]->wins;
    $rankData["Losses"] = json_decode($output)[0]->losses;
    return $rankData;
}






// TODO add following code after finishing: 
// } else {
//     http_response_code("403");
// }
?>



