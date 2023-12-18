<?php 
if (session_status() === PHP_SESSION_NONE) session_start(); 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include('/hdd1/clashapp/templates/head.php');
setCodeHeader('404', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');
?>
<div class="flex flex-col items-center justify-center w-full min-h-[calc(100vh_-_150px)]">
    <img src="/clashapp/data/misc/webp/empty_search.avif?version= <?= md5_file("/hdd1/clashapp/data/misc/webp/empty_search.avif") ?>" class="w-64 pb-8 -mt-8" alt="A frog emoji with a questionmark">
    <h1 class="text-2xl pb-2 font-bold"><?= __("Whoops, we couldn't find what you are looking for.") ?></h1>
    <span class="text-base text-silver"><?= __("Hint: Teamsearches only work for players in active clash teams.") ?></span>
    <div id="logout-button">
        <button type="button" class="h-12 w-64 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc]" onclick="history.back()">
            <span class="text-xl"><?= __("&#8592; Return to previous page") ?></span>
        </button>
    </div>
</div>
<?php
include('/hdd1/clashapp/templates/footer.php');
?>