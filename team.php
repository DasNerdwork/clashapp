<?php session_start(); 
$startInitialTime = microtime(true);
$memInitialTime = memory_get_usage();
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

// ----------------------------------------------------------------v- INITIALIZER -v---------------------------------------------------------------- //

// These arrays are necessary and used for the getSuggestedBans function as parameters to retrieve the most accurate suggested ban data efficiently
// $playerNameTeamArray = array(); // collects all 5 player names TODO: currently no use for, remove or useful?
$playerSumidTeamArray = array(); // collects all 5 sumids
$playerLanesTeamArray = array(); // collects all main and secondary roles per ["sumid"]
$masteryDataTeamArray = array(); // collects mastery data per ["sumid"] to have them combined in a single array
$matchIDTeamArray = array(); // collects ALL matchid's of the whole team combined (without duplicates)
$timeAndMemoryArray = array(); // saves the speed of every function and its  memory requirements
$timeAndMemoryArray["InitializingAndHeader"]["Time"] = number_format((microtime(true) - $startInitialTime), 2, ',', '.')." s";
$timeAndMemoryArray["InitializingAndHeader"]["Memory"] = number_format((memory_get_usage() - $memInitialTime)/1024, 2, ',', '.')." kB";
$execOnlyOnce = false;

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

        $startCheckBanFile = microtime(true);
        $memCheckBanFile = memory_get_usage();
        if(!file_exists('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json')){
            $fp = fopen('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json', 'c');
            $suggestedBanFileContent["SuggestedBans"]=[] ;
            $suggestedBanFileContent["Status"]= 0;
            fwrite($fp, json_encode($suggestedBanFileContent));
            fclose($fp);
        } // no need to load the select bans file content in else block as setInterval in javascript immediately loads it anyways
        $timeAndMemoryArray["CheckBanFile"]["Time"] = number_format((microtime(true) - $startCheckBanFile), 2, ',', '.')." s";
        $timeAndMemoryArray["CheckBanFile"]["Memory"] = number_format((memory_get_usage() - $memCheckBanFile)/1024, 2, ',', '.')." kB";


// --------------------------------------------------------v- PRINT TOP PART TITLE, BANS & CO. -v-------------------------------------------------------- //

        $startPageTop = microtime(true);
        $memPageTop = memory_get_usage();
        // echo "TournamentID: ".$teamDataArray["TournamentID"]; // TODO: Add current tournament to view
        echo "
        <div id='top-part' style='height: 388px;'>
            <h1 class='schatten' id='teamname' style='padding-right: 10px; margin-left: 6px; display: inline-block;'>
                <center>
                    <img class='team-logo' src='/clashapp/data/misc/clash/logos/".$teamDataArray["Icon"]."/1_64.webp' width='64'>
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
                    <img class='lane-selector' style='filter: saturate(0) brightness(50%);' src='/clashapp/data/misc/lanes/UTILITY.webp' width='28' onclick='highlightLaneIcon(this);' data-lane='sup'>
                    <img class='lane-selector' style='filter: saturate(0) brightness(50%);' src='/clashapp/data/misc/lanes/BOTTOM.webp' width='28' onclick='highlightLaneIcon(this);' data-lane='adc'>
                    <img class='lane-selector' style='filter: saturate(0) brightness(50%);' src='/clashapp/data/misc/lanes/MIDDLE.webp' width='28' onclick='highlightLaneIcon(this);' data-lane='mid'>
                    <img class='lane-selector' style='filter: saturate(0) brightness(50%);' src='/clashapp/data/misc/lanes/JUNGLE.webp' width='28' onclick='highlightLaneIcon(this);' data-lane='jgl'>
                    <img class='lane-selector' style='filter: saturate(0) brightness(50%);' src='/clashapp/data/misc/lanes/TOP.webp' width='28' onclick='highlightLaneIcon(this);' data-lane='top'>
                </div>
                <div id='champSelect'>";
                    showBanSelector(); echo "
                </div>
            </form>
        </div>";
        $timeAndMemoryArray["PageTop"]["Time"] = number_format((microtime(true) - $startPageTop), 2, ',', '.')." s";
        $timeAndMemoryArray["PageTop"]["Memory"] = number_format((memory_get_usage() - $memPageTop)/1024, 2, ',', '.')." kB";

