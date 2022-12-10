<?php
$currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");
?>
<div class="min-h-[calc(100vh_-_90px)]">
<header>
	<div class="flex h-16">
		<div class="w-44 -mr-2.5 float-left p-2">
			<a href="/" class="block no-underline text-white align-middle w-full">
				<img src="/clashapp/data/misc/webp/logo.webp" alt="CLASH" height="48">
			</a>
		</div>
		<nav class="m-2.5 p-1">
			<ul class="flex">
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
                    <a href="https://clash.dasnerdwork.net/profile">
						<span class="text-xl leading-4 active:text-[#ccc]">Profile</span>
					</a>
				</li>
                <li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
                    <a href="https://clash.dasnerdwork.net/patch-notes"> 
						<span class="text-xl leading-4 active:text-[#ccc]">Patchnotes</span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clash.dasnerdwork.net/stats" onclick="return false;">
						<span class="text-xl leading-4 active:text-[#ccc]">Stats</span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clash.dasnerdwork.net/docs" onclick="return false;">
						<span class="text-xl leading-4 active:text-[#ccc]">Docs</span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clash.dasnerdwork.net/counters" onclick="return false;">
						<span class="text-xl leading-4 active:text-[#ccc]">Counters</span>
					</a>
				</li>
				<li class="ml-1.5 mr-1.5 block p-2 no-underline text-white">
					<a href="https://clash.dasnerdwork.net/team/test">
						<span class="text-xl leading-4 active:text-[#ccc]">Test</span>
					</a>
				</li>
			</ul>
		</nav>
        <form id="search-bar" class="h-10 flex absolute left-2/4 -translate-x-2/4 translate-y-1/4" action="" onsubmit="return false;" method="GET" autocomplete="off">
            <input type="text" name="name" id="name" class="pl-2.5 text-base text-black focus:pl-2.5 focus:text-base" value="" placeholder="Summonername">
            <input type="submit" name="submitBtn" id="submitBtn" class="w-20 text-base bg-white text-black cursor-pointer focus:text-base active:bg-[#ccc]" value="Search" onclick="sanitize(this.form.name.value);">
            <button type="button" id="updateBtn" class="w-20 text-base bg-white text-black hidden cursor-pointer focus:text-base active:bg-[#ccc]" onclick="showLoader();" disabled>Update</button>
            <div class="w-10 h-10 items-center justify-center flex absolute -right-10 opacity-0" id="main-search-loading-spinner">
                <div class="border-4 border-solid border-t-transparent animate-spin rounded-2xl h-6 w-6" id="loader"></div>
            </div>
        </form>
        <div class="absolute right-0 flex h-16">
            <?php if(isset($_SESSION['user']['sumid'])){ // If there is currently a user logged in && the user has a connected league account
                if(file_exists('/var/www/html/clash/clashapp/data/player/'.$_SESSION['user']['sumid'].'.json')){
                $headerJson = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$_SESSION['user']['sumid'].'.json'), true);
                }
            ?>
            <div class="flex justify-center items-center px-4">
                <?php echo '<a href="https://clash.dasnerdwork.net/profile/'.strtolower($headerJson["PlayerData"]["Name"]).'">';
                      echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$headerJson["PlayerData"]["Icon"].'.webp" class="align-middle mr-2.5 no-underline inline-flex" width="32" loading="lazy">';
                      echo '<span class="hover:text-[#fff] hover:underline active:text-[#ddd]">'.$headerJson["PlayerData"]["Name"].'</span></a>'; ?>
            </div>
            <?php } else if(isset($_SESSION['user']['username'])){ ?>
            <div class="flex justify-center items-center px-4">
                <a href="https://clash.dasnerdwork.net/settings">
                    <img src="/clashapp/data/misc/profile-icon.webp" class="align-middle mr-2.5 no-underline inline-flex" width="32" loading="lazy">
                    <?php echo '<span class="hover:text-[#fff] hover:underline active:text-[#ddd]">'.$_SESSION['user']['username'].'</span></a>'; ?> 
            </div>
            <?php } ?>
            <div>
                <button type="button" class="after:content-['\2B9F'] h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc]">
                    <span id="language-selector">English</span>
                </button>
            </div>
            <?php if(!isset($_SESSION['user'])){ ?>
            <div id="login-register-button">
                <a href="https://clash.dasnerdwork.net/login">
                    <button type="button" class="h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc]">
                        <span>Login</span>
                    </button>
                </a>
            </div>
            <?php } else { ?>
            <div id="logout-button">
                <a href="https://clash.dasnerdwork.net/logout">
                    <button type="button" class="h-8 w-28 align-middle mr-2.5 ml-2.5 text-base translate-y-2/4 bg-[#eee] text-black active:bg-[#ccc]">
                        <span>Logout</span>
                    </button>
                </a>
            </div>
            <?php } ?>
            <div id="settings-button" class="flex items-center ml-2 mr-4">
                <a href="/settings">
                    <img src="/clashapp/data/misc/settings-wheel.webp" width="20" height="20"></img>
                </a>
            </div>
        </div>
    </div>
</header>
