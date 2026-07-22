<?php 

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Profile', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');
?>

<body class="bg-darker">
<?php
/**
 *
 * @author Florian Falk <dasnerdwork@gmail.com>
 * @author Pascal Gnadt <p.gnadt@gmx.de>
 * @copyright Copyright (c) date("Y"), Florian Falk
 *
 */

// $startWhole = microtime(true);
$ladezeiten = array();
$emoteSources = array("/clashapp/data/misc/webp/ok.avif?version=".md5_file("/hdd1/clashapp/data/misc/webp/ok.avif"),"/clashapp/data/misc/webp/teemo.avif?version=".md5_file("/hdd1/clashapp/data/misc/webp/teemo.avif"),"/clashapp/data/misc/webp/priceless.avif?version=".md5_file("/hdd1/clashapp/data/misc/webp/priceless.avif"));
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

include_once('/hdd1/clashapp/src/functions.php');
include_once('/hdd1/clashapp/src/update.php');

if (isset($_GET["name"])){
    // Format text field input to swap spaces with '+' for correct api requests
    $formattedInput = preg_replace('/\s+/', '+', $_GET["name"]);
    
// $startFetchPlayerData = microtime(true);
$error_message = array();
$success_message = array();

$riotIdArray = explode('/', $formattedInput);
$playerName = $riotIdArray[0];
$playerTag = $riotIdArray[1] ?? 'euw';
$existsInDB = false;
$forceReload = false;

echo generateSinglePlayerData($playerName, $playerTag, $forceReload);

if (!empty($success_message)) { 
    foreach($success_message as $su){
        if($su != ""){
            echo '<div class="bg-[#00ff0040] -mb-12 text-base text-center leading-[3rem]">
                    <strong>'. $su .'</strong>
                  </div>';
        }
    }
} else if (!empty($error_message)) { 
    foreach($error_message as $er){
        if($er != ""){
            echo '<div class="bg-[#ff000040] -mb-12 text-base text-center leading-[3rem]">
                <strong>'. $er .'</strong>
            </div>';
        }
    }
}

$randomIconPath = glob("/hdd1/clashapp/data/patch/{$currentPatch}/img/profileicon/*.avif")[array_rand(glob("/hdd1/clashapp/data/patch/{$currentPatch}/img/profileicon/*.avif"))];
echo "
<div class='h-[26rem] flex justify-center items-center mx-4 mt-4 upper-banner-part bg-dark rounded'>
    <div class='w-[calc(100%-696px)] grid grid-cols-5 gap-4 h-[calc(100%-2rem)]'>
        <div class='relative flex justify-center pt-32 border-darker/25 border-r-4 border-dashed h-full'>
            <img id='profileicon' src='".str_replace('/hdd1', '', $randomIconPath)."?version=".md5_file($randomIconPath)."' width='84' height='84' style='filter: grayscale(100%)' class='rounded-full mt-6 z-0 max-h-[84px] max-w-[84px] pointer-events-none select-none' alt='The custom profile icon of a player'>
            <div class='playerlevel text-loading-light absolute mt-[6.8rem] text-xs z-[9]'>30</div>
            <img src='/clashapp/data/misc/levels/prestige_crest_lvl_030.avif?version=".md5_file("/hdd1/clashapp/data/misc/levels/prestige_crest_lvl_030.avif")."' width='190' height='190' style='filter: grayscale(100%)' class='profileborder-030 absolute -mt-[2.05rem] z-[8] pointer-events-none select-none' style='-webkit-mask-image: radial-gradient(circle at center, white 50%, transparent 70%); mask-image: radial-gradient(circle at center, white 50%, transparent 70%);' alt='The profile border corresponding to a players level'>
            <div class='absolute mt-[8.75rem] z-[9]'>
                <span id='playername' class='text-loading-light'>".__("Player")." </span>
                <span id='playertag' class='z-[9] bg-loading px-1 rounded ml-1 text-sm text-gray-300'>#EUW</span>
            </div>
        </div>
        <div class='border-darker/25 border-r-4 border-dashed h-full flex justify-center items-end'>Placeolder Solo/Duo</div>
        <div class='border-darker/25 border-r-4 border-dashed h-full flex justify-center items-end'>Placeolder Flex</div>
        <div class='border-darker/25 border-r-4 border-dashed h-full flex justify-center items-end'>Placeolder Mastery</div>
        <div class='h-full flex justify-center items-end'>Placeholder Settings</div>
    </div>
</div>

<table class='w-full table table-fixed border-separate border-spacing-4'>
    <tr id='match-history' x-data='{ advancedGlobal: true }'>
        <td class='w-[332px] min-w-[316px] align-top'>
            <div class='row-span-2 p-4 flex items-center justify-center rounded bg-[#141624]'>
                <div class='h-[37.5rem] min-w-[300px] bg-black'>
                    "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                    <span class='h-[37.5rem] flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                    } else { echo "
                        <div class='lazyhtml' data-lazyhtml onvisible>
                            <script type='text/lazyhtml'>
                            <!--
                            <ins class='adsbygoogle'
                                    style='display:block;height:600px;width:100%'
                                    data-ad-client='ca-pub-8928684248089281'
                                    data-ad-slot='5772151527'
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
        </td>
        <td class='w-auto align-top'>
            <div class='bg-dark rounded p-4 h-[40rem]'>
            </div>
        </td>
        <td class='align-top w-auto opacity-0 bg-dark p-4' style='animation: .5s ease-in-out 0s 1 fadeIn; animation-fill-mode: forwards;'>
            <table class='rounded-b bg-[#141624] w-full'>
                <tr id='matchhistory'>
                </tr>
            </table>
        </td>
        <td class='w-auto align-top'>
            <div class='bg-dark rounded p-4 h-[40rem]'>
            </div>
        </td>
        <td class='w-[332px] min-w-[316px] align-top'>
            <div class='row-span-2 p-4 flex items-center justify-center rounded bg-[#141624]'>
                <div class='h-[37.5rem] min-w-[300px] bg-black'>
                    "; if (isset($_SESSION['user']['email']) && $db->getPremium($_SESSION['user']['email'])) { echo "
                    <span class='h-[37.5rem] flex items-center justify-center'><img src='".$emoteSources[rand(0,count($emoteSources)-1)]."' class='max-h-full max-w-[50%]' alt='A random premium emote'></span>"; 
                    } else { echo "
                        <div class='lazyhtml' data-lazyhtml onvisible>
                            <script type='text/lazyhtml'>
                            <!--
                            <ins class='adsbygoogle'
                                    style='display:block;height:600px;width:100%'
                                    data-ad-client='ca-pub-8928684248089281'
                                    data-ad-slot='7994990182'
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
        </td>
    </tr>
</table>";
}

include('/hdd1/clashapp/templates/footer.php');
?>