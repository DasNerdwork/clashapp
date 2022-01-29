<?php 
// TODO add following code after finishing: if (strstr($_SERVER['HTTP_REFERER'],"dasnerdwork.net/clash")) {

function getPlayerData($username){

    // initialize api_key variable
    $api_key = "***REMOVED***";
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
    $playerData["Name"] = json_decode($output)->name;
    $playerData["PUUID"] = json_decode($output)->puuid;
    $playerData["SumID"] = json_decode($output)->id;
    $playerData["AccountID"] = json_decode($output)->accountId;
    $playerData["Level"] = json_decode($output)->summonerLevel;
    $playerData["Icon"] = json_decode($output)->profileIconId;
    $playerData["LastChange"] = json_decode($output)->revisionDate;

    // print collected values
    echo "Name: " . $playerData["Name"] . "<br>";
    echo "PUUID: " . $playerData["PUUID"] . "<br>";
    echo "SumID: " . $playerData["SumID"] . "<br>";
    echo "AccountID: " . $playerData["AccountID"] . "<br>";
    echo "Level: " . $playerData["Level"] . "<br>";
    echo "Icon: " . $playerData["Icon"] . "<br>";
    echo "LastChange: " . $playerData["LastChange"] . "<br>";
  
    return $playerData;
}

// // Match ID Grabber

//     // Create Timestampf for "now"
// $date = new DateTime("now", new DateTimeZone('Europe/Berlin'));
//     //echo $date->format('Y-m-d H:i:s') . "<br>";

//     // Variables for curl request
function grabMatches(){
if(isset(json_decode($output)->puuid)) {
    $puuid = json_decode($output)->puuid;
}
    $starttime = "1640991600"; //01.01.22 0:00h
    // $endtime = $date->getTimestamp();
    $gametype = "ranked";
    $matchcount = "100";

    // Start curl request

    // Set curl URL
    // curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?startTime=" . $starttime . "&endTime=" . $endtime . "&type=" . $gametype . "&start=0&count=" . $matchcount . "&api_key=" . $api_key);
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

//         if($httpcode == "429"){
//             sleep(121);        
//             curl_setopt($ch, CURLOPT_URL, "https://europe.api.riotgames.com/lol/match/v5/matches/by-puuid/" . $puuid . "/ids?&type=" . $gametype . "&start=".$start."&count=" . $matchcount . "&api_key=" . $api_key);

        
//             //return the transfer as a string
//             curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
//             // $output contains the output string
//             $matchid_output = curl_exec($ch);
        
//             $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
//             // close curl resource to free up system resources
//             curl_close($ch);
//         }
        
//         //daten holen für die alle bisher geholten matches
//         match_grabber($matchid_output,$api_key,$username);

//         // echo("<pre style='background-color: #1f1f1f;'>");
//         // echo("<center>[Matchhistory]</center>");
        
//         // foreach (json_decode($matchid_output) as $match) {
//         //     echo("ID" . $i . ": " . $match . "<br>");
//         //     $i++;
//         // }
//         // echo("</pre>");


        $start += 100;
        $id_count = count(json_decode($matchid_output, true));
        $i += count(json_decode($matchid_output, true));
    }


//     $i = 0;
    echo "hier ist $i";

}

function getMatchesByPUUID($puuid){

$matches_count = scandir("/var/www/html/wordpress/clashapp/data/matches/");
$count = 0;
echo "<table class='table'>";

for($i = 2; $i < count($matches_count)-1; $i++){
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
                    if($inhalt->info->participants[$in]->win == true) {
                        echo '<tr class="online">';
                    } else {
                        echo '<tr class="offline">';
                    }
                    echo "<td>Name: ".$inhalt->info->participants[$in]->summonerName . "</td>"; 
                    echo "<td>Champion: ".$inhalt->info->participants[$in]->championName . "</td>";
                    echo "<td>Position: ".$inhalt->info->participants[$in]->teamPosition . "</td>"; 
                    echo "<td>KDA: ".$inhalt->info->participants[$in]->kills . "/"; 
                    echo $inhalt->info->participants[$in]->deaths . "/"; 
                    echo $inhalt->info->participants[$in]->assists . "</td>"; 
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
            $answer = "[" . $current_time->format('H:i:s') . "] Got new matchdata from " . $username . ": " . $matchid . ".json - Status: " . $httpcode;
            echo $answer;
            $myfile = file_put_contents('/var/www/html/wordpress/clashapp/data/matches/log.txt', $answer.PHP_EOL , FILE_APPEND | LOCK_EX);
            // echo("</pre>");
            // $i++;
            
            $fp = fopen('/var/www/html/wordpress/clashapp/data/matches/' . $matchid . '.json', 'w');
            fwrite($fp, $match_output);
            fclose($fp);
        }else{
            $current_time = new DateTime("now", new DateTimeZone('Europe/Berlin'));
            $noanswer = "[" . $current_time->format('H:i:s') . "] " . $matchid . ".json existiert bereits";
            echo $noanswer;
            $myfile = file_put_contents('/var/www/html/wordpress/clashapp/data/matches/log.txt', $noanswer.PHP_EOL , FILE_APPEND | LOCK_EX);
        }
    }
}
    













// TODO add following code after finishing: 
// } else {
//     http_response_code("403");
// }
?>



