<?php
$currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");
?>
<div class="wrapper">
<header>
	<div class="header-nav-menu">
		<div class="clickable-logo">
			<a href="/" class="nav-menu-link">
				<img src="/clashapp/data/misc/webp/logo.webp" width="150" alt="CLASH" height="48">
			</a>
		</div>
		<nav class="header-li">
			<ul>
				<li>
                    <a href="https://clash.dasnerdwork.net/profile" class="nav-menu-link">
						<span>Profile</span>
					</a>
				</li>
                <li>
                    <a href="https://clash.dasnerdwork.net/patch-notes" class="nav-menu-link">
						<span>Patch Notes</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/stats" class="nav-menu-link" onclick="return false;">
						<span>Stats</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/docs" class="nav-menu-link" onclick="return false;">
						<span>Docs</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/counters" class="nav-menu-link" onclick="return false;">
						<span>Counters</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/team/test" class="nav-menu-link">
						<span>Test</span>
					</a>
				</li>
			</ul>
		</nav>
        <form id="suchfeld" action="" onsubmit="return false;" method="GET" autocomplete="off" style="display: flex;">
            <input type="text" name="name" id="name" value="" placeholder="Summonername">
            <input type="submit" name="submitBtn" id="submitBtn" value="Search" onclick="sanitize(this.form.name.value);">
            <button type="button" id="updateBtn" onclick="showLoader();" style="display: none;" disabled>Update</button>
            <div class="sbl-circ" id="loader"></div>
        </form>
        <div class="misc-button-menu">
            <?php if(isset($_SESSION['user']['sumid'])){ 
                if(file_exists('/var/www/html/clash/clashapp/data/player/'.$_SESSION['user']['sumid'].'.json')){
                $headerJson = json_decode(file_get_contents('/var/www/html/clash/clashapp/data/player/'.$_SESSION['user']['sumid'].'.json'), true);
                }
            ?>
            <div class="profile-button">
                <?php echo '<a href="https://clash.dasnerdwork.net/profile/'.strtolower($headerJson["PlayerData"]["Name"]).'" class="profile-link">';
                      echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$headerJson["PlayerData"]["Icon"].'.webp" style="vertical-align:middle; margin-right: 10px; text-decoration: none !important; " width="32" loading="lazy">';
                      echo '<span class="profile-link-text">'.$headerJson["PlayerData"]["Name"].'</span></a>'; ?>
            </div>
            <?php } else if(isset($_SESSION['user']['username'])){ ?>
            <div class="profile-button">
                <a href="https://clash.dasnerdwork.net/settings" class="profile-link">
                    <img src="/clashapp/data/misc/profile-icon.webp" style="vertical-align:middle; margin-right: 10px; text-decoration: none !important; " width="32" loading="lazy">
                    <?php echo '<span class="profile-link-text">'.$_SESSION['user']['username'].'</span></a>'; ?> 
            </div>
            <?php } ?>
            <div>
                <button type="button" class="select-language-button misc-button">
                    <span id="language-selector">English</span>
                </button>
            </div>
            <?php if(!isset($_SESSION['user'])){ ?>
            <div class="login-register-button">
                <a href="https://clash.dasnerdwork.net/login">
                    <button type="button" class="misc-button">
                        <span>Login</span>
                    </button>
                </a>
            </div>
            <?php } else { ?>
            <div class="logout-button">
                <a href="https://clash.dasnerdwork.net/logout">
                    <button type="button" class="misc-button">
                        <span>Logout</span>
                    </button>
                </a>
            </div>
            <?php } ?>
            <div class="settings-button">
                <a href="/settings">
                    <img src="/clashapp/data/misc/settings-wheel.webp" width="20" height="20"></img>
                </a>
            </div>
        </div>
    </div>
</header>
