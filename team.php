<?php session_start(); 
include_once('functions.php');
include_once('updateTeam.php');
include_once('update.php');
require_once 'clash-db.php';
 
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

include('head.php');
setCodeHeader('Clash', true, true);
include('header.php');
/**
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 */
/**
 * 1. API request to get SumID by username
 * 2. API request to get player.by-summoner (to get the "teamId"
 * 3. API request to get the other SumIDs via team.by-teami
 * 4. Call update on all users and get their contents
 *      5x for general user Info
 *      5x for Mastery
 *      5x for rankData
 *      5x for matchIDlist
 *      => 100-23= 77
 *      => 77/5 = 15,4 -> 15
 * 5. Download data for 15 newest matches via updateProfile($_POST["username"], 15);
 */


$output = json_decode(file_get_contents('/hdd1/clashapp/misc/player.by-summoner.json'), true);
$playerNameTeamArray = array();
$playerSumidTeamArray = array();

if (isset($_GET["name"]) && $_GET["name"] != "404"){
    $teamID = $_GET["name"];

    if(!(file_exists('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json'))){
        $fp = fopen('/var/www/html/clash/clashapp/data/teams/'.$teamID.'.json', 'c');
        $suggestedBanFileContent["SuggestedBans"]=[] ;
        $suggestedBanFileContent["Status"]= 0;
        fwrite($fp, json_encode($suggestedBanFileContent));
        fclose($fp);
    }

    $teamDataArray = getTeamByTeamID($teamID);
    $icon = $teamDataArray["Icon"];
    // echo "TournamentID: ".$teamDataArray["TournamentID"]."<br>";
    echo "<h1 class='schatten' id='teamname' style='padding-right: 10px; display: inline-block;'><center>";
    echo "<img class='team-logo' src='/clashapp/data/misc/clash/logos/".$icon."/1_64.png' width='64' loading='lazy'><div class='team-title' id='team-title'>".strtoupper($teamDataArray["Tag"])." | ".strtoupper($teamDataArray["Name"])." (Tier ".$teamDataArray["Tier"].")</div></center></h1>";
    echo "<div><div id='suggested-ban-title'>Empfohlene Bans:</div>";
    echo "<div id='suggestedBans' class='schatten'></div></div>";
    echo "<div id='selectedBans' class='schatten' style='float: right; position: relative; right: 430px;'></div><br><br>";
?>
<form id="banSearch" class="schatten" action="" onsubmit="return false;" method="GET" autocomplete="off">
    <div id="top-ban-bar">
        <input type="text" name="champName" id="champSelector" value="" placeholder="Championname" style="margin-bottom: 5px;">
        <img class="lane-selector" style="filter: brightness(50%);" src="/clashapp/data/misc/lanes/UTILITY.png" width="28" onclick="highlightLaneIcon(this);" data-lane="sup" loading="lazy">
        <img class="lane-selector" style="filter: brightness(50%);" src="/clashapp/data/misc/lanes/BOTTOM.png" width="28" onclick="highlightLaneIcon(this);" data-lane="adc" loading="lazy">
        <img class="lane-selector" style="filter: brightness(50%);" src="/clashapp/data/misc/lanes/MIDDLE.png" width="28" onclick="highlightLaneIcon(this);" data-lane="mid" loading="lazy">
        <img class="lane-selector" style="filter: brightness(50%);" src="/clashapp/data/misc/lanes/JUNGLE.png" width="28" onclick="highlightLaneIcon(this);" data-lane="jgl" loading="lazy">
        <img class="lane-selector" style="filter: brightness(50%);" src="/clashapp/data/misc/lanes/TOP.png" width="28" onclick="highlightLaneIcon(this);" data-lane="top" loading="lazy">
    </div>
<?php
    echo "<div id='champSelect'>";
    showBanSelector();
    echo "</div>";

    echo "</form></h1>";
    echo "<table class='table' style='width:100%; table-layout: fixed;'><tr>";
    $tableWidth = round(100/count($teamDataArray["Players"]));
    $playerDataDirectory = new DirectoryIterator('/var/www/html/clash/clashapp/data/player/');
    $matchIDTeamArray = array();
    $masteryDataTeamArray = array();
    $playerLanesTeamArray = array();

    foreach($teamDataArray["Players"] as $key => $player){
        echo "<td style='vertical-align: top;'><table class='table schatten'><tr><td style='width:".$tableWidth."%; text-align: center;'>";

        unset($sumid);

        foreach ($playerDataDirectory as $playerDataJSONFile) { // going through all files
            $playerDataJSONPath = $playerDataJSONFile->getFilename();   // get all filenames as variable
            if(!($playerDataJSONPath == "." || $playerDataJSONPath == "..")){
                // echo str_replace(".json", "", $playerDataJSONPath) ." - ". $player["summonerId"];
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
                } else {
                    // echo "No Match found :(<br>".str_replace(".json", "", $playerDataJSONPath)."<br>".$player["summonerId"]."<br>";
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
        $playerNameTeamArray[] = $playerName;
        $playerSumidTeamArray[] = $sumid;
        $masteryDataTeamArray[$sumid] = $masteryData;

        echo "<center><div style='display: flex; justify-content: center; width: 200px; margin-bottom: 24px; position: relative;'>";
        if(file_exists('/var/www/html/clash/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.png')){
            echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.png" width="84" style="border-radius: 100%;margin-top: 25px; z-index: -1;" loading="lazy">';
        }
        $rankVal = 0;
        $levelFileName = "001";
        $highEloLP = "";
        foreach($rankData as $rankedQueue){
            if($rankedQueue["Queue"] == "RANKED_SOLO_5x5" || $rankedQueue["Queue"] == "RANKED_FLEX_SR" ){
                switch ($rankedQueue["Tier"]){
                    case ($rankedQueue["Tier"] == "CHALLENGER" && $rankVal < 9):
                        $rankVal = 9;
                        $rankNumber = "";
                        $highestRank = $rankedQueue["Tier"];
                        $highEloLP = $rankedQueue["LP"];
                        break;
                    case ($rankedQueue["Tier"] == "GRANDMASTER" && $rankVal < 8):
                        $rankVal = 8;
                        $rankNumber = "";
                        $highestRank = $rankedQueue["Tier"];
                        $highEloLP = $rankedQueue["LP"];
                        break;
                    case ($rankedQueue["Tier"] == "MASTER" && $rankVal < 7):
                        $rankVal = 7;
                        $rankNumber = "";
                        $highestRank = $rankedQueue["Tier"];
                        $highEloLP = $rankedQueue["LP"];
                        break;
                    case ($rankedQueue["Tier"] == "DIAMOND" && $rankVal < 6):
                        $rankVal = 6;
                        $rankNumber = $rankedQueue["Rank"];
                        $highestRank = $rankedQueue["Tier"];
                        break;
                    case ($rankedQueue["Tier"] == "PLATINUM" && $rankVal < 5):
                        $rankVal = 5;
                        $rankNumber = $rankedQueue["Rank"];
                        $highestRank = $rankedQueue["Tier"];
                        break;
                    case ($rankedQueue["Tier"] == "GOLD" && $rankVal < 4):
                        $rankVal = 4;
                        $rankNumber = $rankedQueue["Rank"];
                        $highestRank = $rankedQueue["Tier"];
                        break;
                    case ($rankedQueue["Tier"] == "SILVER" && $rankVal < 3):
                        $rankVal = 3;
                        $rankNumber = $rankedQueue["Rank"];
                        $highestRank = $rankedQueue["Tier"];
                        break;
                    case ($rankedQueue["Tier"] == "BRONZE" && $rankVal < 2):
                        $rankVal = 2;
                        $rankNumber = $rankedQueue["Rank"];
                        $highestRank = $rankedQueue["Tier"];
                        break;
                    case ($rankedQueue["Tier"] == "IRON" && $rankVal < 1):
                        $rankVal = 1;
                        $rankNumber = $rankedQueue["Rank"];
                        $highestRank = $rankedQueue["Tier"];
                        break;
                }
            }
        }
        if($rankVal != 0){
            $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/var/www/html/clash/clashapp/data/misc/ranks/*'.strtolower($highestRank).'_base.ls_ch.png', GlobIterator::CURRENT_AS_PATHNAME)))[0];
            $webBorderPath = str_replace("/var/www/html/clash","",$profileBorderPath);
            if(file_exists($profileBorderPath)){
                echo '<img src="'.$webBorderPath.'" width="384" style="position: absolute; top: -126px; z-index: -1;" loading="lazy">';
            }
            if ($highEloLP != ""){
                echo "<div style='font-weight: bold; color: #e8dfcc; position: absolute; margin-top: -5px; font-size: 12px;'>".$highEloLP." LP</div>";
            } else {
                echo "<div style='font-weight: bold; color: #e8dfcc; position: absolute; margin-top: 17px; font-size: 12px;'>".$rankNumber."</div>";
            }

            echo "<div style='color: #e8dfcc; position: absolute; margin-top: 111px; font-size: 12px;'>".$playerData["Level"]."</div>";
        } else {
            switch ($playerData["Level"]){
                case ($playerData["Level"] < 30):
                    $levelFileName = "001";
                    break;
                case ($playerData["Level"] < 50):
                    $levelFileName = "030";
                    break;
                case ($playerData["Level"] < 75):
                    $levelFileName = "050";
                    break;
                case ($playerData["Level"] < 100):
                    $levelFileName = "075";
                    break;
                case ($playerData["Level"] < 125):
                    $levelFileName = "100";
                    break;
                case ($playerData["Level"] < 150):
                    $levelFileName = "125";
                    break;
                case ($playerData["Level"] < 175):
                    $levelFileName = "150";
                    break;
                case ($playerData["Level"] < 200):
                    $levelFileName = "175";
                    break;
                case ($playerData["Level"] < 225):
                    $levelFileName = "200";
                    break;
                case ($playerData["Level"] < 250):
                    $levelFileName = "225";
                    break;
                case ($playerData["Level"] < 275):
                    $levelFileName = "250";
                    break;
                case ($playerData["Level"] < 300):
                    $levelFileName = "275";
                    break;
                case ($playerData["Level"] < 325):
                    $levelFileName = "300";
                    break;
                case ($playerData["Level"] < 350):
                    $levelFileName = "325";
                    break;
                case ($playerData["Level"] < 375):
                    $levelFileName = "350";
                    break;
                case ($playerData["Level"] < 400):
                    $levelFileName = "375";
                    break;
                case ($playerData["Level"] < 425):
                    $levelFileName = "400";
                    break;
                case ($playerData["Level"] < 450):
                    $levelFileName = "425";
                    break;
                case ($playerData["Level"] < 475):
                    $levelFileName = "450";
                    break;
                case ($playerData["Level"] < 500):
                    $levelFileName = "475";
                    break;
                case ($playerData["Level"] >= 500):
                    $levelFileName = "500";
                    break;
            }

        $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/var/www/html/clash/clashapp/data/misc/levels/prestige_crest_lvl_'.$levelFileName.'.png', GlobIterator::CURRENT_AS_PATHNAME)))[0];
        $webBorderPath = str_replace("/var/www/html/clash","",$profileBorderPath);

        if(file_exists($profileBorderPath)){
            echo '<img src="'.$webBorderPath.'" width="190" style="position: absolute;  top: -37px; z-index: -1;" loading="lazy">';
            }
        echo "<div style='color: #e8dfcc; position: absolute; margin-top: 105px; font-size: 12px;'>".$playerData["Level"]."</div>";
        }
        echo "</div></center>";
        echo "<div class='player-name'>".$playerName."</div>";

        getMatchIDs($puuid, 15);

        $matchids_sliced = array_slice($matchids, 0, 15);
        $matchDaten = getMatchData($matchids_sliced);
        $matchRankingArray = getMatchRanking($matchids_sliced, $matchDaten, $sumid);
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
echo '</body>';

include('footer.php');
?>