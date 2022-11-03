<?php
session_start();
 
if (!isset($_SESSION['user'])) {
    header('Location: login');
}

require_once 'clash-db.php';
require_once 'functions.php';

$db = new DB();
$account_status = $db->check_status($_SESSION['user']['id'], $_SESSION['user']['username']);
if($account_status['status'] == "error"){
    $error_message = $account_status['message'];
} else if($account_status['status'] == "success"){
    $success_message = $account_status['message'];
}
$currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");

if (isset($_POST['password'])) {
    $response = $db->delete_account($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['region'], $_SESSION['user']['email'], $_POST['password']);
    if ($response['status'] == 'success') {
        header('Location: logout');
    } else {
        $success_message = $response['message'];
    }
}

if (isset($_POST['current-password']) && isset($_POST['new-password']) && isset($_POST['confirm-new-password'])) {
    $uppercase = preg_match('@[A-Z]@', $_POST['new-password']);
    $lowercase = preg_match('@[a-z]@', $_POST['new-password']);
    $number    = preg_match('@[0-9]@', $_POST['new-password']);
    $specialChars = preg_match('@[^\w]@', $_POST['new-password']);
    if ($db->check_credentials($_SESSION['user']['email'], $_POST['current-password'])['status'] != 'success') {
        $error_message = 'Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.'; // TODO change to password reset mail instead of onclick open
    } else {
         if ($_POST["new-password"] !== $_POST["confirm-new-password"]) {
            $error_message = "The entered passwords do not match.";
         } else {
            if ($_POST["current-password"] == $_POST["new-password"]) {
                $error_message = "New password cannot be the same as old password.";
             } else {
                if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($_POST['new-password']) < 8 || strlen($_POST['new-password']) > 32) {
                    $error_message = "Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character."; 
                } else {
                    $options = [
                        'cost' => 11,
                    ];
                    $reset = $db->reset_password($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['email'], password_hash($_POST['new-password'], PASSWORD_BCRYPT, $options));
                    $success_message = $reset['message'];
                }
            }
        }
    }
}

if ($_GET['verify']){
    $sumid = $db->get_sumid($_SESSION['user']['username']);
    if($sumid['status'] == 'success') {
        $_SESSION['user']["sumid"] = $sumid['sumid'];
    }
    Header('Location: '.$_SERVER['PHP_SELF'].'?verified=true');
}

if ($_GET['verified'] == "true") {
    $success_message = "Accounts successfully linked!";
} else if ($_GET['verified'] == "false") {
    $success_message = "Successfully disconnected accounts.";
}

if (isset($_POST['dcpassword'])) {
    $response = $db->check_credentials($_SESSION['user']['username'], $_POST['dcpassword']);
    if ($response['status'] == 'success') {
        if($db->disconnect_account($_SESSION['user']['username'], $_SESSION['user']["sumid"])){
            unset($_SESSION['user']["sumid"]);
            Header('Location: '.$_SERVER['PHP_SELF'].'?verified=false');
        } else {
            $error_message = "Unable to locally disconnected accounts. Please reach out to an administrator.";
        }
    } else {
        $error_message = 'Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.'; // TODO change to password reset mail instead of onclick open
    }
}

include('head.php');
setCodeHeader('Settings', true, true);
include('header.php');
?>

