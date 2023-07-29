<?php 
session_start();

// print_r($_SESSION);

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$startInitialTime = microtime(true);
$memInitialTime = memory_get_usage();
include_once('/hdd1/clashapp/functions.php');
include_once('/hdd1/clashapp/update.php');
require_once '/hdd1/clashapp/clash-db.php';

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

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Clash', $css = true, $javascript = true, $alpinejs = true, $websocket = true);
include('/hdd1/clashapp/templates/header.php');

// ----------------------------------------------------------------v- INITIALIZER -v---------------------------------------------------------------- //

// These arrays are necessary and used for the getSuggestedBans function as parameters to retrieve the most accurate suggested ban data efficiently
$playerSumidTeamArray = array(); // collects all 5 sumids
$playerLanesTeamArray = array(); // collects all main and secondary roles per ["sumid"]
$masteryDataTeamArray = array(); // collects mastery data per ["sumid"] to have them combined in a single array
$matchIDTeamArray = array(); // collects ALL matchid's of the whole team combined (without duplicates)
$timeAndMemoryArray = array(); // saves the speed of every function and its  memory requirements
$timeAndMemoryArray["InitializingAndHeader"]["Time"] = number_format((microtime(true) - $startInitialTime), 2, ',', '.')." s";
$timeAndMemoryArray["InitializingAndHeader"]["Memory"] = number_format((memory_get_usage() - $memInitialTime)/1024, 2, ',', '.')." kB";
$execOnlyOnce = false;
$newMatchesDownloaded = false;
$recalculateSuggestedBanData = false;
$matchAlpineCounter = 0;
$currentPlayerNumber = 1;
$upToDate = false;
$matchDownloadLog = '/var/www/html/clash/clashapp/data/logs/matchDownloader.log'; // The log patch where any additional info about this process can be found
echo "<script>const requests = {};</script>";

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
        if(!file_exists('/hdd1/clashapp/data/teams/'.$teamID.'.json')){
            $fp = fopen('/hdd1/clashapp/data/teams/'.$teamID.'.json', 'c');
            $suggestedBanFileContent["SuggestedBans"] = [] ;
            $suggestedBanFileContent["Status"] = 0;
            $suggestedBanFileContent["LastUpdate"] = 0;
            $suggestedBanFileContent["Rating"] = [];
            fwrite($fp, json_encode($suggestedBanFileContent));
            fclose($fp);
        } 
        $timeAndMemoryArray["CheckBanFile"]["Time"] = number_format((microtime(true) - $startCheckBanFile), 2, ',', '.')." s";
        $timeAndMemoryArray["CheckBanFile"]["Memory"] = number_format((memory_get_usage() - $memCheckBanFile)/1024, 2, ',', '.')." kB";


