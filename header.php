<div class="wrapper">
<header>
	<div class="header-nav-menu">
		<div class="clickable-logo">
			<a href="/" class="nav-menu-link">
				<img src="/clashapp/data/misc/svg/logo.svg" width="150" alt="CLASH" height="48">
			</a>
		</div>
		<nav>
			<ul>
				<li>
                    <a href="https://clash.dasnerdwork.net/profile/" class="nav-menu-link">
						<span>Profile</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/stats/" class="nav-menu-link">
						<span>Stats</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/docs/" class="nav-menu-link">
						<span>Docs</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/counters/" class="nav-menu-link">
						<span>Counters</span>
					</a>
				</li>
				<li>
					<a href="https://clash.dasnerdwork.net/tft/" class="nav-menu-link">
						<span>TFT</span>
					</a>
				</li>
			</ul>
		</nav>
        <form id="suchfeld" action="" onsubmit="return false;" method="GET" autocomplete="off" style="display: flex;">
            <input type="text" name="name" id="name" value="" placeholder="Beschwörername">
            <input type="submit" name="submitBtn" id="submitBtn" value="Suchen" onclick="sanitize(this.form.name.value);">
            <button type="button" id="updateBtn" onclick="showLoader();" style="display: none;" disabled>Aktualisieren</button>
            <div class="sbl-circ" id="loader"></div>
        </form>
        <div class="misc-button-menu">
            <?php if(isset($_SESSION['user']['sumid'])){ 
            $profilePlayerData = getPlayerData('sumid', $_SESSION['user']['sumid']);
            ?>
            <div class="profile-button">
                <?php echo '<a href="https://clash.dasnerdwork.net/profile/'.$profilePlayerData["Name"].'" class="profile-link">';
                      echo '<img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$profilePlayerData["Icon"].'.png" style="vertical-align:middle; margin-right: 10px; text-decoration: none !important; " width="32" loading="lazy">';
                      echo '<span class="profile-link-text">'.$profilePlayerData['Name'].'</span></a>'; ?>
            </div>
            <?php } else if(isset($_SESSION['user']['username'])){ ?>
            <div class="profile-button">
                <a href="https://clash.dasnerdwork.net/settings" class="profile-link">
                    <img src="/clashapp/data/misc/profile-icon.png" style="vertical-align:middle; margin-right: 10px; text-decoration: none !important; " width="32" loading="lazy">
                    <?php echo '<span class="profile-link-text">'.$_SESSION['user']['username'].'</span></a>'; ?> 
            </div>
            <?php } ?>
            <div>
                <button type="button" class="select-language-button misc-button">
                    <span id="language-selector">Deutsch</span>
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
                    <img src="/clashapp/data/misc/settings-wheel.png" width="20" height="20"></img>
                </a>
            </div>
        </div>
    </div>
</header>