<?php if (!empty($success_message)) { ?>
<div class="account_status">
    <strong><?php echo $success_message; ?></strong>
</div>
<?php } else if (!empty($error_message)) { ?>
<div class="error">
    <strong><?php echo $error_message; ?></strong>
</div>
<?php } ?>
<div class="outer-form">
    <div class="clash-form">
        <div class='clash-form-title'><?php echo 'Welcome, '. $_SESSION['user']['username']; ?></div>
        <div><input type="button" onclick="location.href='/logout';" value="Log Out"></button></div>
        <div id="reset-password-area">
            <button id="reset-password-button" onclick="resetPassword(true);">Reset Password</button>
            <div id="reset-password-description">
                <div class='clash-form-title'>Reset your password</div>
                <span class='descriptive-text'>The same rules apply as used to register, meaning that the password has to:</span>
                <ul class='password-conditions'>
                    <li>Include at least one lowercase letter</li>
                    <li>Include at least one uppercase letter</li>
                    <li>Include at least one number</li>
                    <li>Include at least one special character</li>
                    <li>Be between 8 to 32 characters long</li>
                </ul>
                <form method="post" id="reset-password-form" style="display: none;">
                    <div><label for="password">Password: </label></div>
                    <div><input type="password" name="current-password" id="current-password" placeholder="Current Password" required /></div>
                    <div><input type="password" name="new-password" id="new-password" placeholder="New Password" required /></div>
                    <div><input type="password" name="confirm-new-password" id="confirm-new-password" placeholder="Confirm Password" required /></div>
                    <div class="flow-root"><button type="submit" id="reset-password-confirm" class="small-button" style="display: none;">Confirm</button>
                    <button type="button" id="reset-password-cancel" class="small-button" style="display: none;" onclick="resetPassword(false);">Cancel</button></div>
                </form>
            </div>
        </div>
        <div id="un-link-account-area">
            <div class='clash-form-title' id="unlink-account-title">Unlink your account</div>
            <span class='descriptive-text' id='unlink-account-desc'>If you wish to unlink your account please enter your password and press confirm. You can always re-link your account again.</span>
            <?php if (!isset($_SESSION['user']['sumid'])) { ?> 
            <div class='clash-form-title' id="link-account-title">Link your account</div>
            <span class='descriptive-text' id='link-account-desc'>To link your account please enter your League of Legends username.</span>
            
            <div>
                <button id="connect-account-button" onclick="connectAccount(true);">Connect League Account</button>
                <form method="post" id="connect-account-form" style="display: none;">
                    <div><label for="name">Summoner Name: </label></div>
                    <input type="text" name="name" id="name" required />
                    <div class="flow-root"><button type="submit" id="connect-account-confirm" class="small-button" style="display: none;">Connect</button>
                    <button type="button" id="connect-account-cancel" class="small-button" style="display: none;" onclick="connectAccount(false);">Cancel</button></div>
                </form>
            </div>
            <?php } else { 
                $currentPlayerData = getPlayerData("sumid", $_SESSION['user']['sumid']);
                echo '<div class="account-link">Linked to: 
                    <img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$currentPlayerData["Icon"].'.png" style="vertical-align:middle" width="32" loading="lazy">
                    '.$currentPlayerData["Name"].'</div>';
                ?> 
            <div id="lower-dcform">
                <button id="disconnect-account-button" onclick="disconnectAccount(true);" style="margin-top: 20px;">Disconnect League Account</button>
                <form method="post" id="disconnect-account-form" style="display: none;">
                    <div><label for="dcpassword">Password: </label></div>
                    <input type="password" name="dcpassword" id="dcpassword" placeholder="Confirm with password" required />
                    <div style="height: 50px;"><button type="submit" id="disconnect-account-confirm" class="small-button" style="display: none;">Confirm</button>
                    <button type="button" id="disconnect-account-cancel" class="small-button" style="display: none;" onclick="disconnectAccount(false);">Cancel</button></div>
                </form>
            </div>
            <?php } ?>
        </div>
        <?php
        if (isset($_POST['name'])) { ?> <div>
        <?php 
        $playerDataArray = getPlayerData("name", $_POST['name']);
            if($playerDataArray['Icon'] != ""){
                $randomIcon = getRandomIcon($playerDataArray["Icon"]);
                echo '<div id="connect-account-area"><div class="clash-form-title" style="margin-top: 12px;">Found account for: '.$playerDataArray['Name'].'</div>'.'<div class="flow-root"><img id="current-profile-icon" src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerDataArray["Icon"].'.png" style="vertical-align:middle; float: left;" width="84" loading="lazy">
                <img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$randomIcon.'.png" style="vertical-align:middle; float: right;" width="84" loading="lazy">
                </div>
                <div style="margin: -3.5em 0 3em 0;">&#10148;</div>
                <div class="descriptive-text account-desc">Temporarily change your summoner icon to the one on the <b>right</b> and click on the connect button to verify your league account.</div>
                <div class="descriptive-text account-desc">Note: The temporary icon was given to you right after the account creation. You can find it by clicking on your profile picture, selecting "all" and scrolling completely down to the bottom.</div>
                <small>If you experience any problems or your account was already claimed please reach out to an administrator.</small>
                <form method="post" id="connect-final-form">
                    <button type="button" name="final" id="connect-final-confirm" style="margin-top: 20px;">Confirm</button>
                    <div class="lds-ring" id="loader"><div></div><div></div><div></div><div></div></div>
                    <div><strong id="icon-error" style="display: none; color: #560909;">Icons stimmen nicht überein.</strong></div>
                </form>
                </div>
                </div>
                <script>
                $("#connect-final-confirm").click(function() {
                    document.getElementById("loader").style.visibility = "visible";
                    $.ajax({
                        url: "connect.php",
                        type: "POST",
                        data: {
                            icon: "'.$randomIcon.'",
                            name: "'.$playerDataArray['Name'].'",
                            sessionUsername: "'.$_SESSION['user']['username'].'"
                        },
                        success: function(data) {
                            data = JSON.parse(data);
                            document.getElementById("loader").style.visibility = "hidden";
                            if(data.status == "success"){
                                location.href = "settings?verify=true";
                            } else {
                                document.getElementById("icon-error").style.display = "unset";
                            }
                        }               
                    });
                });
                </script>'; 
            } else {
                echo "<h3>Could not find an account for: ".$_POST['name']."</h3>";
            }
        }
        ?>
        <div>
            <button id="account-delete-button" onclick="deleteAccount(true);">Delete Account</button>
            <form method="post" id="account-delete-form" style="display: none;">
                <div><label for="password">Password: </label></div>
                <input type="password" name="password" id="password" placeholder="Confirm with password" required />
                <div><button type="submit" id="account-delete-confirm" style="display: none;">Confirm</button>
                <button type="button" id="account-delete-cancel" style="display: none;" onclick="deleteAccount(false);">Cancel</button></div>
            </form>
        </div>
    </div>
</div>

<?php 
include('footer.php');
?>
