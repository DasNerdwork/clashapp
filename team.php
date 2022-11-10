<?php session_start(); 
include_once('functions.php');
include_once('update.php');
require_once 'clash-db.php';

/**
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 */

// -----------------------------------------------------------v- CHECK STAY LOGGED IN COOKIE -v----------------------------------------------------------- //

if ((isset($_COOKIE['stay-logged-in'])) && !isset($_SESSION['user'])) {
    $db = new DB();
    $sessionData = $db->get_data_via_stay_code($_COOKIE['stay-logged-in']);
    if($sessionData['status'] !== 'error') {
        $_SESSION['user'] = array('id' => $sessionData['id'], 'region' => $sessionData['region'], 'username' => $sessionData['username'], 'email' => $sessionData['email'], 'sumid' => $sessionData['sumid']);
        setcookie("stay-logged-in", $_COOKIE['stay-logged-in'], time() + (86400 * 30), "/");
    }
}
if ((isset($_COOKIE['stay-logged-in']))) {
    setcookie("stay-logged-in", $_COOKIE['stay-logged-in'], time() + (86400 * 30), "/");
}

// ----------------------------------------------------------------v- PRINT HEADER -v---------------------------------------------------------------- //

include('head.php');
setCodeHeader('Clash', true, true);
include('header.php');

// -----------------------------------------------------------v- INITIALIZE COLLECTOR ARRAYS -v----------------------------------------------------------- //

// These arrays are necessary and used for the getSuggestedBans function as parameters to retrieve the most accurate suggested ban data efficiently
// $playerNameTeamArray = array(); // collects all 5 player names TODO: currently no use for, remove or useful?
$playerSumidTeamArray = array(); // collects all 5 sumids
$playerLanesTeamArray = array(); // collects all main and secondary roles per ["sumid"]
$masteryDataTeamArray = array(); // collects mastery data per ["sumid"] to have them combined in a single array
$matchIDTeamArray = array(); // collects ALL matchid's of the whole team combined (without duplicates)

// -----------------------------------------------------------v- SANITIZE & CHECK TEAM ID -v----------------------------------------------------------- //

