<?php
$currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");
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
                <input type="text" name="name" class="h-16 w-full py-2.5 pl-2.5 pr-16 text-xl border-none text-black font-normal rounded-l-full indent-5 outline-none focus:pl-2.5 focus:text-xl" value="" placeholder="Enter a Summoner Name">
                <input type="submit" name="submitBtn" class=\'h-16 w-20 py-2.5 pl-2.5 pr-16 text-xl border-none bg-white text-black cursor-pointer rounded-r-full bg-[length:50%] bg-[url("/clashapp/data/misc/webp/searchicon.webp")] bg-no-repeat bg-center focus:text-xl active:bg-[#ccc]\' value="" onclick="sanitize(this.form.name.value);">
                <div class="w-10 h-10 items-center justify-center flex absolute -right-10 opacity-0" id="main-search-loading-spinner">
                <div class="border-4 border-solid border-t-transparent animate-spin rounded-2xl h-6 w-6" id="loader"></div>
                </div>
            </form>';
        } else { echo '
            <form class="h-10 flex absolute left-2/4 -translate-x-2/4 translate-y-1/4" action="" onsubmit="return false;" method="GET" autocomplete="off">
                <input type="text" name="name" class="pl-2.5 text-base text-black focus:pl-2.5 focus:text-base" value="" placeholder='.__("Summonername").' autocomplete="off">
                <input type="submit" name="submitBtn" class="w-20 text-base bg-white text-black cursor-pointer focus:text-base active:bg-[#ccc]" value="'.__('Search').'" onclick="sanitize(this.form.name.value);">
                <div class="w-10 h-10 items-center justify-center flex absolute -right-10 opacity-0" id="main-search-loading-spinner">
                <div class="border-4 border-solid border-t-transparent animate-spin rounded-2xl h-6 w-6" id="loader"></div>
                </div>
            </form>';
        } 
        // TODO: Design and find usage for update button and/or remove: <button type="button" id="updateBtn" class="w-20 text-base bg-white text-black hidden cursor-pointer focus:text-base active:bg-[#ccc]" onclick="showLoader();" disabled>Update</button>
        ?>
        <div class="absolute right-0 flex h-16">
            <?php if(isset($_SESSION['user']['sumid'])){ // If there is currently a user logged in && the user has a connected league account
                if(file_exists('/var/www/html/clash/clashapp/data/player/'.$_SESSION['user']['sumid'].'.json')){
                $headerJson = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$_SESSION['user']['sumid'].'.json'), true);
                }
            ?>
            <div class="flex justify-center items-center px-4 mt-[3px]">
                <?php echo '<a href="https://clashscout.com/profile/'.strtolower($headerJson["PlayerData"]["Name"]).'">';
                      echo '<img width="32" height="32" src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$headerJson["PlayerData"]["Icon"].'.webp" class="align-middle mr-2.5 no-underline inline-flex" alt="A custom profile icon of a player">';
                      echo '<p id="highlighter" class="inline hover:text-[#fff] hover:underline decoration-2 active:text-[#ddd]" style="text-decoration-skip-ink: none;"><span class="text-white">'.$headerJson["PlayerData"]["Name"].'</span></p></a>'; ?>
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
                    <img src="/clashapp/data/misc/settings-wheel.webp" width="20" height="20" alt="A settings wheel icon which looks like a gear"></img>
                </a>
            </div>
        </div>
    </div>
</header>