// -------------------------------------------------------------v- PRINT SEPARATE PLAYER COLUMN -v------------------------------------------------------------- //

        $startFetchPlayerTotal = microtime(true);
        $memFetchPlayerTotal = memory_get_usage();
        echo "
        <table class='table' style='width:100%; table-layout: fixed;'>
            <tr>";
                $tableWidth = round(100/count($teamDataArray["Players"]));
                $playerDataDirectory = new DirectoryIterator('/var/www/html/clash/clashapp/data/player/');
                foreach($teamDataArray["Players"] as $key => $player){ 
                    $startFetchPlayer[$key] = microtime(true);
                    $memFetchPlayer[$key] = memory_get_usage();
                    echo "
                    <td style='vertical-align: top;'>
                        <table class='table schatten'>
                            <tr>
                                <td style='width:".$tableWidth."%; text-align: center;'>";
                                    unset($sumid); // necessary for check 22 lines below
                                    foreach ($playerDataDirectory as $playerDataJSONFile) { // going through all files
                                        $playerDataJSONPath = $playerDataJSONFile->getFilename();   // get all filenames as variable
                                        if(!($playerDataJSONPath == "." || $playerDataJSONPath == "..")){
                                            // echo str_replace(".json", "", $playerDataJSONPath) ." - ". $player["summonerId"];
                                            if(str_replace(".json", "", $playerDataJSONPath) == $player["summonerId"]){ // if the team players sumid = filename in player json path
                                                $playerDataJSON = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$playerDataJSONPath), true); // get filepath content as variable
                                                $tempMatchIDs = getMatchIDs($playerDataJSON["PlayerData"]["PUUID"], 15);
                                                if($playerDataJSON["MatchIDs"][0] != $tempMatchIDs[0]){ // If first matchid is outdated -> call updateProfile below because $sumid is still unset from above
                                                    echo "<script>console.log('INFO: ".$playerDataJSON["PlayerData"]["Name"]." was out-of-date -> Updated.');</script>";
                                                    break;
                                                } else {
                                                    $playerData = $playerDataJSON["PlayerData"];
                                                    $playerName = $playerDataJSON["PlayerData"]["Name"];
                                                    $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                                    $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                                    $rankData = $playerDataJSON["RankData"];
                                                    $masteryData = $playerDataJSON["MasteryData"];
                                                    $matchids = $playerDataJSON["MatchIDs"];
                                                    break;
                                                }  
                                            } else {
                                                // TODO: Error Handling echo "No Match found :(<br>".str_replace(".json", "", $playerDataJSONPath)."<br>".$player["summonerId"]."<br>";
                                            }
                                        }
                                    }
                                    if(!isset($sumid) && $player["summonerId"] != "") {
                                        updateProfile($player["summonerId"], 15, "sumid", $tempMatchIDs);
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
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["FetchPlayerData"]["Time"] = number_format((microtime(true) - $startFetchPlayer[$key]), 2, ',', '.')." s";
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["FetchPlayerData"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayer[$key])/1024, 2, ',', '.')." kB";
                                
                                    // ----------------------------------------------------------------v- PROFILE ICON BORDERS -v---------------------------------------------------------------- //

                                    if(!$execOnlyOnce) $startProfileIconBorders = microtime(true);
                                    $memProfileIconBorders = memory_get_usage();
                                    echo "
                                    <center>
                                        <div style='display: flex; justify-content: center; width: 200px; margin-bottom: 24px; position: relative;'>";
                                            if(file_exists('/var/www/html/clash/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.webp')){
                                                echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.webp" width="84" style="border-radius: 100%;margin-top: 25px; z-index: -1;" loading="lazy">';
                                            }

                                            $rankOrLevelArray = getRankOrLevel($rankData);
                                            if($rankOrLevelArray["Type"] === "Rank"){ // If user has a rank
                                                // Print the profile border image url for current highest rank
                                                $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/var/www/html/clash/clashapp/data/misc/ranks/*'.strtolower($rankOrLevelArray["HighestRank"]).'_base.ls_ch.webp', GlobIterator::CURRENT_AS_PATHNAME)))[0];
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
                                                $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/var/www/html/clash/clashapp/data/misc/levels/prestige_crest_lvl_'.$rankOrLevelArray["LevelFileName"].'.webp', GlobIterator::CURRENT_AS_PATHNAME)))[0];
                                                $webBorderPath = str_replace("/var/www/html/clash","",$profileBorderPath);
                                                if(file_exists($profileBorderPath)){
                                                    echo '<img src="'.$webBorderPath.'" width="190" style="position: absolute;  top: -37px; z-index: -1;" loading="lazy">';
                                                    }
                                                echo "<div style='color: #e8dfcc; position: absolute; margin-top: 105px; font-size: 12px;'>".$playerData["Level"]."</div>";
                                            } echo "
                                        </div>
                                    </center>";// separate player name below icon + border
                                    echo "<div class='player-name'>".$playerName."</div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Time"] = number_format((microtime(true) - $startProfileIconBorders), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Memory"] = number_format((memory_get_usage() - $memProfileIconBorders)/1024, 2, ',', '.')." kB";

                            // ----------------------------------------------------------------v- GET REUSABLE MATCH DATA -v---------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startGetMatchData = microtime(true);
                            $memGetMatchData = memory_get_usage();
                            $matchids_sliced = array_slice($matchids, 0, 15); // Select first 15 MatchIDs of current player
                            $matchDaten = getMatchData($matchids_sliced); // Get the opened & combined data of all of them
                            // if(!$execOnlyOnce) echo "<pre>"; print_r($matchDaten); echo "</pre>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMatchData"]["Time"] = number_format((microtime(true) - $startGetMatchData), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMatchData"]["Memory"] = number_format((memory_get_usage() - $memGetMatchData)/1024, 2, ',', '.')." kB";
                            if(!$execOnlyOnce) $startGetMatchRanking = microtime(true);
                            $memGetMatchRanking = memory_get_usage();
                            $matchRankingArray = getMatchRanking($matchids_sliced, $matchDaten, $sumid); // Fetches ALL match scores to use in section "PRINT AVERAGE MATCHSCORE"
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMatchRanking"]["Time"] = number_format((microtime(true) - $startGetMatchRanking), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMatchRanking"]["Memory"] = number_format((memory_get_usage() - $memGetMatchRanking)/1024, 2, ',', '.')." kB";
                            if(!$execOnlyOnce) $startGetLanePercentages = microtime(true);
                            $memGetLanePercentages = memory_get_usage();
                            $playerLanes = getLanePercentages($matchDaten, $puuid); // Retrieves the two most played lanes of the give puuid
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetLanePercentages"]["Time"] = number_format((microtime(true) - $startGetLanePercentages), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetLanePercentages"]["Memory"] = number_format((memory_get_usage() - $memGetLanePercentages)/1024, 2, ',', '.')." kB";
                            if(!$execOnlyOnce) $startGetMostPlayedWith = microtime(true);
                            $memGetMostPlayedWith = memory_get_usage();
                            $mostPlayedWithArray = mostPlayedWith($matchDaten, $puuid); // As the name says <--
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMostPlayedWith"]["Time"] = number_format((microtime(true) - $startGetMostPlayedWith), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMostPlayedWith"]["Memory"] = number_format((memory_get_usage() - $memGetMostPlayedWith)/1024, 2, ',', '.')." kB";
                            unset($matchDaten); // cleanup array for next player & faster loadtime

                            // ------------------------------------------------------------------v- PRINT PLAYER LANES -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startProfileIconBorders = microtime(true);
                            $memProfileIconBorders = memory_get_usage();
                            $playerMainRole = $playerLanes[0];
                            $playerSecondaryRole = $playerLanes[1];
                            $queueRole = $player["position"];
                            // Also add main & secondary role to collective team array
                            $playerLanesTeamArray[$sumid]["Mainrole"] = $playerLanes[0];
                            $playerLanesTeamArray[$sumid]["Secrole"] = $playerLanes[1];

                            echo "
                            <tr>
                                <td style='text-align: center;'>
                                    <div class='position-disclaimer'>Positions: ";
                                        if(file_exists('/var/www/html/clash/clashapp/data/misc/lanes/'.$playerMainRole.'.webp')){
                                            echo '<img class="lane-icon" src="/clashapp/data/misc/lanes/'.$playerMainRole.'.webp" width="32" loading="lazy">';
                                        }
                                        if(file_exists('/var/www/html/clash/clashapp/data/misc/lanes/'.$playerSecondaryRole.'.webp')){
                                            echo '<img class="lane-icon" src="/clashapp/data/misc/lanes/'.$playerSecondaryRole.'.webp" width="32" loading="lazy">';
                                        }
                                        echo " queued as ";
                                        if(file_exists('/var/www/html/clash/clashapp/data/misc/lanes/'.$queueRole.'.webp')){
                                            echo '<img class="lane-icon" src="/clashapp/data/misc/lanes/'.$queueRole.'.webp" width="32" loading="lazy">';
                                        } echo"
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Time"] = number_format((microtime(true) - $startProfileIconBorders), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Memory"] = number_format((memory_get_usage() - $memProfileIconBorders)/1024, 2, ',', '.')." kB";

                            // ------------------------------------------------------------------v- TODO: AGAIN DOWNLOAD? -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startAgainDownload = microtime(true);
                            $memAgainDownload = memory_get_usage();
                            foreach($matchids_sliced as $matchid){
                                if(!file_exists('/var/www/html/clash/clashapp/data/matches/' . $matchid . ".json")){
                                    downloadMatchByID($matchid, $playerName);
                                }
                            }
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["StartAgainDownload"]["Time"] = number_format((microtime(true) - $startAgainDownload), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["StartAgainDownload"]["Memory"] = number_format((memory_get_usage() - $memAgainDownload)/1024, 2, ',', '.')." kB";

                            // ------------------------------------------------------------------v- PRINT RANKED STATS -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startPrintRankedStats = microtime(true);
                            $memPrintRankedStats = memory_get_usage();
                            echo "
                            <tr>
                                <td style='text-align: center; vertical-align: middle; height: 7em;'>
                                    <div style='display: inline-flex;'>";
                                    if(empty($rankData) || empty(array_intersect(array("RANKED_SOLO_5x5", "RANKED_FLEX_SR"), array_column($rankData,"Queue")))){
                                        echo "<div class='schatten' style='margin: 10px 20px; padding: 5px;'>Unranked</div>";
                                    } else {
                                        foreach($rankData as $rankQueue){
                                            if($rankQueue["Queue"] == "RANKED_SOLO_5x5"){ echo "
                                                <div class='schatten' style='margin: 10px 20px; padding: 5px;'>
                                                    <font size='-1'>Ranked Solo/Duo:</font><br>
                                                    <span style='color: #".getRankColor($rankQueue["Tier"])."'>".ucfirst(strtolower($rankQueue["Tier"])). " " . $rankQueue["Rank"]."</span>";
                                            } else if($rankQueue["Queue"] == "RANKED_FLEX_SR"){ echo "
                                                <div class='schatten' style='margin: 10px 20px; padding: 5px;'>
                                                    <font size='-1'>Ranked Flex:</font><br>
                                                    <span style='color: #".getRankColor($rankQueue["Tier"])."'>".ucfirst(strtolower($rankQueue["Tier"])). " " . $rankQueue["Rank"]."</span>";
                                            } echo " / " . $rankQueue["LP"] . " LP<br>
                                                    WR: " . round((($rankQueue["Wins"]/($rankQueue["Wins"]+$rankQueue["Losses"]))*100),2) . "%<br>
                                                    <font size='-1'>(".$rankQueue["Wins"]+$rankQueue["Losses"]." Games)</font>
                                                </div>";
                                        }
                                    } echo "
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintRankedStats"]["Time"] = number_format((microtime(true) - $startPrintRankedStats), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintRankedStats"]["Memory"] = number_format((memory_get_usage() - $memPrintRankedStats)/1024, 2, ',', '.')." kB";
                            
                            // ----------------------------------------------------------------v- PRINT MASTERY DATA -v---------------------------------------------------------------- //
                            
                            if(!$execOnlyOnce) $startPrintMasteryData = microtime(true);
                            $memPrintMasteryData = memory_get_usage();
                            echo "
                            <tr>
                                <td style='text-align: center; vertical-align: top; height: 7em;'>
                                    <div style='display: inline-flex;'>";
                                        for($i=0; $i<3; $i++){
                                            if(file_exists('/var/www/html/clash/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryData[$i]["Filename"].'.webp')){ echo '
                                                <div>
                                                    <img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryData[$i]["Filename"].'.webp" width="64" style="margin: 0px 28px; z-index: 1;" loading="lazy"><br>'.
                                                    $masteryData[$i]["Champion"].'<br>
                                                    <img src="/clashapp/data/misc/mastery-'.$masteryData[$i]["Lvl"].'.webp" width="32" style="position: relative; bottom: 90px; left: 30px; z-index: 2;">'.
                                                    "<div class='mastery-points' style='color: #".getMasteryColor(str_replace(',','',$masteryData[$i]["Points"])).";'>".explode(",",$masteryData[$i]["Points"])[0]."k</div>
                                                </div>";
                                            }
                                        } echo "
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMasteryData"]["Time"] = number_format((microtime(true) - $startPrintMasteryData), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMasteryData"]["Memory"] = number_format((memory_get_usage() - $memPrintMasteryData)/1024, 2, ',', '.')." kB";
                            
                            // -------------------------------------------------------------v- PRINT AVERAGE MATCHSCORE -v------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startPrintAverageMatchscore = microtime(true);
                            $memPrintAverageMatchscore = memory_get_usage();
                            echo "
                            <tr>
                                <td style='vertical-align: top; text-align: center;'>
                                    <div>Average Matchscore: ".number_format((array_sum($matchRankingArray)/count($matchRankingArray)), 2)."</div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintAverageMatchscore"]["Time"] = number_format((microtime(true) - $startPrintAverageMatchscore), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintAverageMatchscore"]["Memory"] = number_format((memory_get_usage() - $memPrintAverageMatchscore)/1024, 2, ',', '.')." kB";

                            // -------------------------------------------------------------------v- PRINT TAGS  -v------------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startPrintTags = microtime(true);
                            $memPrintTags = memory_get_usage();

                            // TODO: Fetch & Print tags + include premades function

                            echo "
                            <tr>
                                <td style='vertical-align: top; text-align: center;'>
                                    <div class='tag-container'>
                                        <ul class='player-tags'>
                                            <li class='tag'>MVP</li>
                                            <li class='tag'>Dragonkiller</li>
                                            <li class='tag'>Newly</li>
                                            <li class='tag'>Invader</li>
                                            <li class='tag'>Lowbob</li>
                                            <li class='tag'>Captain</li>
                                            <li class='tag'>Premate</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                            <li class='tag'>Test</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>";

                            // echo "<pre>";
                            //     print_r($mostPlayedWithArray);
                            // echo "</pre>";

                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintTags"]["Time"] = number_format((microtime(true) - $startPrintTags), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintTags"]["Memory"] = number_format((memory_get_usage() - $memPrintTags)/1024, 2, ',', '.')." kB";

                            // ----------------------------------------------------------------v- PRINT MATCH HISTORY  -v---------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startPrintMatchHistory = microtime(true);
                            $memPrintMatchHistory = memory_get_usage();
                            echo "
                            <tr>
                                <td style='vertical-align: top; text-align: center;'>";
                                    printTeamMatchDetailsByPUUID($matchids_sliced, $puuid, $matchRankingArray);
                                    echo "
                                </td>
                            </tr>
                        </table>
                    </td>";
                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMatchHistory"]["Time"] = number_format((microtime(true) - $startPrintMatchHistory), 2, ',', '.')." s";
                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMatchHistory"]["Memory"] = number_format((memory_get_usage() - $memPrintMatchHistory)/1024, 2, ',', '.')." kB";
                    foreach($matchids as $matchid){
                        if(!in_array($matchid, $matchIDTeamArray)){
                            $matchIDTeamArray[] = $matchid;
                        }
                    }
                    $execOnlyOnce = false;
                    $timeAndMemoryArray["Player"][$playerName]["TotalPlayer"]["Time"] = number_format((microtime(true) - $startFetchPlayer[$key]), 2, ',', '.')." s";
                    $timeAndMemoryArray["Player"][$playerName]["TotalPlayer"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayer[$key])/1024, 2, ',', '.')." kB";
                    // break; // Uncomment if we want only 1 player to render
                }
                $startGetSuggestedBans = microtime(true);
                $memGetSuggestedBans = memory_get_usage();
                echo "
            </tr>
        </table>";
        $timeAndMemoryArray["FetchPlayerTotal"]["Time"] = number_format((microtime(true) - $startFetchPlayerTotal), 2, ',', '.')." s";
        $timeAndMemoryArray["FetchPlayerTotal"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayerTotal)/1024, 2, ',', '.')." kB";

    // -------------------------------------------------------------------------------v- CALCULATE & PRINT SUGGESTED BAN DATA  -v------------------------------------------------------------------------------- //

    $suggestedBanMatchData = getMatchData($matchIDTeamArray);
    $suggestedBanArray = getSuggestedBans($playerSumidTeamArray, $masteryDataTeamArray, $playerLanesTeamArray, $matchIDTeamArray, $suggestedBanMatchData);
    foreach($suggestedBanArray as $banChampion){
            echo '<div class="suggested-ban-champion">';
                echo '<div class="ban-hoverer" onclick="">';
                    echo '<img class="suggested-ban-icon" style="height: auto; z-index: 1;" data-id="' . $banChampion["Filename"] . '" src="/clashapp/data/patch/' . $currentPatch . '/img/champion/' . str_replace(' ', '', $banChampion["Filename"]) . '.webp" width="48" loading="lazy">';
                    echo '<img class="ban-overlay" src="/clashapp/data/misc/icon-ban.webp" width="48" loading="lazy">';
                    echo '<img class="ban-overlay-red" draggable="false" src="/clashapp/data/misc/icon-ban-red.webp" width="48" loading="lazy"></div>';
                echo '<span class="suggested-ban-caption" style="display: block;">' . $banChampion["Champion"] . '</span>';
            echo '</div>';
        }
    }
    
    // ----------------------------------------------------------------------------------------------v- END + FOOTER  -v---------------------------------------------------------------------------------------------- //

}
include('footer.php');
$timeAndMemoryArray["Total"]["Time"] = number_format((microtime(true) - $startInitialTime), 2, ',', '.')." s";
$timeAndMemoryArray["Total"]["Memory"] = number_format((memory_get_usage() - $memInitialTime)/1024, 2, ',', '.')." kB";

    // --------------------------------------------------------------------------------------------------v- DEBUG  -v-------------------------------------------------------------------------------------------------- //

echo "
<script>console.log('INFO: Time and Memory Array generated:'); console.log(".json_encode($timeAndMemoryArray).");</script>
<script>console.log('INFO: PlayerData calls: '+playerDataCalls);</script>
<script>console.log('INFO: MasteryScore calls: '+masteryScoresCalls);</script>
<script>console.log('INFO: CurrentRank calls: '+currentRankCalls);</script>
<script>console.log('INFO: MatchIDs calls: '+matchIdCalls);</script>
<script>console.log('INFO: MatchDownload calls: '+matchDownloadCalls);</script>
<script>let full = playerDataCalls+masteryScoresCalls+currentRankCalls+matchIdCalls+matchDownloadCalls; console.log('INFO: Total calls: '+full);</script>";

?>