$teamID = filter_var($_GET["name"], FILTER_SANITIZE_URL);
if (($teamID == null || (strlen($teamID) <= 6 && !in_array($teamID, array("404", "test")))) && str_contains($_SERVER['REQUEST_URI'], "team")){ // only allow 404, test page and usual requests
    header('Location: /team/404');
    exit;
} else {
    $teamDataArray = getTeamByTeamID($teamID);
    if($teamDataArray["Status"] == "404" && $teamID != "404"){ // necessary to check against 404 to prevent loop-redirect
        header('Location: /team/404');
        exit;
    } else {

// --------------------------------------------------------v- PRE-CREATE SELECTED BAN LIVE FILE -v-------------------------------------------------------- //

        if(!file_exists('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json')){
            $fp = fopen('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json', 'c');
            $suggestedBanFileContent["SuggestedBans"]=[] ;
            $suggestedBanFileContent["Status"]= 0;
            fwrite($fp, json_encode($suggestedBanFileContent));
            fclose($fp);
        } // no need to load the select bans file content in else block as setInterval in javascript immediately loads it anyways

// --------------------------------------------------------v- PRINT TOP PART TITLE, BANS & CO. -v-------------------------------------------------------- //

        // echo "TournamentID: ".$teamDataArray["TournamentID"]; // TODO: Add current tournament to view
        echo "
        <div id='top-part' style='height: 388px;'>
            <h1 class='schatten' id='teamname' style='padding-right: 10px; margin-left: 6px; display: inline-block;'>
                <center>
                    <img class='team-logo' src='/clashapp/data/misc/clash/logos/".$teamDataArray["Icon"]."/1_64.png' width='64'>
                    <div class='team-title' id='team-title'>".strtoupper($teamDataArray["Tag"])." | ".strtoupper($teamDataArray["Name"])." (Tier ".$teamDataArray["Tier"].")</div>
                </center>
            </h1>
            <div>
                <div id='suggested-ban-title'>Suggested Bans:</div>
                <div id='suggestedBans' class='schatten'></div>
            </div>
            <div id='selectedBans' class='schatten' style='float: right; position: relative;'></div>
            <form id='banSearch' class='schatten' action='' onsubmit='return false;' method='GET' autocomplete='off'>
                <div id='top-ban-bar'>
                    <input type='text' name='champName' id='champSelector' value='' placeholder='Championname' style='margin-bottom: 5px;'>
                    <img class='lane-selector' style='filter: brightness(50%);' src='/clashapp/data/misc/lanes/UTILITY.png' width='28' onclick='highlightLaneIcon(this);' data-lane='sup'>
                    <img class='lane-selector' style='filter: brightness(50%);' src='/clashapp/data/misc/lanes/BOTTOM.png' width='28' onclick='highlightLaneIcon(this);' data-lane='adc'>
                    <img class='lane-selector' style='filter: brightness(50%);' src='/clashapp/data/misc/lanes/MIDDLE.png' width='28' onclick='highlightLaneIcon(this);' data-lane='mid'>
                    <img class='lane-selector' style='filter: brightness(50%);' src='/clashapp/data/misc/lanes/JUNGLE.png' width='28' onclick='highlightLaneIcon(this);' data-lane='jgl'>
                    <img class='lane-selector' style='filter: brightness(50%);' src='/clashapp/data/misc/lanes/TOP.png' width='28' onclick='highlightLaneIcon(this);' data-lane='top'>
                </div>
                <div id='champSelect'>";
                    showBanSelector(); echo "
                </div>
            </form>
        </div>";

// -------------------------------------------------------------v- PRINT SEPARATE PLAYER COLUMN -v------------------------------------------------------------- //

        echo "<table class='table' style='width:100%; table-layout: fixed;'><tr>";
        $tableWidth = round(100/count($teamDataArray["Players"]));
        $playerDataDirectory = new DirectoryIterator('/var/www/html/clash/clashapp/data/player/');

        foreach($teamDataArray["Players"] as $key => $player){
            echo "<td style='vertical-align: top;'><table class='table schatten'><tr><td style='width:".$tableWidth."%; text-align: center;'>";

            unset($sumid);

            foreach ($playerDataDirectory as $playerDataJSONFile) { // going through all files
                $playerDataJSONPath = $playerDataJSONFile->getFilename();   // get all filenames as variable
                if(!($playerDataJSONPath == "." || $playerDataJSONPath == "..")){
                    // echo str_replace(".json", "", $playerDataJSONPath) ." - ". $player["summonerId"];
                    if(str_replace(".json", "", $playerDataJSONPath) == $player["summonerId"]){ // if the team players sumid = filename in player json path
                        $playerDataJSON = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$playerDataJSONPath), true); // get filepath content as variable
                        if($playerDataJSON["MatchIDs"] != getMatchIDs($playerDataJSON["PlayerData"]["PUUID"], 30)) break;  // If first matchid is outdated -> call updateProfile below because $sumid is still unset from above
                        $playerData = $playerDataJSON["PlayerData"];
                        $playerName = $playerDataJSON["PlayerData"]["Name"];
                        $sumid = $playerDataJSON["PlayerData"]["SumID"];
                        $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                        $rankData = $playerDataJSON["RankData"];
                        $masteryData = $playerDataJSON["MasteryData"];
                        $matchids = $playerDataJSON["MatchIDs"];
                        break;
                    } else {
                        // TODO: Error Handling echo "No Match found :(<br>".str_replace(".json", "", $playerDataJSONPath)."<br>".$player["summonerId"]."<br>";
                    }
                }
            }
            if(!isset($sumid) && $player["summonerId"] != "") {
                updateProfile($player["summonerId"], 15, "sumid");
                foreach ($playerDataDirectory as $playerDataJSONFile) { // going through all files
                    $playerDataJSONPath = $playerDataJSONFile->getFilename();   // get all filenames as variable
                    if(!($playerDataJSONPath == "." || $playerDataJSONPath == "..")){
                        if(str_replace(".json", "", $playerDataJSONPath) == $player["summonerId"]){ // if the team players sumid = filename in player json path
                            $playerDataJSON = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$playerDataJSONPath), true); // get filepath content as variable
                            $playerData = $playerDataJSON["PlayerData"];
                            $playerName = $playerDataJSON["PlayerData"]["Name"];
                            $sumid = $playerDataJSON["PlayerData"]["SumID"];
                            $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                            $rankData = $playerDataJSON["RankData"];
                            $masteryData = $playerDataJSON["MasteryData"];
                            $matchids = $playerDataJSON["MatchIDs"];
                            break;
                        }
                    }
                }
            }
            // $playerNameTeamArray[] = $playerName; // TODO: currently no use for, remove or useful?
            $playerSumidTeamArray[] = $sumid;
            $masteryDataTeamArray[$sumid] = $masteryData;

            
            // ----------------------------------------------------------------v- PROFILE ICON BORDERS -v---------------------------------------------------------------- //

            echo "<center><div style='display: flex; justify-content: center; width: 200px; margin-bottom: 24px; position: relative;'>";
            if(file_exists('/var/www/html/clash/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.png')){
                echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.png" width="84" style="border-radius: 100%;margin-top: 25px; z-index: -1;" loading="lazy">';
            }

            $rankOrLevelArray = getRankOrLevel($rankData);
            if($rankOrLevelArray["Type"] === "Rank"){ // If user has a rank
                // Print the profile border image url for current highest rank
                $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/var/www/html/clash/clashapp/data/misc/ranks/*'.strtolower($rankOrLevelArray["HighestRank"]).'_base.ls_ch.png', GlobIterator::CURRENT_AS_PATHNAME)))[0];
                $webBorderPath = str_replace("/var/www/html/clash","",$profileBorderPath);
                if(file_exists($profileBorderPath)){
                    echo '<img src="'.$webBorderPath.'" width="384" style="position: absolute; top: -126px; z-index: -1;" loading="lazy">';
                }
                // Additionally print LP count if user is Master+ OR print the rank number (e.g. IV)
                if ($rankOrLevelArray["HighEloLP"] != ""){
                    echo "<div style='font-weight: bold; color: #e8dfcc; position: absolute; margin-top: -5px; font-size: 12px;'>".$rankOrLevelArray["HighEloLP"]." LP</div>";
                } else {
                    echo "<div style='font-weight: bold; color: #e8dfcc; position: absolute; margin-top: 17px; font-size: 12px;'>".$rankOrLevelArray["RankNumber"]."</div>";
                }
                
                echo "<div style='color: #e8dfcc; position: absolute; margin-top: 111px; font-size: 12px;'>".$playerData["Level"]."</div>"; // Always current lvl at the bottom
            } else if($rankOrLevelArray["Type"] === "Level") { // Else set to current level border
                $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/var/www/html/clash/clashapp/data/misc/levels/prestige_crest_lvl_'.$rankOrLevelArray["LevelFileName"].'.png', GlobIterator::CURRENT_AS_PATHNAME)))[0];
                $webBorderPath = str_replace("/var/www/html/clash","",$profileBorderPath);
                if(file_exists($profileBorderPath)){
                    echo '<img src="'.$webBorderPath.'" width="190" style="position: absolute;  top: -37px; z-index: -1;" loading="lazy">';
                    }
                echo "<div style='color: #e8dfcc; position: absolute; margin-top: 105px; font-size: 12px;'>".$playerData["Level"]."</div>";
            }
            echo "</div></center>";
            echo "<div class='player-name'>".$playerName."</div>"; // separate player name below icon + border

            // ----------------------------------------------------------------v- POSITIONS -v---------------------------------------------------------------- //

            $matchids_sliced = array_slice($matchids, 0, 15); // Select first 30 MatchIDs of current player
            // $startMatchData = microtime(true);
            // $startMemory = memory_get_usage();
            $matchDaten = getMatchData($matchids_sliced);
            // foreach($matchDaten as $singleMatch){
            //     echo "<pre>";
            //     print_r($singleMatch);
            //     echo "</pre>";
            // }
            // echo memory_get_usage() - $startMemory;
            // echo " und ".number_format(microtime(true) - $startMatchData, 4);
            // $t = 0;
            // echo "test1<br><pre>";
            // print_r($matchids_sliced);
            // echo "</pre><br>".$sumid."<br>";
            // foreach($matchDaten as $singleMatch){
            //     echo "Got Ranking for Match Number: ".$t;
            //     echo "<pre>";
            //     print_r($singleMatch);
            //     echo "<pre>";
            //     $t++;
            // }
            $matchRankingArray = getMatchRanking($matchids_sliced, $matchDaten, $sumid);
            // echo "test2<br>";
            $playerLanes = getLanePercentages($matchDaten, $puuid);
            $playerMainRole = $playerLanes[0];

            $playerSecondaryRole = $playerLanes[1];
            $playerLanesTeamArray[$sumid]["Mainrole"] = $playerLanes[0];
            $playerLanesTeamArray[$sumid]["Secrole"] = $playerLanes[1];

            $queueRole = $player["position"];
            echo "<div class='position-disclaimer'>Positions: ";
            if(file_exists('/var/www/html/clash/clashapp/data/misc/lanes/'.$playerMainRole.'.png')){
                echo '<img src="/clashapp/data/misc/lanes/'.$playerMainRole.'.png" width="32" loading="lazy">';
            }
            if(file_exists('/var/www/html/clash/clashapp/data/misc/lanes/'.$playerSecondaryRole.'.png')){
                echo '<img src="/clashapp/data/misc/lanes/'.$playerSecondaryRole.'.png" width="32" loading="lazy">';
            }
            echo " queued as ";
            if(file_exists('/var/www/html/clash/clashapp/data/misc/lanes/'.$queueRole.'.png')){
                echo '<img src="/clashapp/data/misc/lanes/'.$queueRole.'.png" width="32" loading="lazy"></div><br>';
            }

            foreach($matchids_sliced as $matchid){
                if(!file_exists('/var/www/html/clash/clashapp/data/matches/' . $matchid . ".json")){
                    downloadMatchByID($matchid, $playerName);
                }
            }

            echo "</td></tr><tr><td style='text-align: center; vertical-align: middle; height: 7em;'>";
            echo "<div style='display: inline-flex;'>";
            if(!empty($rankData)){
                $key = array_search('RANKED_SOLO_5x5', array_column($rankData,"Queue"));
                if($key !== false){
                    echo "<div class='schatten' style='margin: 10px 20px; padding: 5px;'><font size='-1'>Ranked Solo/Duo:</font><br>";
                    switch ($rankData[$key]["Tier"]){
                        case ($rankData[$key]["Tier"] == "CHALLENGER"):
                            echo "<span style='color: #52cfff'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "GRANDMASTER"):
                            echo "<span style='color: #cd423a'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "MASTER"):
                            echo "<span style='color: #b160f3'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "DIAMOND"):
                            echo "<span style='color: #617ecb'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "PLATINUM"):
                            echo "<span style='color: #23af88'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "GOLD"):
                            echo "<span style='color: #d79c5d'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "SILVER"):
                            echo "<span style='color: #99a0b5'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "BRONZE"):
                            echo "<span style='color: #cd8d7f'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "IRON"):
                            echo "<span style='color: #392b28'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                    }
                    echo " / " . $rankData[$key]["LP"] . " LP<br>WR: " . round((($rankData[$key]["Wins"]/($rankData[$key]["Wins"]+$rankData[$key]["Losses"]))*100),2) . "%<br><font size='-1'>(".$rankData[$key]["Wins"]+$rankData[$key]["Losses"]." Games)</font></div>";
                }
                $key = array_search('RANKED_FLEX_SR', array_column($rankData,"Queue"));
                if($key !== false){
                    echo "<div class='schatten' style='margin: 10px 20px; padding: 5px;'><font size='-1'>Ranked Flex:</font><br>";
                    switch ($rankData[$key]["Tier"]){
                        case ($rankData[$key]["Tier"] == "CHALLENGER"):
                            echo "<span style='color: #52cfff'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "GRANDMASTER"):
                            echo "<span style='color: #cd423a'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "MASTER"):
                            echo "<span style='color: #b160f3'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "DIAMOND"):
                            echo "<span style='color: #617ecb'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "PLATINUM"):
                            echo "<span style='color: #23af88'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "GOLD"):
                            echo "<span style='color: #d79c5d'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "SILVER"):
                            echo "<span style='color: #99a0b5'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "BRONZE"):
                            echo "<span style='color: #cd8d7f'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                        case ($rankData[$key]["Tier"] == "IRON"):
                            echo "<span style='color: #392b28'>".ucfirst(strtolower($rankData[$key]["Tier"])). " " . $rankData[$key]["Rank"]."</span>";
                            break;
                    }
                    echo " / " . $rankData[$key]["LP"] . " LP<br>WR: " . round((($rankData[$key]["Wins"]/($rankData[$key]["Wins"]+$rankData[$key]["Losses"]))*100),2) . "%<br><font size='-1'>(".$rankData[$key]["Wins"]+$rankData[$key]["Losses"]." Games)</font></div>";
                }
                if(empty(array_intersect(array("RANKED_SOLO_5x5", "RANKED_FLEX_SR"), array_column($rankData,"Queue")))){
                    echo "<div class='schatten' style='margin: 10px 20px; padding: 5px;'>Unranked</div>";
                }
            } else if(empty($rankData)){
                echo "<div class='schatten' style='margin: 10px 20px; padding: 5px;'>Unranked</div>";
            }
            echo "</div>";

            echo "</td></tr><tr><td style='text-align: center; vertical-align: top; height: 8em;'>";

            echo "<div style='display: inline-flex;'>";
            for($i=0; $i<3; $i++){
                if(file_exists('/var/www/html/clash/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryData[$i]["Filename"].'.png')){
                    echo '<div><img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryData[$i]["Filename"].'.png" width="64" style="margin: 0px 28px;" loading="lazy"><br>';
                    echo $masteryData[$i]["Champion"]."<br>";
                    echo "MR: ".$masteryData[$i]["Lvl"]."<br>";
                    $masteryPoints = str_replace(',','',$masteryData[$i]["Points"]);
                    if ($masteryPoints < 100000){
                        echo "<div class='mastery-points'>".$masteryData[$i]["Points"]."</div><br></div>";
                    } else if ($masteryPoints >= 100000 && $masteryPoints < 200000){
                        echo "<div class='mastery-points' style='color: #ffe485;'>".$masteryData[$i]["Points"]."</div><br></div>";
                    } else if ($masteryPoints >= 200000 && $masteryPoints < 300000){
                        echo "<div class='mastery-points' style='color: #D3A609;'>".$masteryData[$i]["Points"]."</div><br></div>";
                    } else if ($masteryPoints >= 300000 && $masteryPoints < 500000){
                        echo "<div class='mastery-points' style='color: #CB820C;'>".$masteryData[$i]["Points"]."</div><br></div>";
                    } else if ($masteryPoints >= 500000 && $masteryPoints < 700000){
                        echo "<div class='mastery-points' style='color: #C35D0F;'>".$masteryData[$i]["Points"]."</div><br></div>";
                    } else if ($masteryPoints >= 700000 && $masteryPoints < 1000000){
                        echo "<div class='mastery-points' style='color: #E12F08;'>".$masteryData[$i]["Points"]."</div><br></div>";
                    } else if ($masteryPoints >= 1000000){
                        echo "<div class='mastery-points' style='color: #FF0000;'>".$masteryData[$i]["Points"]."</div><br></div>";
                    }
                }
            }
            echo "</div>";
            echo "</td></tr><tr><td style='vertical-align: top; text-align: center;'>";
            echo "Average Matchscore: ".number_format((array_sum($matchRankingArray)/count($matchRankingArray)), 2); // Calculates and prints the average Matchscore of a player
            // echo "<div style='margin: 10px 0px;'>";
            // foreach (mostPlayedWith($matchDaten, $puuid) as $key => $value){
            //     foreach ($teamDataArray["Players"] as $teamMember){
            //         if(file_exists('/var/www/html/clash/clashapp/data/player/'.$teamMember["summonerId"].'.json')){
            //             $uniquePlayerName = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$teamMember["summonerId"].'.json'), true)["PlayerData"]["Name"];
            //         }
            //         if ($key == $uniquePlayerName){
            //             echo "Prematemöglichkeit: ".$key." auf ".$value." Games<br>";
            //             break;
            //         }
            //     }
            // }
            // echo "</div>";
            printTeamMatchDetailsByPUUID($matchids_sliced, $puuid, $matchRankingArray);
            // echo "<pre>";
            // print_r(getSuggestedBans($sumid, $matchDaten));
            // echo "</pre>";
            echo "</td></tr></table></td>";
            foreach($matchids as $matchid){
                if(!in_array($matchid, $matchIDTeamArray)){
                    $matchIDTeamArray[] = $matchid;
                }
            }
            // break; // Uncomment if only 1 player
    }
    $suggestedBanMatchData = getMatchData($matchIDTeamArray);

    echo "</tr></table>";
    //    echo "<pre>";
    //    print_r($playerSumidTeamArray);
    //    echo "</pre>";
    //    echo "<pre>";
    //    print_r($matchIDTeamArray);
    //    echo "</pre>";
    //    echo "<pre>";
    //    print_r(getSuggestedPicksAndTeamstats($playerSumidTeamArray, $matchIDTeamArray, $suggestedBanMatchData));
    //    echo "</pre>";
    $suggestedBanArray = getSuggestedBans($playerSumidTeamArray, $masteryDataTeamArray, $playerLanesTeamArray, $matchIDTeamArray, $suggestedBanMatchData);
    //    echo "<pre>";
    //    print_r($suggestedBanArray);
    //    echo "</pre>";
    foreach($suggestedBanArray as $banChampion){
            echo '<div class="suggested-ban-champion">';
                echo '<div class="ban-hoverer" onclick="">';
                    echo '<img class="suggested-ban-icon" style="height: auto; z-index: 1;" data-id="' . $banChampion["Filename"] . '" src="/clashapp/data/patch/' . $currentPatch . '/img/champion/' . str_replace(' ', '', $banChampion["Filename"]) . '.png" width="48" loading="lazy">';
                    echo '<img class="ban-overlay" src="/clashapp/data/misc/icon-ban.png" width="48" loading="lazy">';
                    echo '<img class="ban-overlay-red" draggable="false" src="/clashapp/data/misc/icon-ban-red.png" width="48" loading="lazy"></div>';
                echo '<span class="suggested-ban-caption" style="display: block;">' . $banChampion["Champion"] . '</span>';
            echo '</div>';
        }
    }
}
echo '</body>';

include('footer.php');
?>