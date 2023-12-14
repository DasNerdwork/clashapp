<?php 
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


$startInitialTime = microtime(true);
$memInitialTime = memory_get_usage();
include_once('/hdd1/clashapp/functions.php');
include_once('/hdd1/clashapp/update.php');
require_once '/hdd1/clashapp/clash-db.php';
require_once '/hdd1/clashapp/mongo-db.php';

/**
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 */

// -----------------------------------------------------------v- CHECK STAY LOGGED IN COOKIE -v----------------------------------------------------------- //

$db = new DB();
if ((isset($_COOKIE['stay-logged-in'])) && !isset($_SESSION['user'])) {
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
$mdb = new MongoDBHelper();
$playerSumidTeamArray = array(); // collects all 5 sumids
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
$currentPlayerNumber2 = 1;
$xhrPCDcount = 1;
$upToDate = false;
$allUpToDate = 0;
$emoteSources = array("/clashapp/data/misc/webp/ok.webp?version=".md5_file("/hdd1/clashapp/data/misc/webp/ok.webp"),"/clashapp/data/misc/webp/teemo.webp?version=".md5_file("/hdd1/clashapp/data/misc/webp/teemo.webp"),"/clashapp/data/misc/webp/priceless.webp?version=".md5_file("/hdd1/clashapp/data/misc/webp/priceless.webp"));
$matchDownloadLog = '/var/www/html/clash/clashapp/data/logs/matchDownloader.log'; // The log patch where any additional info about this process can be found
$autosuggestRequest = $mdb->getAutosuggestAggregate();
$championDataArray = json_decode(file_get_contents("/hdd1/clashapp/data/patch/".$currentPatch."/data/en_US/champion.json"), true);
$championArray = array();
foreach ($championDataArray['data'] as $championKey => $championInfo) {
    $championArray["{$championInfo['name']}"] = "{$championInfo['image']['full']}";
}
if($autosuggestRequest["success"]){
    $autosuggestData = $autosuggestRequest["data"];
    echo "<script>const autosuggestData = " . json_encode(array_map('trim', $autosuggestData)) . ";</script>";
} else {
    echo "<script>const autosuggestData = '';</script>";
}
echo "
<script>
const requests = {};
var cached = 0;
const currentPatch = " . json_encode($currentPatch) . ";
const championData = " . json_encode($championArray) . ";
const containerTitle = '" . __("Summoner") . "';
const searchHistoryTitle = '" . __("Recently Searched") . "';
</script>";


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
        if(!$mdb->findDocumentByField('teams', 'TeamID', $teamID)["success"]){
            $suggestedBanFileContent["TeamID"] = $teamID;
            $suggestedBanFileContent["SuggestedBans"] = [];
            $suggestedBanFileContent["Status"] = 0;
            $suggestedBanFileContent["LastUpdate"] = 0;
            $suggestedBanFileContent["Rating"] = (object) [];
            $mdb->insertDocument('teams', $suggestedBanFileContent);
        }
        $timeAndMemoryArray["CheckBanFile"]["Time"] = number_format((microtime(true) - $startCheckBanFile), 2, ',', '.')." s";
        $timeAndMemoryArray["CheckBanFile"]["Memory"] = number_format((memory_get_usage() - $memCheckBanFile)/1024, 2, ',', '.')." kB";

// ------------------------------------------------------------v- CUSTOM BAN CONTEXT MENU -v------------------------------------------------------------ //

echo '
<div id="customBanContextMenu" class="opacity-0 absolute z-50 bg-[#202124] px-1 py-1 rounded text-sm border-[#646464] border-[1px] cursor-pointer transition-opacity duration-75">
<ul class="bg-[#202124] hover:bg-[#3f4042] px-1 py-0.5 rounded">
    <li onclick="lockSelectedBan(currentSelectedContextMenuElement)">🔒 <span class="text-xs">'.__("Lock Ban").'</span></li>
</ul>
</div>
<div id="customBanUnlockMenu" class="opacity-0 absolute z-50 bg-[#202124] px-1 py-1 rounded text-sm border-[#646464] border-[1px] cursor-pointer transition-opacity duration-75">
<ul class="bg-[#202124] hover:bg-[#3f4042] px-1 py-0.5 rounded">
    <li onclick="unlockSelectedBan(currentSelectedContextMenuElement)">🔓 <span class="text-xs">'.__("Unlock Ban").'</span></li>
</ul>
</div>
';

// --------------------------------------------------------v- PRINT TOP PART TITLE, BANS & CO. -v-------------------------------------------------------- //

        $startPageTop = microtime(true);
        $memPageTop = memory_get_usage();
        // echo "TournamentID: ".$teamDataArray["TournamentID"]; // TODO: Add current tournament to view
        echo"
        <div id='top-part' class='h-[26rem] grid twok:grid-cols-topbartwok fullhd:grid-cols-topbarfullhd gap-4 mt-4 ml-4 mr-4 relative'>
            <div id='team-info' class='h-[26rem] row-span-2'>
                <div class='p-4 rounded bg-[#141624] h-[26rem] grid grid-rows-teaminfo'>
                    <h1 id='teamname' class='inline-flex items-center gap-4'>";
                    if(fileExistsWithCache("/clashapp/data/misc/clash/logos/".$teamDataArray["Icon"]."/1_64.webp")){
                        echo "<img id='team-logo' src='/clashapp/data/misc/clash/logos/".$teamDataArray["Icon"]."/1_64.webp?version=".md5_file('/hdd1/clashapp/data/misc/clash/logos/'.$teamDataArray["Icon"].'/1_64.webp')."' width='64' alt='The in league of legends selected logo of the clash team'>";
                    } else {
                        echo "<img id='team-logo' src='/clashapp/data/misc/clash/logos/0/1_64.webp?version=".md5_file('/hdd1/clashapp/data/misc/clash/logos/0/1_64.webp')."' width='64' alt='The in league of legends selected logo of the clash team'>";
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
                    "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                    <span class='h-[21rem] flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                    } else { echo "
                        <div class='lazyhtml' data-lazyhtml onvisible>
                            <script type='text/lazyhtml'>
                            <!--
                            <ins class='adsbygoogle'
                                    style='display:block;height:336px;width:100%'
                                    data-ad-client='ca-pub-8928684248089281'
                                    data-ad-slot='9162424205'
                                    data-full-width-responsive='true'></ins>
                            <script>
                                    (adsbygoogle = window.adsbygoogle || []).push({});
                            </script>
                            -->
                            </script>
                        </div>
                    "; } echo "
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
                            <input type='text' aria-label='Copy to Clipboard' value='https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."' onclick=\"copyToClipboard('https://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."');\" class='cursor-copy m-8 text-lg p-3 w-fit bg-[#0e0f18] rounded-xl' readonly @click='tooltip = 1, setTimeout(() => tooltip = 0, 2000)'></input>
                            <div class='w-40 bg-black/50 text-white text-center text-xs rounded-lg py-2 absolute z-30 bottom-3/4 ml-[6.75rem] px-3' x-show='tooltip' x-transition @click='tooltip = 0'>
                                Copied to Clipboard
                                <svg class='absolute text-black h-2 w-full left-0 top-full' x='0px' y='0px' viewBox='0 0 255 255' xml:space='preserve'><polygon class='fill-current' points='0,0 127.5,127.5 255,0'/></svg>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='row-span-2 h-[26rem] flex items-center justify-center rounded bg-[#141624]'>
                    <div class='h-[21rem] w-[17.5rem] bg-black'>
                        "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                        <span class='h-[21rem] flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                        } else { echo "
                        <div class='lazyhtml' data-lazyhtml onvisible>
                            <script type='text/lazyhtml'>
                            <!--
                            <ins class='adsbygoogle'
                                style='display:block;height:336px;width:100%'
                                data-ad-client='ca-pub-8928684248089281'
                                data-ad-slot='8062709929'
                                data-full-width-responsive='true'></ins>
                            <script>
                                (adsbygoogle = window.adsbygoogle || []).push({});
                            </script>
                            -->
                            </script>
                        </div>
                        "; } echo "
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
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer' src='/clashapp/data/misc/lanes/UTILITY.webp?version=".md5_file('/hdd1/clashapp/data/misc/lanes/UTILITY.webp')."' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='sup' alt='An icon for the support lane'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/BOTTOM.webp?version=".md5_file('/hdd1/clashapp/data/misc/lanes/BOTTOM.webp')."' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='adc' alt='An icon for the bottom lane'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/MIDDLE.webp?version=".md5_file('/hdd1/clashapp/data/misc/lanes/MIDDLE.webp')."' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='mid' alt='An icon for the middle lane'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/JUNGLE.webp?version=".md5_file('/hdd1/clashapp/data/misc/lanes/JUNGLE.webp')."' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='jgl' alt='An icon for the jungle'>
                            <img class='lane-selector saturate-0 brightness-50 float-right cursor-pointer mr-2.5 fullhd:mr-1' src='/clashapp/data/misc/lanes/TOP.webp?version=".md5_file('/hdd1/clashapp/data/misc/lanes/TOP.webp')."' width='28' height='28' onclick='highlightLaneIcon(this);' data-lane='top' alt='An icon for the top lane'>
                        </div>
                        <div id='champSelect' class='overflow-y-scroll twok:gap-2 twok:gap-y-4 fullhd:gap-y-1 pl-[10px] inline-flex flex-wrap w-full -ml-[0.3rem] pt-1 twok:w-[97%] twok:ml-1 twok:h-[13rem] fullhd:h-[13.5rem]'>";
                            showBanSelector(); echo "
                        </div>
                        <div id='emptySearchEmote' class='hidden items-center justify-center gap-2 h-3/5 relative -top-48'><img src='/clashapp/data/misc/webp/empty_search.webp?version=".md5_file('/hdd1/clashapp/data/misc/webp/empty_search.webp')."' class='w-16' alt='A frog emoji with a questionmark'><span>".__("Whoops, did you mistype?")."</span></div>
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
        <table class='w-full table table-fixed border-separate border-spacing-4 min-h-[2300px]' x-data='{ advancedGlobal: ".$matchesExpanded." }'>
            <tr>";
            // count($teamDataArray["Players"]) == 1 ? $tableWidth = "100%" : $tableWidth = round(100/count($teamDataArray["Players"]));          // disabled due to cumulative layout shift
                $playerSpawnDelay = 0;
                isset($_GET["reload"]) ? $forceReload = true : $forceReload = false;
                foreach($teamDataArray["Players"] as $key => $player){ 
                    echo generatePlayerColumnData($xhrPCDcount, $player["summonerId"], $teamID, $player["position"], $forceReload);
                    $xhrPCDcount++;
                    $startFetchPlayer[$key] = microtime(true);
                    $memFetchPlayer[$key] = memory_get_usage();
                    echo "
                    <td class='align-top w-1/5 opacity-0 relative' style='animation: .5s ease-in-out ".$playerSpawnDelay."s 1 fadeIn; animation-fill-mode: forwards;'>
                        <table class='rounded bg-[#141624]'>
                        <tbody id='animate-body-".$currentPlayerNumber2."' class='animate-pulse'>
                            <tr>
                                <td class='w-1/5 text-center'>";
                                    $playerSpawnDelay += 0.2;
                                    unset($sumid); // necessary for check 22 lines below
                                    $playerDataRequest = $mdb->getPlayerBySummonerId($player["summonerId"]);
                                    if($playerDataRequest["success"]){
                                        if($mdb->findDocumentByField('teams', 'TeamID', $teamID)["success"]){
                                            $tempTeamJSON = $mdb->findDocumentByField('teams', 'TeamID', $teamID)["document"];
                                            $playerDataJSONString = json_encode($playerDataRequest["data"]);
                                            $playerDataJSON = json_decode($playerDataJSONString, true);
                                            isset($_GET["reload"]) ? $forceReload = true : $forceReload = false;
                                            if(((time() - $tempTeamJSON->LastUpdate) > 1800) || ($tempTeamJSON->LastUpdate == 0) || ($forceReload)){ // FIXME: force reload only temp for testing
                                                $tempMatchIDs = getMatchIDs($playerDataJSON["PlayerData"]["PUUID"], 15);                                           
                                                $matchInPlayerJsonButNotExistent = false;
                                                foreach(array_keys($playerDataJSON["MatchIDs"]) as $matchid) {
                                                    if(!$mdb->findDocumentByField("matches", 'metadata.matchId', $matchid)["success"]){
                                                        $matchInPlayerJsonButNotExistent = true;
                                                    }
                                                }
                                                if((array_keys($playerDataJSON["MatchIDs"])[0] != $tempMatchIDs[0]) || $matchInPlayerJsonButNotExistent){ // If first matchid is outdated -> call updateProfile below because $sumid is still unset from above
                                                    echo "<script>console.log('INFO: ".$playerDataJSON["PlayerData"]["GameName"]." was out-of-date -> Force updating.'); requests['".$player["summonerId"]."'] = 'Pending';</script>";
                                                    $newMatchesDownloaded = true;
                                                } else {
                                                    $playerName = $playerDataJSON["PlayerData"]["GameName"];
                                                    $playerTag = $playerDataJSON["PlayerData"]["Tag"];
                                                    $playerData = $playerDataJSON["PlayerData"];
                                                    $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                                    $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                                    $rankData = $playerDataJSON["RankData"];
                                                    $masteryData = $playerDataJSON["MasteryData"];
                                                    $matchids = array_keys($playerDataJSON["MatchIDs"]);

                                                    echo "<script>console.log('".$playerName." already up to date.'); requests['".$player["summonerId"]."'] = 'Done';</script>";
                                                    // echo "<script>console.log('DEBUG: New Teamupdate: ".(time() - $tempTeamJSON->LastUpdate)."')</script>";
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
                                                            $xhrMessage = "mode=both&matchids=".json_encode($playerDataJSON["MatchIDs"])."&puuid=".$puuid."&sumid=".$sumid;
                                                        } else {
                                                            // if only player lanes are missing/wrong
                                                            $xhrMessage = "mode=lanes&matchids=".json_encode($playerDataJSON["MatchIDs"])."&puuid=".$puuid."&sumid=".$sumid;
                                                        }
                                                    } elseif($recalculateMatchIDs){
                                                        // if only matchscores are wrong/missing
                                                        $xhrMessage = "mode=scores&matchids=".json_encode($playerDataJSON["MatchIDs"])."&sumid=".$sumid;
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
                                                        $allUpToDate++;
                                                        if($allUpToDate == count($teamDataArray["Players"]) ){ // Reset anti-request timer if all people are up to date and onAllFinish not called
                                                            // $tempTeamJSON->LastUpdate = time();
                                                            $mdb->addElementToDocument('teams', 'TeamID', $teamID, 'LastUpdate', time());
                                                        }
                                                    }
                                                }
                                            } else {
                                                $playerName = $playerDataJSON["PlayerData"]["GameName"];
                                                $playerTag = $playerDataJSON["PlayerData"]["Tag"];
                                                $playerData = $playerDataJSON["PlayerData"];
                                                $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                                $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                                $rankData = $playerDataJSON["RankData"];
                                                $masteryData = $playerDataJSON["MasteryData"];
                                                $matchids = array_keys($playerDataJSON["MatchIDs"]);

                                                echo "<script>console.log('".$playerName." already up to date.'); requests['".$player["summonerId"]."'] = 'Done';</script>";
                                                // echo "<script>console.log('DEBUG: Last Teamupdate < 10mins ago: ".(time() - $tempTeamJSON->LastUpdate)."')</script>";
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
                                    if(!isset($sumid) && $player["summonerId"] != "") {
                                        echo "<script>console.log('No playerfile found or out of date (".$player["summonerId"].")'); requests['".$player["summonerId"]."'] = 'Pending';</script>";
                                        updateProfile($player["summonerId"], 15, "sumid");
                                        $playerDataRequest = $mdb->getPlayerBySummonerId($player["summonerId"]);
                                        if($playerDataRequest["success"]){
                                            $playerDataJSONString = json_encode($playerDataRequest["data"]);
                                            $playerDataJSON = json_decode($playerDataJSONString, true);
                                            $playerData = $playerDataJSON["PlayerData"];
                                            $playerName = $playerDataJSON["PlayerData"]["GameName"];
                                            $playerTag = $playerDataJSON["PlayerData"]["Tag"];
                                            $sumid = $playerDataJSON["PlayerData"]["SumID"];
                                            $puuid = $playerDataJSON["PlayerData"]["PUUID"];
                                            $rankData = $playerDataJSON["RankData"];
                                            $masteryData = $playerDataJSON["MasteryData"];
                                            $matchids = array_keys($playerDataJSON["MatchIDs"]);
                                        }
                                    }
                                    if($teamDataArray["Players"][$key] == end($teamDataArray["Players"])){ // If we are at the last player (all possible downloads would be ready at this point)
                                        if($newMatchesDownloaded){
                                            $recalculateSuggestedBanData = true;
                                        }
                                    }
                                    $playerSumidTeamArray[$sumid] = $playerName;
                                    $masteryDataTeamArray[$sumid] = $masteryData;
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["FetchPlayerData"]["Time"] = number_format((microtime(true) - $startFetchPlayer[$key]), 2, ',', '.')." s";
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["FetchPlayerData"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayer[$key])/1024, 2, ',', '.')." kB";
                                
                                    // ----------------------------------------------------------------v- PROFILE ICON BORDERS -v---------------------------------------------------------------- //

                                    if(!$execOnlyOnce) $startProfileIconBorders = microtime(true);
                                    $memProfileIconBorders = memory_get_usage();
                                    $randomIconPath = glob("/hdd1/clashapp/data/patch/{$currentPatch}/img/profileicon/*")[array_rand(glob("/hdd1/clashapp/data/patch/{$currentPatch}/img/profileicon/*"))];
                                    echo "
                                        <div id='single-player-column-".$currentPlayerNumber2."' class='h-40 mt-4 grid grid-cols-2 gap-4 single-player-column' data-sumid='".$player["summonerId"]."'>
                                        <div class='relative flex justify-center'>
                                        <img id='profileicon-".$currentPlayerNumber2."' src='".str_replace('/hdd1', '', $randomIconPath)."?version=".md5_file($randomIconPath)."' width='84' height='84' style='filter: grayscale(100%)' class='rounded-full mt-6 z-0 max-h-[84px] max-w-[84px] pointer-events-none select-none' alt='The custom profile icon of a player'>
                                        <div class='playerlevel text-loading-light absolute mt-[6.8rem] text-xs z-20'>30</div>
                                        <img src='/clashapp/data/misc/levels/prestige_crest_lvl_030.webp?version=".md5_file("/hdd1/clashapp/data/misc/levels/prestige_crest_lvl_030.webp")."' width='190' height='190' style='filter: grayscale(100%)' class='profileborder-030 absolute -mt-[2.05rem] z-10 pointer-events-none select-none' style='-webkit-mask-image: radial-gradient(circle at center, white 50%, transparent 70%); mask-image: radial-gradient(circle at center, white 50%, transparent 70%);' alt='The profile border corresponding to a players level'>
                                        <div class='absolute mt-[8.75rem] z-20'><span id='playername-".$currentPlayerNumber2."' class='text-loading-light'>".__("Player")." ".$currentPlayerNumber2."</span><span id='playertag-".$currentPlayerNumber2."' class='bg-loading px-1 rounded ml-1 text-sm text-gray-300'>#EUW</span></div></div>";

                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["ProfileIconBorders"]["Time"] = number_format((microtime(true) - $startProfileIconBorders), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["ProfileIconBorders"]["Memory"] = number_format((memory_get_usage() - $memProfileIconBorders)/1024, 2, ',', '.')." kB";
                            $matchids_sliced = array_slice($matchids, 0, 15); // Select first 15 MatchIDs of current player

                            // ------------------------------------------------------------------v- PRINT PLAYER LANES -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startProfileIconBorders = microtime(true);
                            $memProfileIconBorders = memory_get_usage();

                            $queueRole = $player["position"];

                            echo "<div class='inline-flex leading-8 gap-1 z-20'>
                                    <div class='grid w-11/12 gap-2 h-fit'>
                                        <div class='flex h-8 items-center justify-between'>
                                            <span class='text-loading-light'>".__("Queued as").":</span>
                                            <div class='inline-flex w-[4.5rem] justify-center' x-data='{ exclamation: false }'>";
                                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/lanes/'.$queueRole.'.webp')){
                                                echo '<img id="queuerole-'.$currentPlayerNumber2.'" class="saturate-0 brightness-100" src="/clashapp/data/misc/lanes/'.$queueRole.'.webp?version='.md5_file('/hdd1/clashapp/data/misc/lanes/'.$queueRole.'.webp').'" width="32" height="32" alt="A league of legends lane icon corresponding to a players position as which he queued up in clash">';
                                            } echo "</div>
                                        </div>
                                        <div class='flex h-8 items-center justify-between'>
                                            <span class='lane-positions text-loading-light'>".__("Position(s)").":</span>
                                            <div class='inline-flex gap-2 w-[72px] justify-center'>";
                                            if(fileExistsWithCache('/hdd1/clashapp/data/misc/lanes/UNKNOWN.webp')){
                                                echo '<img id="mainrole-'.$currentPlayerNumber2.'" class="saturate-0 brightness-100" src="/clashapp/data/misc/lanes/UNKNOWN.webp?version='.md5_file('/hdd1/clashapp/data/misc/lanes/UNKNOWN.webp').'" width="32" height="32" alt="A league of legends lane icon corresponding to a players main position">';
                                                echo '<img id="secrole-'.$currentPlayerNumber2.'" class="saturate-0 brightness-100" src="/clashapp/data/misc/lanes/UNKNOWN.webp?version='.md5_file('/hdd1/clashapp/data/misc/lanes/UNKNOWN.webp').'" width="32" height="32" alt="A league of legends lane icon corresponding to a players secondary position">';
                                            } echo "
                                            </div>
                                        </div>";
                                        if(!$execOnlyOnce) $startPrintAverageMatchscore = microtime(true);
                                        $memPrintAverageMatchscore = memory_get_usage(); echo "
                                        <div class='flex h-8 items-center justify-between'>
                                            <span class='cursor-help text-loading-light' onmouseenter='showTooltip(this, \"".__("Average Matchscore of all ranked & clash games (without remakes or <10min games)")."\", 500, \"top-right\", \"".__("adjScoreTooltipMargin")."\")' onmouseleave='hideTooltip(this)'>".__("Avg. Score").":</span>
                                            <div class='inline-flex w-[4.5rem] justify-center'>
                                                <span id='matchscore-".$currentPlayerNumber2."' class='transition-opacity duration-500 easy-in-out text-loading-light'>0.00</span>
                                            </div>
                                        </div>";
                                        if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintAverageMatchscore"]["Time"] = number_format((microtime(true) - $startPrintAverageMatchscore), 2, ',', '.')." s";
                                        if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintAverageMatchscore"]["Memory"] = number_format((memory_get_usage() - $memPrintAverageMatchscore)/1024, 2, ',', '.')." kB"; echo "
                                    </div>
                                </div>
                            </div>";

                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["ProfileIconBorders"]["Time"] = number_format((microtime(true) - $startProfileIconBorders), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["ProfileIconBorders"]["Memory"] = number_format((memory_get_usage() - $memProfileIconBorders)/1024, 2, ',', '.')." kB";

                            // ------------------------------------------------------------------v- PRINT RANKED STATS -v------------------------------------------------------------------ //

                            if(!$execOnlyOnce) $startPrintRankedStats = microtime(true);
                            $memPrintRankedStats = memory_get_usage();
                            echo "
                            <tr>
                                <td class='text-center h-32 min-h-[8rem]'> 
                                    <div id='rankcontent-".$currentPlayerNumber2."' class='inline-flex w-full justify-evenly'>
                                        <div class='flex items-center gap-2 rounded bg-[#0e0f18] p-2'><img src='/clashapp/data/misc/webp/unranked_emote.webp' style='filter: grayscale(100%)' width='64' height='64' loading='lazy' class='w-16' alt='A blitzcrank emote with a questionmark in case this player has no retrievable ranked data'><span class='min-w-[5.5rem] text-loading-light'>".__("Unranked")."</span></div>
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintRankedStats"]["Time"] = number_format((microtime(true) - $startPrintRankedStats), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintRankedStats"]["Memory"] = number_format((memory_get_usage() - $memPrintRankedStats)/1024, 2, ',', '.')." kB";
                            
                            // ----------------------------------------------------------------v- PRINT MASTERY DATA -v---------------------------------------------------------------- //
                            
                            if(!$execOnlyOnce) $startPrintMasteryData = microtime(true);
                            $memPrintMasteryData = memory_get_usage();
                            echo "
                            <tr>
                                <td class='text-center h-32'>
                                    <div id='masterycontent-".$currentPlayerNumber2."' class='masterycontentscroll inline-flex gap-8 slider-container overflow-hidden overflow-x-scroll whitespace-nowrap fullhd:max-w-[17rem] twok:max-w-[23rem] h-full w-full py-2 justify-center'>
                                        ";
                                        $maxScore = 300;
                                        for ($i=0; $i < 4; $i++) { 
                                            $randomChampPath = glob("/hdd1/clashapp/data/patch/{$currentPatch}/img/champion/*")[array_rand(glob("/hdd1/clashapp/data/patch/{$currentPatch}/img/champion/*"))];
                                            $randomScore = rand(30, $maxScore); echo "
                                            <div><div class='slider-item flex-none h-full whitespace-nowrap inline-block cursor-grab'>
                                                <img src='".str_replace('/hdd1', '', $randomChampPath)."?version=".md5_file("{$randomChampPath}")."' width='64' height='64' class='block relative z-0' style='filter: grayscale(100%)' alt='A champion icon of the league of legends champion ".pathinfo(basename($randomChampPath), PATHINFO_FILENAME)."'>
                                                <span class='max-w-[64px] text-ellipsis overflow-hidden whitespace-nowrap block text-loading-light'>".pathinfo(basename($randomChampPath), PATHINFO_FILENAME)."</span>
                                                <img src='/clashapp/data/misc/mastery-7.webp?version=".md5_file('/hdd1/clashapp/data/misc/mastery-7.webp')."' width='32' height='32' style='filter: grayscale(100%)' class='relative -top-[5.75rem] -right-11 z-10' alt='A mastery hover icon on top of the champion icon in case the player has achieved level 5 or higher'><div class='-mt-7 text-loading-light'>{$randomScore}k</div>
                                            </div></div>";
                                            $maxScore = $randomScore;
                                        } echo "
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintMasteryData"]["Time"] = number_format((microtime(true) - $startPrintMasteryData), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintMasteryData"]["Memory"] = number_format((memory_get_usage() - $memPrintMasteryData)/1024, 2, ',', '.')." kB";
                            
                            // -------------------------------------------------------------------v- PRINT TAGS  -v------------------------------------------------------------------- //

                            if(!$execOnlyOnce) $startPrintTags = microtime(true);
                            $memPrintTags = memory_get_usage();
                            echo "
                            <tr class='mt-2'>
                                <td>
                                    <div id='taglist-".$currentPlayerNumber2."' class='max-h-[5.7rem] overflow-hidden mt-4 mb-2 flex flex-wrap px-4 gap-2 min-h-[6rem] justify-center'>
                                        <div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] bg-loading cursor-help'>".__('Loading')."</div>
                                        <div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] bg-loading cursor-help'>".__('Player')."</div>
                                        <div class='playerTag list-none border border-solid border-[#141624] py-2 px-3 rounded h-fit text-[#cccccc] bg-loading cursor-help'>".__('Tags...')."</div>
                                    </div>
                                </td>
                            </tr>";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintTags"]["Time"] = number_format((microtime(true) - $startPrintTags), 2, ',', '.')." s";
                            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintTags"]["Memory"] = number_format((memory_get_usage() - $memPrintTags)/1024, 2, ',', '.')." kB";

                            // ---------------------------------------------------v- SAVE DATA FOR MATCH HISTORY AND END TOP PART PLAYER  -v---------------------------------------------------- //

                            $tempStoreArray[$key]["matchids_sliced"] = $matchids_sliced;
                            $tempStoreArray[$key]["puuid"] = $puuid;
                            $tempStoreArray[$key]["sumid"] = $sumid;
                            $tempStoreArray[$key]["matchRankingArray"] = $playerDataJSON["MatchIDs"];
                            $currentPlayerNumber2++;

                            echo "
                        </tbody>
                        </table>
                    </td>";
                    foreach($matchids as $matchid){
                        if(!in_array($matchid, $matchIDTeamArray)){
                            $matchIDTeamArray[] = $matchid;
                        }
                    }
                    $execOnlyOnce = false;
                    $timeAndMemoryArray["Player"][$player["summonerId"]]["TotalPlayer"]["Time"] = number_format((microtime(true) - $startFetchPlayer[$key]), 2, ',', '.')." s";
                    $timeAndMemoryArray["Player"][$player["summonerId"]]["TotalPlayer"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayer[$key])/1024, 2, ',', '.')." kB";
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



                $test = getMatchData(array_keys($playerDataJSON["MatchIDs"]));

                // -----------------------------------------------------------------------------v- MIDDLE AD BANNER  -v----------------------------------------------------------------------- //

                echo "
                </tr>
                <tr class='trenner h-32 bg-darker rounded'>
                    <td colspan='5'>
                        <div class='flex items-center justify-start gap-4 w-full' style='content-visibility: auto;'>
                            <div class='rounded bg-[#141624] p-4'>
                                <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                                    "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                                    <span class='h-full flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                                    } else { echo "
                                    <div class='lazyhtml' data-lazyhtml onvisible>
                                        <script type='text/lazyhtml'>
                                        <!--
                                        <ins class='adsbygoogle'
                                                style='display:block'
                                                data-ad-client='ca-pub-8928684248089281'
                                                data-ad-slot='5730429745'
                                                data-ad-format='horizontal'
                                                data-full-width-responsive='true'></ins>
                                        <script>
                                                (adsbygoogle = window.adsbygoogle || []).push({});
                                        </script>
                                        -->
                                        </script>
                                    </div>
                                    "; } echo "
                                </div>
                            </div>
                            <div class='rounded bg-[#141624] p-4'>
                                <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                                    "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                                    <span class='h-full flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                                    } else { echo "
                                    <div class='lazyhtml' data-lazyhtml onvisible>
                                        <script type='text/lazyhtml'>
                                        <!--
                                        <ins class='adsbygoogle'
                                                style='display:block'
                                                data-ad-client='ca-pub-8928684248089281'
                                                data-ad-slot='3743394805'
                                                data-ad-format='horizontal'
                                                data-full-width-responsive='true'></ins>
                                        <script>
                                                (adsbygoogle = window.adsbygoogle || []).push({});
                                        </script>
                                        -->
                                        </script>
                                    </div>
                                    "; } echo "
                                </div>
                            </div>
                            <div class='grid rounded bg-[#141624] h-[122px] p-4 w-full text-center min-w-max items-center'>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-pointer accent-[#27358b]' name='tagOptions' id='tagOptions' 
                                @change='document.getElementById(\"tagOptions\").checked ? setCookie(\"tagOptions\", \"multi-colored\") : deleteCookie(\"tagOptions\");
                                updateTagColor(this)'"; 
                                if(isset($_COOKIE["tagOptions"])){ if($_COOKIE["tagOptions"] == "multi-colored"){ echo "checked"; }} echo " ></input><label for='tagOptions'> ".__("Multi-Colored Tags")."</label></div>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-pointer accent-[#27358b]' name='expand-all-matches' id='expand-all-matches' @change='document.getElementById(\"expand-all-matches\").checked ? advancedGlobal = true : advancedGlobal = false'></input><label for='expand-all-matches'> ".__("Expand all matches")."</label></div>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-not-allowed accent-[#27358b]' name='additional-setting2' id='additional-setting2' disabled></input><label for='additional-setting2'> ".__("Additional setting")."</label></div>
                                <div class='cursor-default h-fit'><input type='checkbox' class='cursor-not-allowed accent-[#27358b]' name='additional-setting3' id='additional-setting3' disabled></input><label for='additional-setting3'> ".__("Additional setting")."</label></div>
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
            if(!$execOnlyOnce) $startPrintMatchHistory = microtime(true);
            foreach($tempStoreArray as $key => $player){ 
                $memPrintMatchHistory = memory_get_usage(); 
                echo "
                <td class='align-top w-1/5 opacity-0' style='animation: .5s ease-in-out 0s 1 fadeIn; animation-fill-mode: forwards;'>
                    <table class='rounded-b bg-[#141624] w-full'>
                        <tr>
                            <td x-data='{ open: true }' x-init='setTimeout(() => open = true, ".$matchAlpineCounter.")' class='single-player-match-history' data-puuid='".$player["puuid"]."' data-sumid='".$player["sumid"]."'>";
                                if($upToDate){
                                    if(!$execOnlyOnce) $startPrintMatchHistoryFunction = microtime(true);
                                    $memPrintMatchHistoryFunction = memory_get_usage(); 
                                    echo printTeamMatchDetailsByPUUID($player["matchids_sliced"], $player["puuid"], $player["matchRankingArray"]);
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintMatchHistoryFunction"]["Time"] = number_format((microtime(true) - $startPrintMatchHistoryFunction), 2, ',', '.')." s";
                                    if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintMatchHistoryFunction"]["Memory"] = number_format((memory_get_usage() - $memPrintMatchHistoryFunction)/1024, 2, ',', '.')." kB";
                                }
                                echo "
                            </td>
                        </tr>
                    </table>
                </td>";
                $matchAlpineCounter += 50;
            } echo "
            </tr>
            </table>";
            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintMatchHistory"]["Time"] = number_format((microtime(true) - $startPrintMatchHistory), 2, ',', '.')." s";
            if(!$execOnlyOnce) $timeAndMemoryArray["Player"][$player["summonerId"]]["PrintMatchHistory"]["Memory"] = number_format((memory_get_usage() - $memPrintMatchHistory)/1024, 2, ',', '.')." kB";
        $timeAndMemoryArray["FetchPlayerTotal"]["Time"] = number_format((microtime(true) - $startFetchPlayerTotal), 2, ',', '.')." s";
        $timeAndMemoryArray["FetchPlayerTotal"]["Memory"] = number_format((memory_get_usage() - $memFetchPlayerTotal)/1024, 2, ',', '.')." kB";

    // -------------------------------------------------------------------------------v- CALCULATE & PRINT SUGGESTED BAN DATA  -v------------------------------------------------------------------------------- //

    // $recalculateSuggestedBanData = true; // uncomment to force recalc
    // Check if suggested ban data is already locally stored
    $currentTeamJSON = $mdb->findDocumentByField('teams', 'TeamID', $teamID, true)["document"];
    if(isset($currentTeamJSON["SuggestedBanData"])){
        $suggestedBanArray = $currentTeamJSON["SuggestedBanData"];
        $timer = 0;
        $zIndex = 10;
        foreach($suggestedBanArray as $champname => $banChampion){
            echo '<div class="suggested-ban-champion inline-block text-center w-16 h-16 opacity-0 relative" style="animation: .5s ease-in-out '.$timer.'s 1 fadeIn; animation-fill-mode: forwards; z-index: '.$zIndex.';" x-data="{ showExplanation: false }">
            <div class="ban-hoverer inline-grid" onclick="addToFile(this.parentElement);" @mouseover="showExplanation=true" @mouseout="showExplanation=false">
                <img class="cursor-help fullhd:w-12 twok:w-14" width="56" height="56" data-id="' . $banChampion->Filename . '" src="/clashapp/data/patch/' . $currentPatch . '/img/champion/' . str_replace(' ', '', $banChampion->Filename) . '.webp?version='.md5_file('/hdd1/clashapp/data/patch/' . $currentPatch . '/img/champion/' . str_replace(' ', '', $banChampion->Filename) . '.webp').'" alt="A league of legends champion icon of ' . $champname . '"></div>
            <span class="suggested-ban-caption w-16 block">' . $champname . '</span>
            <div class="grid grid-cols-[35%_15%_auto] w-[27rem] bg-black/90 text-white text-center text-xs rounded-lg py-2 absolute ml-16 -mt-[5.5rem] px-3" x-show="showExplanation" x-transition x-transition:enter.delay.500ms x-cloak @mouseenter="showExplanation = true" @mouseleave="showExplanation = false">
            <div class="py-3 px-2 flex justify-end items-center font-bold border-b-2 border-r-2 border-solid border-dark text-end">'.__('Category').'</div><div class="py-3 px-2 flex justify-center items-center font-bold border-b-2 border-r-2 border-solid border-dark">'.__('Addition').'</div><div class="py-3 px-2 flex justify-start text-left font-bold border-b-2 border-solid border-dark">'.__('Explanation').'</div>';
            if (isset($suggestedBanArray->{$champname}->Points->Value)) {
                echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Highest Mastery').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->Points->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">' . $playerSumidTeamArray[$suggestedBanArray->{$champname}->Points->Cause] . ' ' . __('achieved a mastery score of') . ' ' . $suggestedBanArray->{$champname}->Points->Value . ' ' . __('on') . ' ' . $champname . '.</div>';
            }
            if (isset($suggestedBanArray->{$champname}->TotalTeamPoints->Value)) {
                echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Total Team Mastery').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->TotalTeamPoints->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.__('This team has a combined mastery score of').' '.str_replace(".", ",", $suggestedBanArray->{$champname}->TotalTeamPoints->Value).' '.__('on').' '.$champname.'.</div>';
            }
            if (isset($suggestedBanArray->{$champname}->CapablePlayers->Value)) {
                if ($suggestedBanArray->{$champname}->CapablePlayers->Value > 1) {
                    echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Capable Player').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->CapablePlayers->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">' . $suggestedBanArray->{$champname}->CapablePlayers->Value . ' ' . __('summoners of this team are able to play') . ' ' . $champname . '.</div>';
                } else {
                    echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Capable Player').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->CapablePlayers->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">' . $suggestedBanArray->{$champname}->CapablePlayers->Value . ' ' . __('summoner of this team is able to play') . ' ' . $champname . '.</div>';
                }
            }
            if (isset($suggestedBanArray->{$champname}->MatchingLanersPrio->Cause)) {
                echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Matching Laners').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->MatchingLanersPrio->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">';
                foreach ($suggestedBanArray->{$champname}->MatchingLanersPrio->Cause as $laner) {
                    if ($laner == reset($suggestedBanArray->{$champname}->MatchingLanersPrio->Cause)) {
                        echo $playerSumidTeamArray[$laner];
                    } else if ($laner == end($suggestedBanArray->{$champname}->MatchingLanersPrio->Cause)) {
                        echo " & " . $playerSumidTeamArray[$laner];
                    } else {
                        echo ", " . $playerSumidTeamArray[$laner];
                    }
                }
                echo ' '.__('are able to perform with').' '.$champname.' '.__('while matching lanes').' (';
                foreach ($suggestedBanArray->{$champname}->MatchingLanersPrio->Lanes as $lane) {
                    if ($lane == reset($suggestedBanArray->{$champname}->MatchingLanersPrio->Lanes)) {
                        echo ucfirst(strtolower($lane));
                    } else if ($lane == end($suggestedBanArray->{$champname}->MatchingLanersPrio->Lanes)) {
                        echo " & " . ucfirst(strtolower($lane));
                    } else {
                        echo ", " . ucfirst(strtolower($lane));
                    }
                }
                echo ').</div>  ';
            }
            echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark text-end">'.__('Last Played').':</div>
            <div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->LastPlayed->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.__('The last time someone played').' '.$champname.' '.__('was').' '.timeDiffToText($suggestedBanArray->{$champname}->LastPlayed->Value).'.</div>';
            if (isset($suggestedBanArray->{$champname}->OccurencesInLastGames->Count)) {
                echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-b-2 border-dark">'.__('Occurences').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-b-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->OccurencesInLastGames->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left border-dashed border-b-2 border-dark">'.$champname.' '.__('was played').' ';
                echo $suggestedBanArray->{$champname}->OccurencesInLastGames->Count > 1 ? $suggestedBanArray->{$champname}->OccurencesInLastGames->Count.' '.__('times').' ' : ' '.__('once').' ';
                echo __('in the teams').' '.$suggestedBanArray->{$champname}->OccurencesInLastGames->Games.' '.__('unique fetched Ranked or Clash games').'.</div>';
            } 
            if (isset($suggestedBanArray->{$champname}->AverageMatchScore->Add)) {
                echo '<div class="py-3 px-2 flex justify-end items-center font-bold border-dashed border-r-2 border-dark text-end">'.__('Average Matchscore').':</div><div class="py-3 px-2 flex justify-center items-center border-dashed border-r-2 border-dark">+ ' . number_format($suggestedBanArray->{$champname}->AverageMatchScore->Add, 2, '.', '') . '</div><div class="py-3 px-2 flex justify-center text-left">'.__('The average matchscore achieved on').' '.$champname.' '.__('is').' '.$suggestedBanArray->{$champname}->AverageMatchScore->Value.'.</div>';
            }
            echo '
            <div class="py-3 px-2 flex justify-end items-center font-bold border-solid border-r-2 border-t-2 border-dark text-end">'.__('Finalscore').':</div><div class="py-3 px-2 flex justify-center items-center underline decoration-double font-bold border-solid border-r-2 border-t-2 border-dark text-base underline-offset-2">'.number_format($suggestedBanArray->{$champname}->FinalScore, 2, '.', '').'</div><div class="flex justify-end items-end text-gray-600 border-solid border-t-2 border-dark"><a href="/graphs-and-formulas">&#187; '.__('Graphs & Formulas').'</a></div>
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
                "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                <span class='h-full flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                } else { echo "
                <div class='lazyhtml' data-lazyhtml onvisible>
                    <script type='text/lazyhtml'>
                    <!--
                    <ins class='adsbygoogle'
                        style='display:block'
                        data-ad-client='ca-pub-8928684248089281'
                        data-ad-slot='6341637981'
                        data-ad-format='horizontal'
                        data-full-width-responsive='true'></ins>
                    <script>
                        (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                    -->
                    </script>
                </div>
                "; } echo "
            </div>
        </div>
        <div class='rounded bg-[#141624] p-4'>
            <div class='twok:w-[970px] fullhd:w-[728px] h-[90px] bg-black'>
                "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                <span class='h-full flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                } else { echo "
                <div class='lazyhtml' data-lazyhtml onvisible>
                    <script type='text/lazyhtml'>
                    <!--
                    <ins class='adsbygoogle'
                            style='display:block'
                            data-ad-client='ca-pub-8928684248089281'
                            data-ad-slot='8776229638'
                            data-ad-format='horizontal'
                            data-full-width-responsive='true'></ins>
                    <script>
                            (adsbygoogle = window.adsbygoogle || []).push({});
                    </script>
                    -->
                    </script>
                </div>
                "; } echo "
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

echo "<script>var timeAndMemoryArray = ".json_encode($timeAndMemoryArray).";
      timeAndMemoryArray['CachedMatches'] = cached;
      console.log(timeAndMemoryArray);</script>";
$apiRequests["total"] = array_sum($apiRequests);
echo "<script>console.log('API Request Array:', ".json_encode($apiRequests).")</script>";
?>