// --------------------------------------------------------v- PRINT TOP PART TITLE, BANS & CO. -v-------------------------------------------------------- //

        $startPageTop = microtime(true);
        $memPageTop = memory_get_usage();
        // echo "TournamentID: ".$teamDataArray["TournamentID"]; // TODO: Add current tournament to view
        echo"
        <div id='top-part' class='h-[26rem] grid twok:grid-cols-topbartwok fullhd:grid-cols-topbarfullhd gap-4 mt-4 ml-4 mr-4 relative'>
            <div id='team-info' class='h-[26rem] row-span-2'>
                <div class='p-4 rounded bg-[#141624] h-[26rem] grid grid-rows-teaminfo'>
                    <h1 id='teamname' class='inline-flex items-center gap-4'>";
                    if(file_exists("/clashapp/data/misc/clash/logos/".$teamDataArray["Icon"]."/1_64.webp")){
                        echo "<img id='team-logo' src='/clashapp/data/misc/clash/logos/".$teamDataArray["Icon"]."/1_64.webp' width='64' alt='The in league of legends selected logo of the clash team'>";
                    } else {
                        echo "<img id='team-logo' src='/clashapp/data/misc/clash/logos/0/1_64.webp' width='64' alt='The in league of legends selected logo of the clash team'>";
                    } echo "
                        <span id='team-title' class='text-2xl break-all'>".strtoupper($teamDataArray["Tag"])." | ".strtoupper($teamDataArray["Name"])." (".__("Tier")." ".$teamDataArray["Tier"].")</span>
                    </h1>
                    <div class='h-full w-full flex flex-col justify-end'>
                    <span class='ml-1 mb-0.5 text-base font-bold'>".__("History")."</span>
                        <div id='historyContainer' class='bg-darker w-full h-32 p-2 flex flex-col-reverse overflow-auto twok:text-base fullhd:text-sm'>
                        </div>
                    </div>
                    "; /** ($teamDataArray); */ echo "
                </div>
            </div>
            <div class='row-span-2 h-[26rem] flex items-center justify-center rounded bg-[#141624]'>
                <div class='h-[21rem] w-[17.5rem] bg-black'>
                    <span class='h-[21rem] flex items-center justify-center'>".__("Advertisement")."</span> 
                </div>
            </div>
            <div class='row-span-2 h-[26rem] grid rounded bg-[#141624]'>
                <span class='w-full h-8 flex justify-center items-center'>".__("Suggested Bans").":</span>
                <div id='suggestedBans' class='w-full grid grid-cols-[64px_64px_64px_64px_64px] gap-y-8 p-2 justify-evenly rounded bg-[#141624] min-h-[180px]'></div>
                "; if(isset($_SESSION['user']['email'])){ echo "
                <div class='flex justify-center text-2xl h-8 mt-4'>
                    <div x-data=\"{ rating: ".(isset($_COOKIE[$_GET['name']]) ? $_COOKIE[$_GET['name']] : 0) .", hover: 0 }\" class='opacity-0' style='animation: .5s ease-in-out 1.5s 1 fadeIn; animation-fill-mode: forwards;'>
                        <div class='flex gap-x-0'>
                            <button type=\"button\" class=\"text-3xl p-0 w-8\" x-bind:class=\"{ 'text-[#0e0f18]' : rating < 1 && hover < 1, 'text-yellow-500': rating >= 1, 'text-[#414246]': hover >= 1 && rating < 1 }\" @mouseover=\"hover = 1\" @mouseout=\"hover = 0\" @click=\"rating = rating == 1 ? 0 : 1, modifyTeamRating(rating,'".hash("md5", $_SESSION['user']['id'])."')\">★</button>
                            <button type=\"button\" class=\"text-3xl p-0 w-8\" x-bind:class=\"{ 'text-[#0e0f18]' : rating < 2 && hover < 2, 'text-yellow-500': rating >= 2, 'text-[#414246]': hover >= 2 && rating < 2 }\" @mouseover=\"hover = 2\" @mouseout=\"hover = 0\" @click=\"rating = rating == 2 ? 0 : 2, modifyTeamRating(rating,'".hash("md5", $_SESSION['user']['id'])."')\">★</button>
                            <button type=\"button\" class=\"text-3xl p-0 w-8\" x-bind:class=\"{ 'text-[#0e0f18]' : rating < 3 && hover < 3, 'text-yellow-500': rating >= 3, 'text-[#414246]': hover >= 3 && rating < 3 }\" @mouseover=\"hover = 3\" @mouseout=\"hover = 0\" @click=\"rating = rating == 3 ? 0 : 3, modifyTeamRating(rating,'".hash("md5", $_SESSION['user']['id'])."')\">★</button>
                            <button type=\"button\" class=\"text-3xl p-0 w-8\" x-bind:class=\"{ 'text-[#0e0f18]' : rating < 4 && hover < 4, 'text-yellow-500': rating >= 4, 'text-[#414246]': hover >= 4 && rating < 4 }\" @mouseover=\"hover = 4\" @mouseout=\"hover = 0\" @click=\"rating = rating == 4 ? 0 : 4, modifyTeamRating(rating,'".hash("md5", $_SESSION['user']['id'])."')\">★</button>
                            <button type=\"button\" class=\"text-3xl p-0 w-8\" x-bind:class=\"{ 'text-[#0e0f18]' : rating < 5 && hover < 5, 'text-yellow-500': rating >= 5, 'text-[#414246]': hover >= 5 && rating < 5 }\" @mouseover=\"hover = 5\" @mouseout=\"hover = 0\" @click=\"rating = rating == 5 ? 0 : 5, modifyTeamRating(rating,'".hash("md5", $_SESSION['user']['id'])."')\">★</button>
                        </div>
                    </div>
                </div>"; }  else { echo "
                    <div class='flex justify-center text-2xl h-8 mt-4 opacity-0' style='animation: .5s ease-in-out 1.5s 1 fadeIn; animation-fill-mode: forwards;'>
                        <div class='flex justify-center gap-x-0' x-data=\"{ showNotice: false }\" x-cloak @mouseover='showNotice = true' @mouseout='showNotice = false'>
                            <button type=\"button\" class=\"text-3xl text-[#0e0f18] p-0 w-8 cursor-default\">★</button>
                            <button type=\"button\" class=\"text-3xl text-[#0e0f18] p-0 w-8 cursor-default\">★</button>
                            <button type=\"button\" class=\"text-3xl text-[#0e0f18] p-0 w-8 cursor-default\">★</button>
                            <button type=\"button\" class=\"text-3xl text-[#0e0f18] p-0 w-8 cursor-default\">★</button>
                            <button type=\"button\" class=\"text-3xl text-[#0e0f18] p-0 w-8 cursor-default\">★</button>
                            <span class='text-sm absolute top-[16rem]' x-show='showNotice' x-transition>".sprintf(__("Voting is only available for %slogged-in%s users"), "<a href='/login' class='underline'>", "</a>")."</span>
                        </div>
                    </div>"; } echo "
                    <div class='flex justify-center items-center opacity-0' style='animation: .5s ease-in-out 1.5s 1 fadeIn; animation-fill-mode: forwards;'>
                        <div class='group relative inline-block' x-data='{ tooltip: 0 }' x-cloak>
                            <input type='text' value='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."' onclick=\"copyToClipboard('https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."');\" class='cursor-copy m-8 text-lg p-3 w-fit bg-[#0e0f18] rounded-xl' readonly @click='tooltip = 1, setTimeout(() => tooltip = 0, 2000)'></input>
                            <div class='w-40 bg-black/50 text-white text-center text-xs rounded-lg py-2 absolute z-30 bottom-3/4 ml-[6.75rem] px-3' x-show='tooltip' x-transition @click='tooltip = 0'>
                                Copied to Clipboard
                                <svg class='absolute text-black h-2 w-full left-0 top-full' x='0px' y='0px' viewBox='0 0 255 255' xml:space='preserve'><polygon class='fill-current' points='0,0 127.5,127.5 255,0'/></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-span-2 h-[26rem] flex items-center justify-center rounded bg-[#141624]'>
                    <div class='h-[21rem] w-[17.5rem] bg-black'>
                        <span class='h-[21rem] flex items-center justify-center'>".__("Advertisement")."</span>
                    </div>
                </div>
                <div class='flex flex-wrap justify-center rounded bg-[#141624] overflow-hidden h-fit pt-1.5 pb-3 transition-all ease-out'>
                    <div class='rotation-title -mb-1.5 flex w-full fullhd:text-sm twok:text-base twok:px-11 max-h-[27px]'>
                        <div class='flex justify-center items-center font-bold w-3/5 bg-lose twok:pb-[6.75rem] fullhd:pb-[6rem] pt-4 rounded-l-md'>".__("First Rotation")."</div>
                        <div class='flex justify-center items-center font-bold w-2/5 bg-mid bg-opacity-10 twok:pb-[6.75rem] fullhd:pb-[6rem] pt-4 rounded-r-md'>".__("Second Rotation")."</div>
                    </div>
                    <div id='selectedBans' class='w-full max-w-[40rem] h-fit flex flex-wrap text-center twok:gap-x-4 twok:px-11 fullhd:pl-[3px] fullhd:gap-x-3 gap-y-2 fullhd:min-h-[85px] twok:min-h-[97px]'>
                    </div>
                </div>
                <div class='flex justify-center rounded bg-[#141624] twok:h-[16.5rem] fullhd:h-[17rem] transition-all ease-out'>
                    <form id='banSearch' class='m-0 pb-4 mb-4 w-full overflow-hidden' action='' onsubmit='return false;' method='GET' autocomplete='off'>
                        <div id='top-ban-bar' class='h-10 text-black'>
                            <div class='inline'>
                                <input type='text' name='champName' id='champSelector' class='mb-[5px] h-8 p-2 twok:pr-10 fullhd:w-[55%]' value='' placeholder='".__("Championname")."'>
                                <button id='champSelectorClear' class='bg-transparent text-gray-500 hover:text-gray-700 focus:outline-none -ml-7 px-2 py-1' onclick='this.previousElementSibling.value=\"\";'>x</button>
                            </div>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer' src='/clashapp/data/misc/lanes/UTILITY.webp' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='sup' alt='An icon for the support lane'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/BOTTOM.webp' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='adc' alt='An icon for the bottom lane'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/MIDDLE.webp' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='mid' alt='An icon for the middle lane'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/JUNGLE.webp' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='jgl' alt='An icon for the jungle'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/TOP.webp' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='top' alt='An icon for the top lane'>
                        </div>
                        <div id='champSelect' class='overflow-y-scroll twok:gap-2 twok:gap-y-4 fullhd:gap-y-1 pl-[10px] inline-flex flex-wrap w-full -ml-[0.3rem] pt-1 twok:w-[97%] twok:ml-1 twok:h-[13rem] fullhd:h-[13.5rem]'>";
                            showBanSelector(); echo "
                        </div>
                        <div id='emptySearchEmote' class='hidden items-center justify-center gap-2 h-3/5 relative -top-48'><img src='/clashapp/data/misc/webp/empty_search.webp' class='w-16' alt='A frog emoji with a questionmark'><span>".__("Whoops, did you mistype?")."</span></div>
                    </form>
                </div>
            </div>";
        $timeAndMemoryArray["PageTop"]["Time"] = number_format((microtime(true) - $startPageTop), 2, ',', '.')." s";
        $timeAndMemoryArray["PageTop"]["Memory"] = number_format((memory_get_usage() - $memPageTop)/1024, 2, ',', '.')." kB";

// -------------------------------------------------------------v- PRINT SEPARATE PLAYER COLUMN -v------------------------------------------------------------- //

        $startFetchPlayerTotal = microtime(true);
        $memFetchPlayerTotal = memory_get_usage();
        if (isset($_COOKIE["matches-expanded"])) {
            $matchesExpanded = 'true';
          } else {
            $matchesExpanded = 'false';
          }
        echo "
        <script>const playerCount = ".count($teamDataArray["Players"])."</script>
        <table class='w-full flex table-fixed border-separate border-spacing-4 min-h-[2300px]' x-data='{ advancedGlobal: ".$matchesExpanded." }'>
            <tr>";
            // count($teamDataArray["Players"]) == 1 ? $tableWidth = "100%" : $tableWidth = round(100/count($teamDataArray["Players"]));          // disabled due to cumulative layout shift
                $playerDataDirectory = new DirectoryIterator('/hdd1/clashapp/data/player/');
                $playerSpawnDelay = 0;
                foreach($teamDataArray["Players"] as $key => $player){ 
                    $startFetchPlayer[$key] = microtime(true);
                    $memFetchPlayer[$key] = memory_get_usage();
                    echo "
                    <td class='align-top w-1/5 max-w-[19.25vw] opacity-0' style='animation: .5s ease-in-out ".$playerSpawnDelay."s 1 fadeIn; animation-fill-mode: forwards;'>
                        <table class='rounded bg-[#141624]'>
                            <tr>
                                <td class='w-1/5 text-center'>";
                                    $playerSpawnDelay += 0.2;
                                    unset($sumid); // necessary for check 22 lines below
                                    foreach ($playerDataDirectory as $playerDataJSONFile) { // going through all files
                                        $playerDataJSONPath = $playerDataJSONFile->getFilename();   // get all filenames as variable
                                        if(!($playerDataJSONPath == "." || $playerDataJSONPath == "..")){
                                            // echo str_replace(".json", "", $playerDataJSONPath) ." - ". $player["summonerId"];
                                            if(str_replace(".json", "", $playerDataJSONPath) == $player["summonerId"]){ // if the team players sumid = filename in player json path
                                                if(file_exists('/hdd1/clashapp/data/teams/'.$teamID.'.json')){
                                                    $tempTeamJSON = json_decode(file_get_contents('/hdd1/clashapp/data/teams/'.$teamID.'.json'), true);
                                                    $playerDataJSON = json_decode(file_get_contents('/hdd1/clashapp/data/player/'.$playerDataJSONPath), true); // get filepath content as variable
                                                    isset($_GET["reload"]) ? $forceReload = true : $forceReload = false;
                                                    if(((time() - $tempTeamJSON["LastUpdate"]) > 600) || ($tempTeamJSON["LastUpdate"] == 0) || ($forceReload)){ // FIXME: force reload only temp for testing
                                                        $tempMatchIDs = getMatchIDs($playerDataJSON["PlayerData"]["PUUID"], 15);
                                                        if(array_keys($playerDataJSON["MatchIDs"])[0] != $tempMatchIDs[0]){ // If first matchid is outdated -> call updateProfile below because $sumid is still unset from above
                                                            echo "<script>console.log('INFO: ".$playerDataJSON["PlayerData"]["Name"]." was out-of-date -> Force updating.'); requests['".$player["summonerId"]."'] = 'Pending';</script>";
                                                            $newMatchesDownloaded = true;
                                                            break;
                                                        } else {
                                                            $playerName = $playerDataJSON["PlayerData"]["Name"];
                                                            $playerData = $playerDataJSON["PlayerData"];
                                                            $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                                            $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                                            $rankData = $playerDataJSON["RankData"];
                                                            $masteryData = $playerDataJSON["MasteryData"];
                                                            $matchids = array_keys($playerDataJSON["MatchIDs"]);

                                                            echo "<script>console.log('".$playerName." already up to date.'); requests['".$player["summonerId"]."'] = 'Done';</script>";
                                                            // echo "<script>console.log('DEBUG: New Teamupdate: ".(time() - $tempTeamJSON["LastUpdate"])."')</script>";
                                                            // HIERNACH MATCHIDS SENDEN
                                                            $recalculateMatchIDs = false;
                                                            $recalculatePlayerLanes = false;
                                                            foreach($playerDataJSON["MatchIDs"] as $singleMatchID => $score){
                                                                if($score == ""){
                                                                    $recalculateMatchIDs = true;
                                                                    break;
                                                                }
                                                            }

                                                            if(!isset($playerDataJSON["LanePercentages"]) || $playerDataJSON["LanePercentages"] == null){
                                                                $recalculatePlayerLanes = true;
                                                            } else {
                                                                $playerLanes = $playerDataJSON["LanePercentages"];
                                                            }

                                                            $xhrMessage = "";
                                                            
                                                            if($recalculatePlayerLanes){
                                                                if($recalculateMatchIDs){
                                                                    // If both player lanes and matchscores are missing/wrong in players .json
                                                                    $xhrMessage = "mode=both&matchids=".json_encode($playerDataJSON["MatchIDs"])."&path=".$playerDataJSONPath."&puuid=".$puuid."&sumid=".$sumid;
                                                                } else {
                                                                    // if only player lanes are missing/wrong
                                                                    $xhrMessage = "mode=lanes&matchids=".json_encode($playerDataJSON["MatchIDs"])."&path=".$playerDataJSONPath."&puuid=".$puuid;
                                                                }
                                                            } elseif($recalculateMatchIDs){
                                                                // if only matchscores are wrong/missing
                                                                $xhrMessage = "mode=scores&matchids=".json_encode($playerDataJSON["MatchIDs"])."&path=".$playerDataJSONPath."&sumid=".$sumid;
                                                            }

                                                            if($recalculatePlayerLanes || $recalculateMatchIDs){
                                                                echo "<script>
                                                                ".processResponseData($currentPlayerNumber)."
                                                                var data = '".$xhrMessage."';
                                                                xhrAfter".$currentPlayerNumber.".send(data);
                                                                </script>
                                                                ";
                                                                $currentPlayerNumber++;
                                                            } else {
                                                                $upToDate = true; // FIXME: As soon as update.php is changed to add via javascript -> remove this
                                                                $tempTeamJSON["LastUpdate"] = time();
                                                                $fp = fopen('/hdd1/clashapp/data/teams/'.$teamID.'.json', 'w+');
                                                                fwrite($fp, json_encode($tempTeamJSON));
                                                                fclose($fp);
                                                            }
                                                            break;
                                                        }
                                                    } else {
                                                        $playerName = $playerDataJSON["PlayerData"]["Name"];
                                                        $playerData = $playerDataJSON["PlayerData"];
                                                        $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                                        $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                                        $rankData = $playerDataJSON["RankData"];
                                                        $masteryData = $playerDataJSON["MasteryData"];
                                                        $matchids = array_keys($playerDataJSON["MatchIDs"]);

                                                        echo "<script>console.log('".$playerName." already up to date.'); requests['".$player["summonerId"]."'] = 'Done';</script>";
                                                        // echo "<script>console.log('DEBUG: Last Teamupdate < 10mins ago: ".(time() - $tempTeamJSON["LastUpdate"])."')</script>";
                                                        // HIERNACH MATCHIDS SENDEN
                                                        $recalculateMatchIDs = false;
                                                        $recalculatePlayerLanes = false;
                                                        foreach($playerDataJSON["MatchIDs"] as $singleMatchID => $score){
                                                            if($score == ""){
                                                                $recalculateMatchIDs = true;
                                                                break;
                                                            }
                                                        }

                                                        if(!isset($playerDataJSON["LanePercentages"]) || $playerDataJSON["LanePercentages"] == null){
                                                            $recalculatePlayerLanes = true;
                                                        } else {
                                                            $playerLanes = $playerDataJSON["LanePercentages"];
                                                        }

                                                        $xhrMessage = "";
                                                        
                                                        if($recalculatePlayerLanes){
                                                            if($recalculateMatchIDs){
                                                                // If both player lanes and matchscores are missing/wrong in players .json
                                                                $xhrMessage = "mode=both&matchids=".json_encode($playerDataJSON["MatchIDs"])."&path=".$playerDataJSONPath."&puuid=".$puuid."&sumid=".$sumid;
                                                            } else {
                                                                // if only player lanes are missing/wrong
                                                                $xhrMessage = "mode=lanes&matchids=".json_encode($playerDataJSON["MatchIDs"])."&path=".$playerDataJSONPath."&puuid=".$puuid;
                                                            }
                                                        } elseif($recalculateMatchIDs){
                                                            // if only matchscores are wrong/missing
                                                            $xhrMessage = "mode=scores&matchids=".json_encode($playerDataJSON["MatchIDs"])."&path=".$playerDataJSONPath."&sumid=".$sumid;
                                                        }

                                                        if($recalculatePlayerLanes || $recalculateMatchIDs){
                                                            echo "<script>
                                                            ".processResponseData($currentPlayerNumber)."
                                                            var data = '".$xhrMessage."';
                                                            xhrAfter".$currentPlayerNumber.".send(data);
                                                            </script>
                                                            ";
                                                            $currentPlayerNumber++;
                                                        } else {
                                                            $upToDate = true;

                                                        }
                                                    }
                                                }  
                                            } else {
                                                // TODO: Error Handling echo "No Match found :(<br>".str_replace(".json", "", $playerDataJSONPath)."<br>".$player["summonerId"]."<br>";
                                            }
                                        }
                                    }
                                    if(!isset($sumid) && $player["summonerId"] != "") {
                                        echo "<script>console.log('No playerfile found for ".$player["summonerId"]."'); requests['".$player["summonerId"]."'] = 'Pending';</script>";
                                        updateProfile($player["summonerId"], 15, "sumid");
                                        foreach ($playerDataDirectory as $playerDataJSONFile) { // going through all files
                                            $playerDataJSONPath = $playerDataJSONFile->getFilename();   // get all filenames as variable
                                            if(!($playerDataJSONPath == "." || $playerDataJSONPath == "..")){
                                                if(str_replace(".json", "", $playerDataJSONPath) == $player["summonerId"]){ // if the team players sumid = filename in player json path
                                                    $playerDataJSON = json_decode(file_get_contents('/hdd1/clashapp/data/player/'.$playerDataJSONPath), true); // get filepath content as variable
                                                    $playerData = $playerDataJSON["PlayerData"];
                                                    $playerName = $playerDataJSON["PlayerData"]["Name"];
                                                    $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                                    $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                                    $rankData = $playerDataJSON["RankData"];
                                                    $masteryData = $playerDataJSON["MasteryData"];
                                                    $matchids = array_keys($playerDataJSON["MatchIDs"]);
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                    if($teamDataArray["Players"][$key] == end($teamDataArray["Players"])){ // If we are at the last player (all possible downloads would be ready at this point)
                                        if($newMatchesDownloaded){
                                            $recalculateSuggestedBanData = true;
                                        }
                                    }
                                    $playerSumidTeamArray[$sumid] = $playerName;
                                    $masteryDataTeamArray[$sumid] = $masteryData;
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["FetchPlayerData"]["Time"] = number_format((microtime(true) - $startFetchPlayer[$key]), 2, ',', '.')." s";
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["FetchPlayerData"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayer[$key])/1024, 2, ',', '.')." kB";
                                
                                    // ----------------------------------------------------------------v- PROFILE ICON BORDERS -v---------------------------------------------------------------- //

                                    if(!$execOnlyOnce) $startProfileIconBorders = microtime(true);
                                    $memProfileIconBorders = memory_get_usage();
                                    echo "<div class='h-40 mt-4 grid grid-cols-2 gap-4 single-player-column' data-puuid='".$puuid."' data-sumid='".$sumid."'>
                                        <div class='relative flex justify-center overflow-hidden'>";
                                            if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.webp')){
                                                echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerData["Icon"].'.webp" width="84" height="84" class="rounded-full mt-6 z-0 max-h-[84px] max-w-[84px] pointer-events-none select-none" alt="The custom profile icon of a player">';
                                            } else {
                                                echo "Missing Img"; // FIXME: Create Fallback
                                            }

                                            $rankOrLevelArray = getRankOrLevel($rankData, $playerData);
                                            if($rankOrLevelArray["Type"] === "Rank"){ // If user has a rank
                                                // Print the profile border image url for current highest rank
                                                $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/hdd1/clashapp/data/misc/ranks/*'.strtolower($rankOrLevelArray["HighestRank"]).'_base.ls_ch.webp', GlobIterator::CURRENT_AS_PATHNAME)))[0];
                                                $webBorderPath = str_replace("/hdd1","",$profileBorderPath);
                                                if(file_exists($profileBorderPath)){
                                                    echo '<img src="'.$webBorderPath.'" width="384" height="384" class="max-w-[384px] -top-32 absolute z-10 pointer-events-none select-none" style="-webkit-mask-image: radial-gradient(circle at center, white 20%, transparent 33%); mask-image: radial-gradient(circle at center, white 20%, transparent 33%);" alt="The profile border corresponding to a players rank">';
                                                }
                                                // Additionally print LP count if user is Master+ OR print the rank number (e.g. IV)
                                                if ($rankOrLevelArray["HighEloLP"] != ""){
                                                    echo "<div class='font-bold color-[#e8dfcc] absolute -mt-2 text-xs z-20'>".$rankOrLevelArray["HighEloLP"]." LP</div>";
                                                } else {
                                                    echo "<div class='font-bold color-[#e8dfcc] absolute mt-[0.85rem] text-xs z-20'>".$rankOrLevelArray["RankNumber"]."</div>";
                                                }
                                                
                                                echo "<div class='color-[#e8dfcc] absolute mt-[6.8rem] text-xs z-20'>".$playerData["Level"]."</div>"; // Always current lvl at the bottom
                                            } else if($rankOrLevelArray["Type"] === "Level") { // Else set to current level border
                                                $profileBorderPath = array_values(iterator_to_array(new GlobIterator('/hdd1/clashapp/data/misc/levels/prestige_crest_lvl_'.$rankOrLevelArray["LevelFileName"].'.webp', GlobIterator::CURRENT_AS_PATHNAME)))[0];
                                                $webBorderPath = str_replace("/hdd1","",$profileBorderPath);
                                                if(file_exists($profileBorderPath)){
                                                    echo '<img src="'.$webBorderPath.'" width="190" height="190" class="absolute -mt-[2.05rem] z-10 pointer-events-none select-none" style="-webkit-mask-image: radial-gradient(circle at center, white 50%, transparent 70%); mask-image: radial-gradient(circle at center, white 50%, transparent 70%);" alt="The profile border corresponding to a players level">';
                                                    }
                                            echo "<div class='absolute text-[#e8dfcc] mt-24 text-xs z-20 twok:mt-[6.8rem]'>".$playerData["Level"]."</div>";
                                            } echo "
                                  <span class='absolute mt-[8.75rem] z-20'>".$playerName."</span></div>";

                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Time"] = number_format((microtime(true) - $startProfileIconBorders), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Memory"] = number_format((memory_get_usage() - $memProfileIconBorders)/1024, 2, ',', '.')." kB";

                            // ----------------------------------------------------------------v- GET REUSABLE MATCH DATA -v---------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startGetMatchData = microtime(true);
                            $memGetMatchData = memory_get_usage();
                            $matchids_sliced = array_slice($matchids, 0, 15); // Select first 15 MatchIDs of current player
                            $slicedPlayerDataMatchIDs = array_slice($playerDataJSON["MatchIDs"], 0, 15);
                            
                            if(!$execOnlyOnce) $startGetLanePercentages = microtime(true);
                            $memGetLanePercentages = memory_get_usage();
                            // print_r($playerLanes);
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetLanePercentages"]["Time"] = number_format((microtime(true) - $startGetLanePercentages), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetLanePercentages"]["Memory"] = number_format((memory_get_usage() - $memGetLanePercentages)/1024, 2, ',', '.')." kB";
                            if(!$execOnlyOnce) $startGetMostPlayedWith = microtime(true);
                            $memGetMostPlayedWith = memory_get_usage();
                            // $mostPlayedWithArray = mostPlayedWith($matchDaten, $puuid); // As the name says <--
                            // print_r($mostPlayedWithArray);
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMostPlayedWith"]["Time"] = number_format((microtime(true) - $startGetMostPlayedWith), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["GetMostPlayedWith"]["Memory"] = number_format((memory_get_usage() - $memGetMostPlayedWith)/1024, 2, ',', '.')." kB";
                            unset($matchDaten); // cleanup array for next player & faster loadtime

                            // ------------------------------------------------------------------v- PRINT PLAYER LANES -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startProfileIconBorders = microtime(true);
                            $memProfileIconBorders = memory_get_usage();

                            $queueRole = $player["position"];
                            if(isset($playerLanes)){
                                $playerMainRole = $playerLanes[0];
                                $playerSecondaryRole = $playerLanes[1];
                                // Also add main & secondary role to collective team array
                                $playerLanesTeamArray[$sumid]["Mainrole"] = $playerLanes[0];
                                $playerLanesTeamArray[$sumid]["Secrole"] = $playerLanes[1];

                                echo "<div class='inline-flex leading-8 gap-1 z-20'>
                                        <div class='grid w-11/12 gap-2 h-fit'>
                                            <div class='flex h-8 items-center justify-between'>
                                                <span>".__("Queued as").":</span>
                                                <div class='inline-flex w-[4.5rem] justify-center' x-data='{ exclamation: false }'>";
                                                if(file_exists('/hdd1/clashapp/data/misc/lanes/'.$queueRole.'.webp')){
                                                    if($queueRole != $playerMainRole && $queueRole != $playerSecondaryRole){ // TODO: Also add Tag "Off Position"
                                                        echo '<img class="saturate-0 brightness-150" src="/clashapp/data/misc/lanes/'.$queueRole.'.webp" width="32" height="32" alt="A league of legends lane icon corresponding to a players position as which he queued up in clash">
                                                            <span class="text-yellow-400 absolute z-40 text-xl -mr-12 font-bold mt-0.5 cursor-help px-1.5" src="/clashapp/data/misc/webp/exclamation-yellow.webp" width="16" loading="lazy" @mouseover="exclamation = true" @mouseout="exclamation = false">!</span>
                                                            <div class="bg-black/50 text-white text-center text-xs rounded-lg w-40 whitespace-pre-line py-2 px-3 absolute z-30 -ml-16 twok:bottom-[49.75rem] fullhd:bottom-[34.75rem]" x-show="exclamation" x-transition x-cloak>'.__("This player did not queue on their main position")
                                                            .'<svg class="absolute text-black h-2 w-full left-0 ml-14 top-full" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve">
                                                                <polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon>
                                                            </svg>
                                                            </div>';
                                                    } else {
                                                        echo '<img class="saturate-0 brightness-150" src="/clashapp/data/misc/lanes/'.$queueRole.'.webp" width="32" height="32" alt="A league of legends lane icon corresponding to a players position as which he queued up in clash">';
                                                    }
                                                } echo"</div>
                                            </div>
                                            <div class='flex h-8 items-center justify-between'>
                                                <span class='lane-positions'>".__("Position(s)").":</span>
                                                <div class='inline-flex gap-2 w-[72px] justify-center'>";
                                                if(file_exists('/hdd1/clashapp/data/misc/lanes/'.$playerMainRole.'.webp')){
                                                    echo '<img class="saturate-0 brightness-150" src="/clashapp/data/misc/lanes/'.$playerMainRole.'.webp" width="32" height="32" alt="A league of legends lane icon corresponding to a players main position">';
                                                }
                                                if(file_exists('/hdd1/clashapp/data/misc/lanes/'.$playerSecondaryRole.'.webp')){
                                                    echo '<img class="saturate-0 brightness-150" src="/clashapp/data/misc/lanes/'.$playerSecondaryRole.'.webp" width="32" height="32" alt="A league of legends lane icon corresponding to a players secondary position">';
                                                }echo "</div>
                                            </div>";
                                            if(!$execOnlyOnce) $startPrintAverageMatchscore = microtime(true);
                                            $memPrintAverageMatchscore = memory_get_usage(); echo "
                                            <div class='flex h-8 items-center justify-between'>
                                                <span>".__("Avg. Score").":</span>
                                                <div class='inline-flex w-[4.5rem] justify-center'>
                                                    <span class='transition-opacity duration-500 easy-in-out'>";
                                                    if($upToDate){
                                                        echo number_format((array_sum(array_values($playerDataJSON["MatchIDs"]))/count(array_values($playerDataJSON["MatchIDs"]))), 2);
                                                    } echo "</span>
                                                </div>
                                            </div>"; // TODO: Add avg. matchscore in file
                                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintAverageMatchscore"]["Time"] = number_format((microtime(true) - $startPrintAverageMatchscore), 2, ',', '.')." s";
                                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintAverageMatchscore"]["Memory"] = number_format((memory_get_usage() - $memPrintAverageMatchscore)/1024, 2, ',', '.')." kB"; echo "
                                        </div>
                                    </div>
                                </div>";
                                unset($playerLanes);
                            } else {
                                echo "<div class='inline-flex leading-8 gap-1 z-20'>
                                        <div class='grid w-11/12 gap-2 h-fit'>
                                            <div class='flex h-8 items-center justify-between'>
                                                <span>".__("Queued as").":</span>
                                                <div class='inline-flex w-[4.5rem] justify-center' x-data='{ exclamation: false }'>";
                                                if(file_exists('/hdd1/clashapp/data/misc/lanes/'.$queueRole.'.webp')){
                                                    echo '<img class="saturate-0 brightness-150" src="/clashapp/data/misc/lanes/'.$queueRole.'.webp" width="32" height="32" alt="A league of legends lane icon corresponding to a players position as which he queued up in clash">';
                                                } echo "
                                                </div>
                                            </div>
                                            <div class='flex h-8 items-center justify-between'>
                                                <span class='lane-positions'>".__("Position(s)").":</span>
                                                <div class='inline-flex gap-2 w-[72px] justify-center'>
                                                </div>
                                            </div>
                                            <div class='flex h-8 items-center justify-between'>
                                                <span>".__("Avg. Score").":</span>
                                                <div class='inline-flex w-[4.5rem] justify-center'>
                                                    <span class='transition-opacity duration-500 easy-in-out opacity-0'></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>";
                            }

                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Time"] = number_format((microtime(true) - $startProfileIconBorders), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["ProfileIconBorders"]["Memory"] = number_format((memory_get_usage() - $memProfileIconBorders)/1024, 2, ',', '.')." kB";





                            // ------------------------------------------------------------------v- AGAIN DOWNLOAD? -v------------------------------------------------------------------ //

                            // if(!$execOnlyOnce) $startAgainDownload = microtime(true);
                            // $memAgainDownload = memory_get_usage();
                            // foreach($matchids_sliced as $matchid){
                            //     if(!file_exists('/hdd1/clashapp/data/matches/' . $matchid . ".json")){
                            //         downloadMatchByID($matchid, $playerName);
                            //     }
                            // }
                            // if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["StartAgainDownload"]["Time"] = number_format((microtime(true) - $startAgainDownload), 2, ',', '.')." s";
                            // if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["StartAgainDownload"]["Memory"] = number_format((memory_get_usage() - $memAgainDownload)/1024, 2, ',', '.')." kB";

                            // ------------------------------------------------------------------v- PRINT RANKED STATS -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startPrintRankedStats = microtime(true);
                            $memPrintRankedStats = memory_get_usage();
                            echo "
                            <tr>
                                <td class='text-center h-32 min-h-[8rem]'> 
                                    <div class='inline-flex w-full justify-evenly'>";
                                    if(empty($rankData) || empty(array_intersect(array("RANKED_SOLO_5x5", "RANKED_FLEX_SR"), array_column($rankData,"Queue")))){
                                        echo "<div class='flex items-center gap-2 rounded bg-[#0e0f18] p-2'><img src='/clashapp/data/misc/webp/unranked_emote.webp' width='64' height='64' loading='lazy' class='w-16' alt='A blitzcrank emote with a questionmark in case this player has no retrievable ranked data'><span class='min-w-[5.5rem]'>".__("Unranked")."</span></div>";
                                    } else {
                                        foreach($rankData as $rankQueue){
                                            if($rankQueue["Queue"] == "RANKED_SOLO_5x5"){ echo "
                                                <div class='rounded bg-[#0e0f18] my-2.5 mx-5 p-2'>
                                                <span class='block text-[0.75rem]'>".__("Ranked Solo/Duo").":</span>
                                                    <span class='text-".strtolower($rankQueue["Tier"])."/100'>".__(ucfirst(strtolower($rankQueue["Tier"]))). " " . $rankQueue["Rank"];
                                            } else if($rankQueue["Queue"] == "RANKED_FLEX_SR"){ echo "
                                                <div class='rounded bg-[#0e0f18] my-2.5 mx-5 p-2'>
                                                    <span class='block text-[0.75rem]'>".__("Ranked Flex").":</span>
                                                    <span class='block text-".strtolower($rankQueue["Tier"])."/100'>".__(ucfirst(strtolower($rankQueue["Tier"]))). " " . $rankQueue["Rank"];
                                            } 
                                            if(($rankQueue["Queue"] == "RANKED_SOLO_5x5") || ($rankQueue["Queue"] == "RANKED_FLEX_SR")){
                                                echo " / " . $rankQueue["LP"] . " ".__("LP")."</span><div class='flex justify-center gap-x-1'><span class='relative block cursor-help'
                                                    onmouseenter='showTooltip(this, \"".__('Winrate')."\", 500, \"top-right\")'
                                                    onmouseleave='hideTooltip(this)'>
                                                    ".__("WR").": </span><span class='inline-block'>" . round((($rankQueue["Wins"]/($rankQueue["Wins"]+$rankQueue["Losses"]))*100),2) . "%</span></div>
                                                        <span class='text-[0.75rem]'>(".$rankQueue["Wins"]+$rankQueue["Losses"]." ".__("Games").")</span>
                                                    </div>";
                                            }
                                        } // TODO: Add previous seasons ranks
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
                                <td class='text-center h-32'>
                                    <div class='inline-flex gap-8'>";
                                        if(sizeof($masteryData) >= 3){
                                            for($i=0; $i<3; $i++){
                                                if(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryData[$i]["Filename"].'.webp')){ echo '
                                                    <div>
                                                        <img src="/clashapp/data/patch/'.$currentPatch.'/img/champion/'.$masteryData[$i]["Filename"].'.webp" width="64" height="64" class="block relative z-0;" alt="A champion icon of the league of legends champion '.$masteryData[$i]["Champion"].'">
                                                        <span>'.$masteryData[$i]["Champion"].'</span>
                                                        <img src="/clashapp/data/misc/mastery-'.$masteryData[$i]["Lvl"].'.webp" width="32" height="32" class="relative -top-[5.75rem] -right-11 z-10" alt="A mastery hover icon on top of the champion icon in case the player has achieved level 5 or higher">';
                                                        if(str_replace(',','', $masteryData[$i]["Points"]) > 999999){
                                                            echo "<div class='-mt-7 text-".getMasteryColor(str_replace(',','',$masteryData[$i]["Points"]))."/100'>".str_replace(",",".",substr($masteryData[$i]["Points"],0,4))."m</div>";
                                                        } else {
                                                            echo "<div class='-mt-7 text-".getMasteryColor(str_replace(',','',$masteryData[$i]["Points"]))."/100'>".explode(",",$masteryData[$i]["Points"])[0]."k</div>";
                                                        } echo "
                                                    </div>";
                                                }
                                            }
                                        } else { echo
                                            "<div>".
                                            '<img src="/clashapp/data/misc/webp/empty_search.webp" height="64" width="64" alt="A frog emoji with a questionmark"></div>'.
                                            "</div>";
                                        } echo "
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMasteryData"]["Time"] = number_format((microtime(true) - $startPrintMasteryData), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMasteryData"]["Memory"] = number_format((memory_get_usage() - $memPrintMasteryData)/1024, 2, ',', '.')." kB";
                            
                            // -------------------------------------------------------------------v- PRINT TAGS  -v------------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startPrintTags = microtime(true);
                            $memPrintTags = memory_get_usage();

                            // TODO: Fetch & Print tags + include premades function

                            

                            echo "
                            <tr>
                                <td>
                                    <div class='max-h-[5.7rem] overflow-hidden mb-2 flex flex-wrap px-4 justify-evenly gap-1'>";
                                    $smurfProbability = calculateSmurfProbability($playerData, $rankData, $masteryData);
                                    if ($smurfProbability >= 0.4 && $smurfProbability < 0.6){
                                        echo "<div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#855e16] cursor-help'>".__("Smurf")."</div>";
                                    } else if ($smurfProbability >= 0.6 && $smurfProbability <= 0.8){
                                        echo "<div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#9c3515] cursor-help'>".__("Smurf")."</div>";
                                    } else if ($smurfProbability > 0.8){
                                        echo "<div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#ff0000] cursor-help'>".__("Smurf")."</div>";
                                    }
                                    echo "
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("MVP")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Dragonkiller")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Newly")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Invader")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Lowbob")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Captain")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Premate")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>
                                            <div class='list-none border border-solid border-[#141624] py-2 px-3 rounded-3xl text-[#cccccc] bg-[#0e0f18] cursor-help'>".__("Test")."</div>";
                                        echo "
                                    </div>
                                </td>
                            </tr>";

                            // echo "<pre>";
                            //     print_r($mostPlayedWithArray);
                            // echo "</pre>";

                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintTags"]["Time"] = number_format((microtime(true) - $startPrintTags), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintTags"]["Memory"] = number_format((memory_get_usage() - $memPrintTags)/1024, 2, ',', '.')." kB";

                            // ---------------------------------------------------v- SAVE DATA FOR MATCH HISTORY AND END TOP PART PLAYER  -v---------------------------------------------------- //

                            $tempStoreArray[$key]["matchids_sliced"] = $matchids_sliced;
                            $tempStoreArray[$key]["puuid"] = $puuid;
                            $tempStoreArray[$key]["sumid"] = $sumid;
                            $tempStoreArray[$key]["matchRankingArray"] = $playerDataJSON["MatchIDs"];

                            echo "
                        </table>
                    </td>";
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
                unset($matchids_sliced);
                unset($slicedPlayerDataMatchIDs);
                $startGetSuggestedBans = microtime(true);
                $memGetSuggestedBans = memory_get_usage();
                clearstatcache(true, $matchDownloadLog); // Used for proper filesize calculation
                $currentTime = new DateTime("now", new DateTimeZone('Europe/Berlin'));
                $endofup = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: Start of update for \"".strtoupper($teamDataArray["Tag"])." | ".strtoupper($teamDataArray["Name"])."\" - (Approximate Logsize: ".number_format((filesize($matchDownloadLog)/1048576), 3)." MB)";
                $border = "[" . $currentTime->format('d.m.Y H:i:s') . "] [matchDownloader - INFO]: -------------------------------------------------------------------------------------";
                file_put_contents($matchDownloadLog, $border.PHP_EOL , FILE_APPEND | LOCK_EX);
                file_put_contents($matchDownloadLog, $endofup.PHP_EOL , FILE_APPEND | LOCK_EX);

                // -----------------------------------------------------------------------------v- MIDDLE AD BANNER  -v----------------------------------------------------------------------- //

                echo "
                </tr>
                <tr class='trenner h-32 bg-darker rounded'>
                    <td colspan='5'>
                        <div class='flex items-center justify-start gap-4 w-full' style='content-visibility: auto;'>
                            <div class='rounded bg-[#141624] p-4'>
                                <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                                    <span class='h-full flex items-center justify-center'>".__("Advertisement")."</span> 
                                </div>
                            </div>
                            <div class='rounded bg-[#141624] p-4'>
                                <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                                    <span class='h-full flex items-center justify-center'>".__("Advertisement")."</span> 
                                </div>
                            </div>
                            <div class='grid rounded bg-[#141624] h-[122px] p-4 w-full text-center min-w-max items-center'>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-pointer accent-[#27358b]' name='expand-all-matches' id='expand-all-matches' @change='document.getElementById(\"expand-all-matches\").checked ? advancedGlobal = true : advancedGlobal = false'></input><label for='expand-all-matches'> ".__("Expand all matches")."</label></div>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-pointer accent-[#27358b]' name='additional-setting' id='additional-setting' disabled></input><label for='additional-setting'> ".__("Additional setting")."</label></div>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-pointer accent-[#27358b]' name='additional-setting2' id='additional-setting2' disabled></input><label for='additional-setting2'> ".__("Additional setting")."</label></div>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-pointer accent-[#27358b]' name='additional-setting3' id='additional-setting3' disabled></input><label for='additional-setting3'> ".__("Additional setting")."</label></div>
                            </div>
                        </div>
                        </div>
                    </td>
                </tr>
                <tr>";

                // ------------------------------------------------------------------------v- PRINT MATCH HISTORY & CONTROL PANEL -v--------------------------------------------------------------- //
                
                echo '
                    <td colspan="5">
                        <div class="bg-dark w-full rounded-t h-8 -mb-[1.15rem]"></div>
                    </td>
                </tr>
                <tr id="match-history">';
            foreach($tempStoreArray as $key => $player){ 
                if(!$execOnlyOnce) $startPrintMatchHistory = microtime(true);
                $memPrintMatchHistory = memory_get_usage(); 
                echo "
                <td class='align-top w-1/5 max-w-[19.25vw] opacity-0' style='animation: .5s ease-in-out 0s 1 fadeIn; animation-fill-mode: forwards;'>
                    <table class='rounded-b bg-[#141624] w-full'>
                        <tr>
                            <td x-data='{ open: false }' x-init='setTimeout(() => open = true, ".$matchAlpineCounter.")' class='single-player-match-history' data-puuid='".$player["puuid"]."' data-sumid='".$player["sumid"]."'>";
                                if($upToDate){
                                    echo printTeamMatchDetailsByPUUID($player["matchids_sliced"], $player["puuid"], $player["matchRankingArray"]);
                                }
                                echo "
                            </td>
                        </tr>
                    </table>
                </td>";
                $matchAlpineCounter += 50;
                if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMatchHistory"]["Time"] = number_format((microtime(true) - $startPrintMatchHistory), 2, ',', '.')." s";
                if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$playerName]["PrintMatchHistory"]["Memory"] = number_format((memory_get_usage() - $memPrintMatchHistory)/1024, 2, ',', '.')." kB";
                } echo "
            </tr>
        </table>";
        $timeAndMemoryArray["FetchPlayerTotal"]["Time"] = number_format((microtime(true) - $startFetchPlayerTotal), 2, ',', '.')." s";
        $timeAndMemoryArray["FetchPlayerTotal"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayerTotal)/1024, 2, ',', '.')." kB";

    // -------------------------------------------------------------------------------v- CALCULATE & PRINT SUGGESTED BAN DATA  -v------------------------------------------------------------------------------- //

    // $recalculateSuggestedBanData = true; // uncomment to force recalc
    // Check if suggested ban data is already locally stored
    $currentTeamJSON = json_decode(file_get_contents('/hdd1/clashapp/data/teams/'.$teamID.'.json'), true);
    if(isset($currentTeamJSON["SuggestedBanData"])){
        $suggestedBanArray = $currentTeamJSON["SuggestedBanData"];
        $timer = 0;
        $zIndex = 10;
        foreach($suggestedBanArray as $champname => $banChampion){
                echo '<div class="suggested-ban-champion inline-block text-center w-16 h-16 opacity-0 relative" style="animation: .5s ease-in-out '.$timer.'s 1 fadeIn; animation-fill-mode: forwards; z-index: '.$zIndex.';" x-data="{ showExplanation: false }">
                    <div class="ban-hoverer inline-grid" onclick="addToFile(this.parentElement);" @mouseover="showExplanation=true" @mouseout="showExplanation=false">
                        <img class="cursor-help fullhd:w-12 twok:w-14" width="56" height="56" data-id="' . $banChampion["Filename"] . '" src="/clashapp/data/patch/' . $currentPatch . '/img/champion/' . str_replace(' ', '', $banChampion["Filename"]) . '.webp" alt="A league of legends champion icon of '.$champname.'"></div>
                    <span class="suggested-ban-caption w-16 block">' . $champname . '</span>
                    <div class="grid grid-cols-[35%_15%_auto] w-[27rem] bg-black/90 text-white text-center text-xs rounded-lg py-2 absolute ml-16 -mt-[5.5rem] px-3" x-show="showExplanation" x-transition x-cloak @mouseenter="showExplanation = true" @mouseleave="showExplanation = false">
                    <div class="py-3 px-2 flex justify-end items-center font-bold border-b-2 border-r-2 border-solid border-dark text-end">'.__('Category').'</div><div class="py-3 px-2 flex justify-center items-center font-bold border-b-2 border-r-2 border-solid border-dark">'.__('Addition').'</div><div class="py-3 px-2 flex justify-start text-left font-bold border-b-2 border-solid border-dark">'.__('Explanation').'</div>';
                    if(isset($suggestedBanArray[$champname]["Points"]["Value"])){
                        echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Highest Mastery').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["Points"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$playerSumidTeamArray[$suggestedBanArray[$champname]["Points"]["Cause"]].' '.__('achieved a mastery score of').' '.$suggestedBanArray[$champname]["Points"]["Value"].' '.__('on').' '.$champname.'.</div>';
                    }
                    if(isset($suggestedBanArray[$champname]["TotalTeamPoints"]["Value"])){
                        echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Total Team Mastery').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["TotalTeamPoints"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.__('This team has a combined mastery score of').' '.str_replace(".", ",", $suggestedBanArray[$champname]["TotalTeamPoints"]["Value"]).' '.__('on').' '.$champname.'.</div>';
                    }
                    if(isset($suggestedBanArray[$champname]["CapablePlayers"]["Value"])){
                        if($suggestedBanArray[$champname]["CapablePlayers"]["Value"] > 1){
                            echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Capable Player').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["CapablePlayers"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$suggestedBanArray[$champname]["CapablePlayers"]["Value"].' '.__('summoners of this team are able to play').' '.$champname.'.</div>';
                        } else {
                            echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Capable Player').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["CapablePlayers"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$suggestedBanArray[$champname]["CapablePlayers"]["Value"].' '.__('summoner of this team is able to play').' '.$champname.'.</div>';
                        }
                    }
                    if(isset($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"])){
                        echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Matching Laners').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["MatchingLanersPrio"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">';
                    foreach($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"] as $laner){
                        if($laner == reset($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"])){
                            echo $playerSumidTeamArray[$laner];
                        } else if($laner == end($suggestedBanArray[$champname]["MatchingLanersPrio"]["Cause"])){
                            echo " & ".$playerSumidTeamArray[$laner];
                        } else {
                            echo ", ".$playerSumidTeamArray[$laner];
                        }
                    } echo ' '.__('are able to perform with').' '.$champname.' '.__('while matching lanes').' ('; 
                    foreach($suggestedBanArray[$champname]["MatchingLanersPrio"]["Lanes"] as $lane){
                        if($lane == reset($suggestedBanArray[$champname]["MatchingLanersPrio"]["Lanes"])){
                            echo ucfirst(strtolower($lane));
                        } else if($laner == end($suggestedBanArray[$champname]["MatchingLanersPrio"]["Lanes"])){
                            echo " & ".ucfirst(strtolower($lane));
                        } else {
                            echo ", ".ucfirst(strtolower($lane));
                        }
                    } echo').</div>  '; } echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Last Played').':</div>
                    <div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["LastPlayed"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.__('The last time someone played').' '.$champname.' '.__('was').' '.timeDiffToText($suggestedBanArray[$champname]["LastPlayed"]["Value"]).'.</div>';
                    if(isset($suggestedBanArray[$champname]["OccurencesInLastGames"]["Count"])){
                        echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">'.__('Occurences').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["OccurencesInLastGames"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$champname.' '.__('was played').' ';
                        echo $suggestedBanArray[$champname]["OccurencesInLastGames"]["Count"] > 1 ? $suggestedBanArray[$champname]["OccurencesInLastGames"]["Count"].' '.__('times').' ' : ' '.__('once').' ';
                        echo __('in the teams').' '.$suggestedBanArray[$champname]["OccurencesInLastGames"]["Games"].' '.__('unique fetched Flex or Clash games').'.</div>';
                    } 
                    if(isset($suggestedBanArray[$champname]["AverageMatchScore"]["Add"])){
                        echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-dark text-end">'.__('Average Matchscore').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-dark">+ '.number_format($suggestedBanArray[$champname]["AverageMatchScore"]["Add"],2,'.','').'</div><div class="py-3 px-2 flex justify-center text-left">'.__('The average matchscore achieved on').' '.$champname.' '.__('is').' '.$suggestedBanArray[$champname]["AverageMatchScore"]["Value"].'.</div>';
                    } echo '
                    <div class="py-3 px-2 flex justify-end items-center font-bold border-solid border-r-2 border-t-2 border-dark text-end">'.__('Finalscore').':</div><div class="py-3 px-2 flex justify-center items-center underline decoration-double font-bold border-solid border-r-2 border-t-2 border-dark text-base underline-offset-2">'.number_format($suggestedBanArray[$champname]["FinalScore"],2,'.','').'</div><div class="flex justify-end items-end text-gray-600 border-solid border-t-2 border-dark"><a href="/docs" onclick="return false;">&#187; '.__('Graphs & Formulas').'</a></div>
                    <svg class="absolute text-black/90 h-4 -ml-4 mt-5 rotate-90" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve"><polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon></svg>
                    </div>';
                    echo '</div>';
                    $timer += 0.1;
                    $zIndex--;
                    if($zIndex == 5) $zIndex = 10;
                }
                echo "<script>console.log('Suggested Bans generated locally');</script>";
            }
    }

    // ------------------------------------------------------------------------------------------v- BOTTOM AD BANNERS  -v------------------------------------------------------------------------------------------- //

    echo "
    <div class='flex items-center justify-center twok:gap-x-48 fullhd:gap-x-36 mb-6 mt-2' style='content-visibility: auto;'>
        <div class='rounded bg-[#141624] p-4'>
            <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                <span class='h-full flex items-center justify-center'>".__("Advertisement")."</span> 
            </div>
        </div>
        <div class='rounded bg-[#141624] p-4'>
            <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                <span class='h-full flex items-center justify-center'>".__("Advertisement")."</span> 
            </div>
        </div>
    </div>
    ";
    
    // ----------------------------------------------------------------------------------------------v- END + FOOTER  -v---------------------------------------------------------------------------------------------- //

}
include('/hdd1/clashapp/templates/footer.php');
$timeAndMemoryArray["Total"]["Time"] = number_format((microtime(true) - $startInitialTime), 2, ',', '.')." s";
$timeAndMemoryArray["Total"]["Memory"] = number_format((memory_get_usage() - $memInitialTime)/1024, 2, ',', '.')." kB";

    // --------------------------------------------------------------------------------------------------v- DEBUG  -v-------------------------------------------------------------------------------------------------- //

// echo "
// <script>console.log(".json_encode($timeAndMemoryArray).");
// console.log({
//     PlayerData: playerDataCalls,
//     MasteryScore: masteryScoresCalls,
//     CurrentRank: currentRankCalls,
//     MatchIDs: matchIdCalls,
//     MatchDownload: matchDownloadCalls,
//     TotalCalls: playerDataCalls+masteryScoresCalls+currentRankCalls+matchIdCalls+matchDownloadCalls});</script>";
?>