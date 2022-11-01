<?php
session_start();
 
if (!isset($_SESSION['user'])) {
    header('Location: login');
}

require_once 'clash-db.php';
require_once 'functions.php';

$db = new DB();
$account_status = $db->check_status($_SESSION['user']['id'], $_SESSION['user']['username']);
$account_status_message = $account_status['message'];
$currentPatch = file_get_contents("/var/www/html/clash/clashapp/data/patch/version.txt");

// print_r($_SESSION['user']);

if (isset($_POST['password'])) {
    $response = $db->delete_account($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['region'], $_SESSION['user']['email'], $_POST['password']);
    if ($response['status'] == 'success') {
        header('Location: logout');
    } else {
        $account_status_message = $response['message'];
    }
}

if (isset($_POST['current-password']) && isset($_POST['new-password']) && isset($_POST['confirm-new-password'])) {
    $uppercase = preg_match('@[A-Z]@', $_POST['new-password']);
    $lowercase = preg_match('@[a-z]@', $_POST['new-password']);
    $number    = preg_match('@[0-9]@', $_POST['new-password']);
    $specialChars = preg_match('@[^\w]@', $_POST['new-password']);
    if ($db->check_credentials($_SESSION['user']['email'], $_POST['current-password'])['status'] != 'success') {
        echo '<strong><div>Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.</strong></div>';
    } else {
         if ($_POST["new-password"] !== $_POST["confirm-new-password"]) {
            echo "<strong><div>New passwords do not match.</strong></div>";
         } else {
            if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($_POST['new-password']) < 8 || strlen($_POST['new-password']) > 32) {
                echo "<strong><div>Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.</strong></div>"; 
            } else {
                $options = [
                    'cost' => 11,
                ];
                $reset = $db->reset_password($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['email'], password_hash($_POST['new-password'], PASSWORD_BCRYPT, $options));
                $account_status_message = $reset['message'];
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
    $account_status_message = "Accounts successfully linked!";
} else if ($_GET['verified'] == "false") {
    $account_status_message = "Successfully disconnected accounts.";
}

if (isset($_POST['dcpassword'])) {
    $response = $db->check_credentials($_SESSION['user']['username'], $_POST['dcpassword']);
    if ($response['status'] == 'success') {
        if($db->disconnect_account($_SESSION['user']['username'], $_SESSION['user']["sumid"])){
            unset($_SESSION['user']["sumid"]);
            Header('Location: '.$_SERVER['PHP_SELF'].'?verified=false');
        } else {
            $account_status_message = "Unable to locally disconnected accounts. Please reach out to an administrator.";
        }
    } else {
        $account_status_message = 'Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.';
    }
}

include('head.php');
setCodeHeader('Settings', true, true);
include('header.php');
?>

<?php if (!empty($account_status_message)) { ?>
<div class="account_status">
    <strong><?php echo $account_status_message; ?></strong>
</div>
<?php } ?>
<strong><?php echo 'Welcome, '. $_SESSION['user']['username']; ?></strong>
<div>
    <p><input type="button" onclick="location.href='/logout';" value="Log Out"></button></p>
</div>
<div>
    <button id="reset-password-button" onclick="resetPassword(true);">Reset Password</button>
    <form method="post" id="reset-password-form" style="display: none;">
        <p><label for="password">Password: </label></p>
        <p><input type="password" name="current-password" id="current-password" placeholder="Current Password" required /></p>
        <p><input type="password" name="new-password" id="new-password" placeholder="New Password" required /></p>
        <p><input type="password" name="confirm-new-password" id="confirm-new-password" placeholder="Confirm Password" required /></p>
        <p><button type="submit" id="reset-password-confirm" style="display: none;">Confirm</button>
        <button type="button" id="reset-password-cancel" style="display: none;" onclick="resetPassword(false);">Cancel</button></p>
    </form>
</div>
<?php if (!isset($_SESSION['user']['sumid'])) { ?> 
<div>
    <button id="connect-account-button" onclick="connectAccount(true);" style="margin-top: 20px;">Connect League Account</button>
    <form method="post" id="connect-account-form" style="display: none;">
        <p><label for="name">Summoner Name: </label></p>
        <input type="text" name="name" id="name" required />
        <button type="submit" id="connect-account-confirm" style="display: none;">Connect</button>
        <button type="button" id="connect-account-cancel" style="display: none;" onclick="connectAccount(false);">Cancel</button></p>
    </form>
</div>
<?php } else { 
    $currentPlayerData = getPlayerData("sumid", $_SESSION['user']['sumid']);
    echo '<div>Linked to 
        <img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$currentPlayerData["Icon"].'.png" style="vertical-align:middle" width="16" loading="lazy">
        '.$currentPlayerData["Name"].'</div>';
    ?> 
    
<div>
    <button id="disconnect-account-button" onclick="disconnectAccount(true);" style="margin-top: 20px;">Disconnect League Account</button>
    <form method="post" id="disconnect-account-form" style="display: none;">
        <p><label for="dcpassword">Password: </label></p>
        <input type="password" name="dcpassword" id="dcpassword" placeholder="Confirm with password" required />
        <p><button type="submit" id="disconnect-account-confirm" style="display: none;">Confirm</button>
        <button type="button" id="disconnect-account-cancel" style="display: none;" onclick="disconnectAccount(false);">Cancel</button></p>
    </form>
</div>
<?php }
    if (isset($_POST['name'])) { ?> <div>
    <?php 
    $playerDataArray = getPlayerData("name", $_POST['name']);
    if($playerDataArray['Icon'] != ""){
        $randomIcon = getRandomIcon($playerDataArray["Icon"]);
        echo '<div><h3>Found account for: '.$playerDataArray['Name'].'</h3></div>'.'<div><img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerDataArray["Icon"].'.png" style="vertical-align:middle" width="84" loading="lazy">
         => 
        <img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$randomIcon.'.png" style="vertical-align:middle" width="84" loading="lazy">
        </div>
        <p>Temporarily change your summoner icon to the one above and click on the connect button to verify your league account.</p>
        <p>Note: The temporary icon was given to you right after the account creation.</p>
        <small>If you experience any problems or your account was already claimed please reach out to an administrator.</small>
        <form method="post" id="connect-final-form">
            <button type="button" name="final" id="connect-final-confirm" style="margin-top: 20px;">Confirm</button>
            <div class="lds-ring" id="loader"><div></div><div></div><div></div><div></div></div>
            <strong id="icon-error" style="display: none;">Icons stimmen nicht überein.</strong>
        </form>
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
        echo "<h3>Could not find an account for: ".$_POST['name']."</h3>";}}
        ?>
<div>
    <button id="account-delete-button" onclick="deleteAccount(true);" style="margin-top: 20px;">Delete Account</button>
    <form method="post" id="account-delete-form" style="display: none;">
        <p><label for="password">Password: </label></p>
        <input type="password" name="password" id="password" placeholder="Confirm with password" required />
        <p><button type="submit" id="account-delete-confirm" style="display: none;">Confirm</button>
        <button type="button" id="account-delete-cancel" style="display: none;" onclick="deleteAccount(false);">Cancel</button></p>
    </form>
</div>

<?php 
include('footer.php');
?>
