<?php
require_once '/hdd1/clashapp/mongo-db.php';
$currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");
$mdb = new MongoDBHelper();
// include_once('/hdd1/clashapp/lang/translate.php');
?>
<!DOCTYPE html>
<div class="min-h-[calc(100vh_-_90px)]">
<header class="bg-dark">
	<div class="flex h-16">
		<div class="w-44 -mr-2.5 float-left p-2">
			<a href="/" class="block no-underline text-white align-middle w-full">
				<img src="/clashapp/data/misc/webp/logo.webp" alt="The main logo of the website" width="160" height="44">
			</a>
		</div>
		<nav class="m-2.5 p-1">
			<ul class="flex">
                <li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
                    <a target="_blank" rel="noopener" href="https://discord.gg/GghR7geCFg"> 
						<span class="text-xl leading-4 active:text-[#ccc]"><?=__('News')?></span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clashscout.com/minigames">
						<span class="text-xl leading-4 active:text-[#ccc]"><?=__('Minigames')?></span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clashscout.com/graphs-and-formulas">
						<span class="text-xl leading-4 active:text-[#ccc]"><?=__('Graphs & Formulas')?></span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clashscout.com/team/test">
						<span class="text-xl leading-4 active:text-[#ccc]"><?=__('Example')?></span>
					</a>
				</li>
			</ul>
		</nav>
        <?php if($_SERVER['REQUEST_URI'] == "/"){ echo '
            <form class="h-10 w-[800px] flex absolute left-2/4 -translate-x-2/4 -translate-y-2/4 top-[60%]" action="" onsubmit="return false;" method="GET" autocomplete="off">
                <input type="text" name="name" class="h-16 w-full py-2.5 pl-2.5 pr-16 text-xl border-none text-black font-normal rounded-l-full indent-5 outline-none focus:pl-2.5 focus:text-xl" value="" placeholder='.__("'Search Teams, Players or Champions'").'>
                <input type="submit" name="submitBtn" class=\'h-16 w-20 py-2.5 pl-2.5 pr-16 text-xl border-none bg-white text-black cursor-pointer rounded-r-full bg-[length:50%] bg-[url("/clashapp/data/misc/webp/searchicon.webp")] bg-no-repeat bg-center focus:text-xl active:bg-[#ccc]\' value="" onclick="sanitize(this.form.name.value);">
                <div class="w-10 h-10 items-center justify-center flex absolute -right-10 opacity-0" id="main-search-loading-spinner">
                <div class="border-4 border-solid border-t-transparent animate-spin rounded-2xl h-6 w-6" id="loader"></div>
                </div>
            </form>';
        } else { echo '
            <div class="flex absolute left-2/4 -translate-x-2/4 translate-y-1/4 flex-col z-50 bg-black">
                <form class="h-10 flex" action="" onsubmit="return false;" method="GET" autocomplete="off">
                    <div class="relative">
                        <div id="tagLineSuggest" class="absolute -z-10 px-2.5 text-base w-full h-full bg-white flex items-center"></div>
                        <input id="main-input" type="text" name="name" class="bg-transparent px-2.5 text-base w-80 h-full !border-r border-solid border-gray-200 focus:!border-r focus:!border-solid focus:!border-gray-200 text-black focus:px-2.5 focus:text-base" value="" placeholder='.__("'Search Teams, Players or Champions'").' autocomplete="off" x-on:focus="autosuggest = true" x-on:focusout="autosuggest = false" maxlength="22">
                    </div>
                    <input type="submit" name="submitBtn" class="w-20 text-base bg-white text-black cursor-pointer focus:text-base active:bg-[#ccc]" value="'.__('Search').'" onclick="sanitize(this.form.name.value);">
                    <div class="w-10 h-10 items-center justify-center flex absolute -right-10 opacity-0" id="main-search-loading-spinner">
                    <div class="border-4 border-solid border-t-transparent animate-spin rounded-2xl h-6 w-6" id="loader"></div>
                    </div>
                </form>
                <div id="autosuggest-container" class="absolute w-[25rem] mt-12 text-whiteblue hidden">
                </div>
            </div>';
        } 
        // TODO: Design and find usage for update button and/or remove: <button type="button" id="updateBtn" class="w-20 text-base bg-white text-black hidden cursor-pointer focus:text-base active:bg-[#ccc]" onclick="showLoader();" disabled>Update</button>
        ?>
        <div class="absolute right-0 flex h-16">
            <?php if(isset($_SESSION['user']['puuid'])){ // If there is currently a user logged in && the user has a connected league account
                if($mdb->getPlayerByPUUID($_SESSION['user']['puuid'])["success"]){
                $headerJsonString = json_encode($mdb->getPlayerByPUUID($_SESSION['user']['puuid'])["data"]);
                $headerJson = json_decode($headerJsonString, true);
                }
                $dataName = isset($_SESSION['user']['username']) ? $_SESSION['user']['username'] : '';
            ?>
            <div class="flex justify-center items-center px-4 mt-[3px]">
                <?php echo '<a class="group" href="https://clashscout.com/profile/'.strtolower($headerJson["PlayerData"]["GameName"]).'/'.strtolower($headerJson["PlayerData"]["Tag"]).'">';
                      echo '<img width="32" height="32" src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$headerJson["PlayerData"]["Icon"].'.webp" class="align-middle mr-2.5 no-underline inline-flex" alt="A custom profile icon of a player">';
                      echo '<p id="highlighter" data-username="'.$dataName.'" class="inline decoration-2 group-hover:text-[#fff] group-hover:underline group-hover:text-[#fff]" style="text-decoration-skip-ink: none;"><span class="text-white">'.$headerJson["PlayerData"]["GameName"].'</span></p><span class="bg-searchtitle px-1 rounded ml-1 text-sm decoration-2 group-hover:text-[#fff] group-hover:text-[#fff] text-[#9ea4bd]">#'.$headerJson["PlayerData"]["Tag"].'</span></a>'; ?>
            </div>
            <?php } else if(isset($_SESSION['user']['username'])){ ?>
            <div class="flex justify-center items-center px-4 mt-[3px]">
                <a href="https://clashscout.com/settings">
                    <img width="32" height="32" src="/clashapp/data/misc/profile-icon.webp" class="align-middle mr-2.5 no-underline inline-flex" alt="The sandard profile icon if no league of legends account is connected">
                    <?php echo '<span id="highlighter" class="hover:text-[#fff] hover:underline decoration-2 active:text-[#ddd]" style="text-decoration-skip-ink: none;">'.$_SESSION['user']['username'].'</span></a>'; ?> 
            </div>
            <?php } ?>
            <div class="w-40 bg-black/75 text-white text-center text-xs rounded-lg py-2 absolute px-3 -ml-[116px] mt-[56px] transition-opacity hidden z-30" id="identityNotice">
                <?=__('This is your current identity and color for others. To customize it please')?> <a href='/login' class='underline'><?=__('login')?></a>.
                <svg class="absolute text-black h-4 w-full left-0 top-full -mt-24 rotate-180" x="0px" y="0px" viewBox="0 0 255 255" xml:space="preserve"><polygon class="fill-current" points="0,0 127.5,127.5 255,0"></polygon></svg>
            </div>
            <div id="highlighterAfter">
                <select id="language-selector" class="text-center h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc] focus:outline-none border-none" onchange="selectLang(this)">
                    <option value="en_US" <?= (!isset($_COOKIE["lang"]) || $_COOKIE["lang"] == "en_US") ? "selected disabled hidden" : ""; ?>>English</option>
                    <option value="de_DE" <?= (isset($_COOKIE["lang"]) && $_COOKIE["lang"] == "de_DE") ? "selected disabled hidden" : ""; ?>>Deutsch</option>
                </select>
            </div>
            <?php if(!isset($_SESSION['user'])){ ?>
            <div id="login-register-button">
                <a href="https://clashscout.com/login?location=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                    <button type="button" class="h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc]">
                        <span><?=__('Login');?></span>
                    </button>
                </a>
            </div>
            <?php } else { ?>
            <div id="logout-button">
                <a href="https://clashscout.com/logout?location=<?= urlencode($_SERVER['REQUEST_URI']) ?>">
                    <button type="button" class="h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc]">
                        <span><?=__('Logout');?></span>
                    </button>
                </a>
            </div>
            <?php } ?>
            <div id="settings-button" class="flex items-center ml-2 mr-4">
                <a href="/settings">
                    <img src="/clashapp/data/misc/settings-wheel.webp" width="20" height="20" alt="A settings wheel icon which looks like a gear" title="<?= __("Settings") ?>"></img>
                </a>
            </div>
        </div>
    </div>
</header>
