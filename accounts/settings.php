<?php
if (!isset($_SESSION)) session_start();

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
 
if (!isset($_SESSION['user'])) {
    header('Location: login');
}

// print_r($_SESSION);

require_once '/hdd2/clashapp/clash-db.php';
require_once '/hdd2/clashapp/functions.php';
require_once '/hdd2/clashapp/accounts/qr-codes.php';

$error_message = array();
$success_message = array();

$db = new DB();
$account_status = $db->check_status($_SESSION['user']['id'], $_SESSION['user']['username']);
if($account_status['status'] == "error"){
    $error_message[] = $account_status['message'];
} else if($account_status['status'] == "success"){
    $success_message[] = $account_status['message'];
}
$currentPatch = file_get_contents("/hdd2/clashapp/data/patch/version.txt");

if (isset($_POST['password'])) {
    $response = $db->delete_account($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['region'], $_SESSION['user']['email'], $_POST['password']);
    if ($response['status'] == 'success') {
        header('Location: https://clash.dasnerdwork.net/logout?deactivated=true');
    } else {
        $error_message[] = $response['message'];
    }
}

if (isset($_POST['current-password']) && isset($_POST['new-password']) && isset($_POST['confirm-new-password'])) {
    $uppercase = preg_match('@[A-Z]@', $_POST['new-password']);
    $lowercase = preg_match('@[a-z]@', $_POST['new-password']);
    $number    = preg_match('@[0-9]@', $_POST['new-password']);
    $specialChars = preg_match('@[^\w]@', $_POST['new-password']);
    if ($db->check_credentials($_SESSION['user']['email'], $_POST['current-password'])['status'] != 'success') {
        $error_message[] = 'Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" class="cursor-pointer">reset</u> your password.'; // TODO change to password reset mail instead of onclick open
    } else {
         if ($_POST["new-password"] !== $_POST["confirm-new-password"]) {
            $error_message[] = "The entered passwords do not match.";
         } else {
            if ($_POST["current-password"] == $_POST["new-password"]) {
                $error_message[] = "New password cannot be the same as old password.";
             } else {
                if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($_POST['new-password']) < 8 || strlen($_POST['new-password']) > 32) {
                    $error_message[] = "Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character."; 
                } else {
                    $options = [
                        'cost' => 11,
                    ];
                    $reset = $db->reset_password($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['email'], password_hash($_POST['new-password'], PASSWORD_BCRYPT, $options));
                    $success_message[] = $reset['message'];
                }
            }
        }
    }
}

if (isset($_GET['verify'])){
    $sumid = $db->get_sumid($_SESSION['user']['username']);
    if($sumid['status'] == 'success') {
        $_SESSION['user']["sumid"] = $sumid['sumid'];
    }
    Header('Location: https://clash.dasnerdwork.net/settings?verified=true');
}

if (isset($_GET['verified'])){
    if($_GET['verified'] == "true") {
       $success_message[] = "Accounts successfully linked!";
   } else if ($_GET['verified'] == "false") {
       $success_message[] = "Successfully disconnected accounts.";
   }
}

if (isset($_GET['remove2FA'])){
    if($_GET['remove2FA']) {
       $success_message[] = "Successfully removed Two-Factor Authentication.";
   }
}

if (isset($_POST['dcpassword'])) {
    $response = $db->check_credentials($_SESSION['user']['username'], $_POST['dcpassword']);
    if ($response['status'] == 'success') {
        if($db->disconnect_account($_SESSION['user']['username'], $_SESSION['user']["sumid"])){
            unset($_SESSION['user']["sumid"]);
            Header('Location: https://clash.dasnerdwork.net/settings?verified=false');
        } else {
            echo "<script>setError('Unable to locally disconnected accounts. Please reach out to an administrator.');</script>"; // TODO: Fix error banner display
        }
    } else {
        echo "<script>setError('Incorrect password. You can try again or <u type=\"button\" onclick=\"resetPassword(true);\" class=\"cursor-pointer\">reset</u> your password.');</script>"; // TODO change to password reset mail instead of onclick open
    }
}

if (isset($_POST['twofa-input'])){
    echo '<script type="text/javascript">
        function setError(message, error){
            document.addEventListener("DOMContentLoaded", function(event){
                let header = document.getElementsByTagName("header")[0];
                var errorBanners = document.getElementsByClassName("error");
                let check = true;
                let bannerId;
                if(errorBanners != null){
                for (let i = 0; i < errorBanners.length; i++) {
                    const eb = errorBanners[i];
                    if(eb.firstChild.innerHTML == message){
                    check = false
                    break
                    }
                }
                }
                if(!check) return "error bereits vorhanden";
                let errorMsg = document.createElement("div");
                error ? errorMsg.setAttribute("class", "error") : errorMsg.setAttribute("class", "account_status");
                errorMsg.innerHTML = "<strong>"+message+"</strong>";
                header.parentNode.insertBefore(errorMsg, header.nextElementSibling);
            });
        }
    </script>';
    if($_SESSION['user']['secret'] != null){
        if(verifyLocal2FA($_SESSION['user']['secret'], $_POST['twofa-input'])){
            $db = new DB();
            if($db->set_2fa_code($_SESSION['user']['secret'], $_SESSION['user']['email'])){
                unset($_SESSION['user']['secret']);
                $_SESSION['user']['2fa'] = "true";
                echo "<script>setError('Successfully enabled Two-Factor Authentication.', false);</script>";
            } else {
                echo "<script>setError('Unable to connect to the database. Please contact an administrator.', true);</script>";
            }
        } else {
            echo "<script>setError('The entered 2FA-Code is either incorrect or expired.', true);</script>";
        }
    } else {
        echo "<script>setError('Unable to set 2FA-Secret. Please contact an administrator.', true);</script>";
    }
}

if (isset($_POST['remove-twofa-input'])){
    echo '<script type="text/javascript">
    function setError(message, error){
        document.addEventListener("DOMContentLoaded", function(event){
            let header = document.getElementsByTagName("header")[0];
            var errorBanners = document.getElementsByClassName("error");
            let check = true;
            let bannerId;
            if(errorBanners != null){
            for (let i = 0; i < errorBanners.length; i++) {
                const eb = errorBanners[i];
                if(eb.firstChild.innerHTML == message){
                check = false
                break
                }
            }
            }
            if(!check) return "error bereits vorhanden";
            let errorMsg = document.createElement("div");
            error ? errorMsg.setAttribute("class", "error") : errorMsg.setAttribute("class", "account_status");
            errorMsg.innerHTML = "<strong>"+message+"</strong>";
            header.parentNode.insertBefore(errorMsg, header.nextElementSibling);
        });
    }
    </script>';
    if (strlen($_POST['remove-twofa-input']) == 6 && is_numeric($_POST['remove-twofa-input'])){
        if(verifyEntered2FA($_SESSION['user']['email'], $_POST['remove-twofa-input'])){
            $db = new DB();
            if($db->remove_2fa_code($_SESSION['user']['email'])){
                if(isset($_SESSION['user']['2fa'])){
                    unset($_SESSION['user']['2fa']);
                }
                Header('Location: https://clash.dasnerdwork.net/settings?remove2FA=true');
            } else {
                echo "<script>setError('Unable to connect to the database. Please contact an administrator.', true);</script>";
            }
        } else {
            echo "<script>setError('The entered 2FA-Code is either incorrect or expired.', true);</script>";
        }
    } else {
        echo "<script>setError('Incorrect or forbidden 2FA-Code format.', true);</script>";
    }
}

include('/hdd2/clashapp/templates/head.php');
setCodeHeader('Settings', true, true);
include('/hdd2/clashapp/templates/header.php');

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
?>
<div class="h-[44vw] w-full flex justify-center items-center">
    <div class="clash-form py-12 px-11 h-fit w-fit bg-dark box-border max-w-md text-center" x-data="{ resetPassword: false, disconnectLeague: false, connectLeague: false, confirmLeague: true, add2FA: false, remove2FA: false, deleteAccount: false }">
        <div class='text-xl mb-2'><?php echo 'Welcome, '. $_SESSION['user']['username']; ?></div>
        <div><input type="button" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" onclick="location.href='/logout';" value="&#129044; Log Out    "></button></div>
        <div>
            <button class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" @click="resetPassword = true, disconnectLeague = false, connectLeague = false, confirmLeague = false ,add2FA = false, remove2FA = false, deleteAccount = false">Reset Password</button>
            <div x-cloak x-transition x-show="resetPassword">
                <div class='text-xl mb-2'>Reset your password</div>
                <span class='text-sm text-justify block'>The same rules apply as used to register, meaning that the password has to:</span>
                <ul class='pt-0 pr-0 pb-3 pl-2 list-["\27A4"]'>
                    <li class="pl-2 text-white text-xs text-left m-2">Include at least one lowercase letter</li>
                    <li class="pl-2 text-white text-xs text-left m-2">Include at least one uppercase letter</li>
                    <li class="pl-2 text-white text-xs text-left m-2">Include at least one number</li>
                    <li class="pl-2 text-white text-xs text-left m-2">Include at least one special character</li>
                    <li class="pl-2 text-white text-xs text-left m-2">Be between 8 to 32 characters long</li>
                </ul>
                <form method="post">
                    <div><label for="password">Password: </label></div>
                    <div><input type="password" class="pl-1" name="current-password" id="current-password" placeholder="Current Password" required /></div>
                    <div><input type="password" class="pl-1" name="new-password" id="new-password" placeholder="New Password" required /></div>
                    <div><input type="password" class="pl-1" name="confirm-new-password" id="confirm-new-password" placeholder="Confirm Password" required /></div>
                    <div class="flow-root"><button type="submit" id="reset-password-confirm" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base">Confirm</button>
                    <button type="button" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" @click="resetPassword = false">Cancel</button></div>
                </form>
            </div>
        </div>
        <div id="un-link-account-area">
            <div class="unlink-account-area" x-cloak x-transition x-show="disconnectLeague">
                <div class='text-xl mb-2' id="unlink-account-title">Unlink your account</div>
                <span class='text-sm text-justify block' id='unlink-account-desc'>If you wish to unlink your account please enter your password and press confirm. You can always re-link your account again.</span>
            </div>
                <?php if (!isset($_SESSION['user']['sumid'])) { ?>
            <button id="connect-account-button" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" @click="resetPassword = false, disconnectLeague = false, confirmLeague = false, connectLeague = true, add2FA = false, remove2FA = false, deleteAccount = false" x-show="!connectLeague">Connect League Account</button>
            <div class="link-account-area" x-cloak x-transition x-show="connectLeague">
                <div class='text-xl mb-2' id="link-account-title">Link your account</div>
                <span class='text-sm text-justify block' id='link-account-desc'>To link your account please enter your League of Legends username.</span>
                <form method="post" id="connect-account-form">
                    <div><label for="name">Summoner Name: </label></div>
                    <input type="text" name="connectname" id="connectname" maxlength=16 required />
                    <div class="flow-root"><button type="submit" id="connect-account-confirm" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" @click="resetPassword = false, disconnectLeague = false, confirmLeague = true, connectLeague = false, add2FA = false, remove2FA = false, deleteAccount = false">Connect</button>
                    <button type="button" id="connect-account-cancel" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" @click="connectLeague = false">Cancel</button></div>
                </form>
            </div>
            <?php } else { 
                $currentPlayerData = getPlayerData("sumid", $_SESSION['user']['sumid']);
                echo '<div class="account-link" id="account-link"><span class="block">Linked to:</span>
                    <img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$currentPlayerData["Icon"].'.webp" class="m-auto inline-flex justify-center max-w-[32px] max-h-[32px]" width="32" loading="lazy">
                    '.$currentPlayerData["Name"].'</div>';
                ?> 
            <div id="lower-dcform">
                <button id="disconnect-account-button" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" @click="resetPassword = false, disconnectLeague = true, connectLeague = false, confirmLeague = false, add2FA = false, remove2FA = false, deleteAccount = false" x-show="!disconnectLeague">Disconnect League Account</button>
                <form method="post" id="disconnect-account-form" x-cloak x-transition x-show="disconnectLeague">
                    <div><label for="dcpassword">Password: </label></div>
                    <input type="password" name="dcpassword" id="dcpassword" placeholder="Confirm with password" required />
                    <div class="h-7 -mt-2"><button type="submit" id="disconnect-account-confirm" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base">Confirm</button>
                    <button type="button" id="disconnect-account-cancel" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" @click="disconnectLeague = false">Cancel</button></div>
                </form>
            </div>
            <?php } ?>
        </div>
        <?php

        if (isset($_POST['connectname'])) {
            $validName = preg_match('/^[A-Za-z0-9\xAA\xB5\xBA\xC0-\xD6\xD8-\xF6\xF8-\x{02C1}\x{02C6}-\x{02D1}\x{02E0}-\x{02E4}\x{02EC}\x{02EE}\x{0370}-\x{0374}\x{0376}\x{0377}\x{037A}-\x{037D}\x{037F}\x{0386}\x{0388}-\x{038A}\x{038C}'
            .'\x{038E}-\x{03A1}\x{03A3}-\x{03F5}\x{03F7}-\x{0481}\x{048A}-\x{052F}\x{0531}-\x{0556}\x{0559}\x{0561}-\x{0587}\x{05D0}-\x{05EA}\x{05F0}-\x{05F2}\x{0620}-\x{064A}\x{066E}\x{066F}\x{0671}-\x{06D3}\x{06D5}\x{06E5}\x{06E6}\x{06EE}'
            .'\x{06EF}\x{06FA}-\x{06FC}\x{06FF}\x{0710}\x{0712}-\x{072F}\x{074D}-\x{07A5}\x{07B1}\x{07CA}-\x{07EA}\x{07F4}\x{07F5}\x{07FA}\x{0800}-\x{0815}\x{081A}\x{0824}\x{0828}\x{0840}-\x{0858}\x{08A0}-\x{08B4}\x{0904}-\x{0939}\x{093D}'
            .'\x{0950}\x{0958}-\x{0961}\x{0971}-\x{0980}\x{0985}-\x{098C}\x{098F}\x{0990}\x{0993}-\x{09A8}\x{09AA}-\x{09B0}\x{09B2}\x{09B6}-\x{09B9}\x{09BD}\x{09CE}\x{09DC}\x{09DD}\x{09DF}-\x{09E1}\x{09F0}\x{09F1}\x{0A05}-\x{0A0A}\x{0A0F}'
            .'\x{0A10}\x{0A13}-\x{0A28}\x{0A2A}-\x{0A30}\x{0A32}\x{0A33}\x{0A35}\x{0A36}\x{0A38}\x{0A39}\x{0A59}-\x{0A5C}\x{0A5E}\x{0A72}-\x{0A74}\x{0A85}-\x{0A8D}\x{0A8F}-\x{0A91}\x{0A93}-\x{0AA8}\x{0AAA}-\x{0AB0}\x{0AB2}\x{0AB3}'
            .'\x{0AB5}-\x{0AB9}\x{0ABD}\x{0AD0}\x{0AE0}\x{0AE1}\x{0AF9}\x{0B05}-\x{0B0C}\x{0B0F}\x{0B10}\x{0B13}-\x{0B28}\x{0B2A}-\x{0B30}\x{0B32}\x{0B33}\x{0B35}-\x{0B39}\x{0B3D}\x{0B5C}\x{0B5D}\x{0B5F}-\x{0B61}\x{0B71}\x{0B83}'
            .'\x{0B85}-\x{0B8A}\x{0B8E}-\x{0B90}\x{0B92}-\x{0B95}\x{0B99}\x{0B9A}\x{0B9C}\x{0B9E}\x{0B9F}\x{0BA3}\x{0BA4}\x{0BA8}-\x{0BAA}\x{0BAE}-\x{0BB9}\x{0BD0}\x{0C05}-\x{0C0C}\x{0C0E}-\x{0C10}\x{0C12}-\x{0C28}\x{0C2A}-\x{0C39}\x{0C3D}'
            .'\x{0C58}-\x{0C5A}\x{0C60}\x{0C61}\x{0C85}-\x{0C8C}\x{0C8E}-\x{0C90}\x{0C92}-\x{0CA8}\x{0CAA}-\x{0CB3}\x{0CB5}-\x{0CB9}\x{0CBD}\x{0CDE}\x{0CE0}\x{0CE1}\x{0CF1}\x{0CF2}\x{0D05}-\x{0D0C}\x{0D0E}-\x{0D10}\x{0D12}-\x{0D3A}\x{0D3D}'
            .'\x{0D4E}\x{0D5F}-\x{0D61}\x{0D7A}-\x{0D7F}\x{0D85}-\x{0D96}\x{0D9A}-\x{0DB1}\x{0DB3}-\x{0DBB}\x{0DBD}\x{0DC0}-\x{0DC6}\x{0E01}-\x{0E30}\x{0E32}\x{0E33}\x{0E40}-\x{0E46}\x{0E81}\x{0E82}\x{0E84}\x{0E87}\x{0E88}\x{0E8A}\x{0E8D}'
            .'\x{0E94}-\x{0E97}\x{0E99}-\x{0E9F}\x{0EA1}-\x{0EA3}\x{0EA5}\x{0EA7}\x{0EAA}\x{0EAB}\x{0EAD}-\x{0EB0}\x{0EB2}\x{0EB3}\x{0EBD}\x{0EC0}-\x{0EC4}\x{0EC6}\x{0EDC}-\x{0EDF}\x{0F00}\x{0F40}-\x{0F47}\x{0F49}-\x{0F6C}\x{0F88}-\x{0F8C}'
            .'\x{1000}-\x{102A}\x{103F}\x{1050}-\x{1055}\x{105A}-\x{105D}\x{1061}\x{1065}\x{1066}\x{106E}-\x{1070}\x{1075}-\x{1081}\x{108E}\x{10A0}-\x{10C5}\x{10C7}\x{10CD}\x{10D0}-\x{10FA}\x{10FC}-\x{1248}\x{124A}-\x{124D}\x{1250}-\x{1256}'
            .'\x{1258}\x{125A}-\x{125D}\x{1260}-\x{1288}\x{128A}-\x{128D}\x{1290}-\x{12B0}\x{12B2}-\x{12B5}\x{12B8}-\x{12BE}\x{12C0}\x{12C2}-\x{12C5}\x{12C8}-\x{12D6}\x{12D8}-\x{1310}\x{1312}-\x{1315}\x{1318}-\x{135A}\x{1380}-\x{138F}'
            .'\x{13A0}-\x{13F5}\x{13F8}-\x{13FD}\x{1401}-\x{166C}\x{166F}-\x{167F}\x{1681}-\x{169A}\x{16A0}-\x{16EA}\x{16F1}-\x{16F8}\x{1700}-\x{170C}\x{170E}-\x{1711}\x{1720}-\x{1731}\x{1740}-\x{1751}\x{1760}-\x{176C}\x{176E}-\x{1770}'
            .'\x{1780}-\x{17B3}\x{17D7}\x{17DC}\x{1820}-\x{1877}\x{1880}-\x{18A8}\x{18AA}\x{18B0}-\x{18F5}\x{1900}-\x{191E}\x{1950}-\x{196D}\x{1970}-\x{1974}\x{1980}-\x{19AB}\x{19B0}-\x{19C9}\x{1A00}-\x{1A16}\x{1A20}-\x{1A54}\x{1AA7}'
            .'\x{1B05}-\x{1B33}\x{1B45}-\x{1B4B}\x{1B83}-\x{1BA0}\x{1BAE}\x{1BAF}\x{1BBA}-\x{1BE5}\x{1C00}-\x{1C23}\x{1C4D}-\x{1C4F}\x{1C5A}-\x{1C7D}\x{1CE9}-\x{1CEC}\x{1CEE}-\x{1CF1}\x{1CF5}\x{1CF6}\x{1D00}-\x{1DBF}\x{1E00}-\x{1F15}'
            .'\x{1F18}-\x{1F1D}\x{1F20}-\x{1F45}\x{1F48}-\x{1F4D}\x{1F50}-\x{1F57}\x{1F59}\x{1F5B}\x{1F5D}\x{1F5F}-\x{1F7D}\x{1F80}-\x{1FB4}\x{1FB6}-\x{1FBC}\x{1FBE}\x{1FC2}-\x{1FC4}\x{1FC6}-\x{1FCC}\x{1FD0}-\x{1FD3}\x{1FD6}-\x{1FDB}'
            .'\x{1FE0}-\x{1FEC}\x{1FF2}-\x{1FF4}\x{1FF6}-\x{1FFC}\x{2071}\x{207F}\x{2090}-\x{209C}\x{2102}\x{2107}\x{210A}-\x{2113}\x{2115}\x{2119}-\x{211D}\x{2124}\x{2126}\x{2128}\x{212A}-\x{212D}\x{212F}-\x{2139}\x{213C}-\x{213F}'
            .'\x{2145}-\x{2149}\x{214E}\x{2183}\x{2184}\x{2C00}-\x{2C2E}\x{2C30}-\x{2C5E}\x{2C60}-\x{2CE4}\x{2CEB}-\x{2CEE}\x{2CF2}\x{2CF3}\x{2D00}-\x{2D25}\x{2D27}\x{2D2D}\x{2D30}-\x{2D67}\x{2D6F}\x{2D80}-\x{2D96}\x{2DA0}-\x{2DA6}'
            .'\x{2DA8}-\x{2DAE}\x{2DB0}-\x{2DB6}\x{2DB8}-\x{2DBE}\x{2DC0}-\x{2DC6}\x{2DC8}-\x{2DCE}\x{2DD0}-\x{2DD6}\x{2DD8}-\x{2DDE}\x{2E2F}\x{3005}\x{3006}\x{3031}-\x{3035}\x{303B}\x{303C}\x{3041}-\x{3096}\x{309D}-\x{309F}\x{30A1}-\x{30FA}'
            .'\x{30FC}-\x{30FF}\x{3105}-\x{312D}\x{3131}-\x{318E}\x{31A0}-\x{31BA}\x{31F0}-\x{31FF}\x{3400}-\x{4DB5}\x{4E00}-\x{9FD5}\x{A000}-\x{A48C}\x{A4D0}-\x{A4FD}\x{A500}-\x{A60C}\x{A610}-\x{A61F}\x{A62A}\x{A62B}\x{A640}-\x{A66E}'
            .'\x{A67F}-\x{A69D}\x{A6A0}-\x{A6E5}\x{A717}-\x{A71F}\x{A722}-\x{A788}\x{A78B}-\x{A7AD}\x{A7B0}-\x{A7B7}\x{A7F7}-\x{A801}\x{A803}-\x{A805}\x{A807}-\x{A80A}\x{A80C}-\x{A822}\x{A840}-\x{A873}\x{A882}-\x{A8B3}\x{A8F2}-\x{A8F7}'
            .'\x{A8FB}\x{A8FD}\x{A90A}-\x{A925}\x{A930}-\x{A946}\x{A960}-\x{A97C}\x{A984}-\x{A9B2}\x{A9CF}\x{A9E0}-\x{A9E4}\x{A9E6}-\x{A9EF}\x{A9FA}-\x{A9FE}\x{AA00}-\x{AA28}\x{AA40}-\x{AA42}\x{AA44}-\x{AA4B}\x{AA60}-\x{AA76}\x{AA7A}'
            .'\x{AA7E}-\x{AAAF}\x{AAB1}\x{AAB5}\x{AAB6}\x{AAB9}-\x{AABD}\x{AAC0}\x{AAC2}\x{AADB}-\x{AADD}\x{AAE0}-\x{AAEA}\x{AAF2}-\x{AAF4}\x{AB01}-\x{AB06}\x{AB09}-\x{AB0E}\x{AB11}-\x{AB16}\x{AB20}-\x{AB26}\x{AB28}-\x{AB2E}\x{AB30}-\x{AB5A}'
            .'\x{AB5C}-\x{AB65}\x{AB70}-\x{ABE2}\x{AC00}-\x{D7A3}\x{D7B0}-\x{D7C6}\x{D7CB}-\x{D7FB}\x{F900}-\x{FA6D}\x{FA70}-\x{FAD9}\x{FB00}-\x{FB06}\x{FB13}-\x{FB17}\x{FB1D}\x{FB1F}-\x{FB28}\x{FB2A}-\x{FB36}\x{FB38}-\x{FB3C}\x{FB3E}'
            .'\x{FB40}\x{FB41}\x{FB43}\x{FB44}\x{FB46}-\x{FBB1}\x{FBD3}-\x{FD3D}\x{FD50}-\x{FD8F}\x{FD92}-\x{FDC7}\x{FDF0}-\x{FDFB}\x{FE70}-\x{FE74}\x{FE76}-\x{FEFC}\x{FF21}-\x{FF3A}\x{FF41}-\x{FF5A}\x{FF66}-\x{FFBE}\x{FFC2}-\x{FFC7}'
            .'\x{FFCA}-\x{FFCF}\x{FFD2}-\x{FFD7}\x{FFDA}-\x{FFDC}]+$/u', $_POST['connectname']);

            if (strlen($_POST['connectname']) > 16 || strlen($_POST['connectname']) < 3) {
                echo '<script>setError("Summoner Names have to be between 3 and 16 characters long.");</script>';
            } else if(!$validName) {
                echo '<script>setError("Summoner Name incorrect: Allowed characters are a-Z, 0-9 and alphabets of other languages.");</script>';
            } else {
                $playerDataArray = getPlayerData("name", $_POST['connectname']);
                if($playerDataArray['Icon'] != ""){
                    $randomIcon = getRandomIcon($playerDataArray["Icon"]);
                    echo '
                    <div>
                        <div id="connect-account-area" class="border-y-2 border-[#21222c] border-dashed pt-2" x-cloak x-transition x-show="confirmLeague">
                            <div class="mb-4 text-xl">Found account for: '.$playerDataArray['Name'].'</div>'.'
                            <div class="flex justify-center items-center gap-8 mb-4">
                                <img id="current-profile-icon" src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$playerDataArray["Icon"].'.webp" class="w-20" loading="lazy">
                                <span>&#10148;</span>
                                <img src="/clashapp/data/patch/'.$currentPatch.'/img/profileicon/'.$randomIcon.'.webp" class="w-20" loading="lazy">
                            </div>
                            <div class="text-sm text-justify block account-desc">Temporarily change your summoner icon to the one on the <b>right</b> and click on the connect button to verify your league account.</div>
                            <div class="text-sm text-justify block account-desc">Note: The temporary icon was given to you right after the account creation. You can find it by clicking on your profile picture, selecting "all" and scrolling completely down to the bottom.</div>
                            <small>If you experience any problems or your account was already claimed please reach out to an administrator.</small>
                            <form method="post" id="connect-final-form" class="flex justify-center mb-0">
                                <button type="button" name="final" id="connect-final-confirm" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white">Confirm</button>
                            </form>
                        </div>
                    </div>
                    <script>
                    $("#connect-final-confirm").click(function() {
                        $("#connect-final-confirm").prop("disabled", true);
                        setTimeout(
                            function() {
                                $("#connect-final-confirm").prop("disabled", false);
                            }, 3000);
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
                                if(data.status == "success"){
                                    location.href = "settings?verify=true";
                                } else {
                                    setError(\'Icons stimmen nicht überein.\');
                                }
                            }               
                        });
                    });
                    </script>'; 
                } else {
                    echo '<script>setError("Could not find an active league of legends account for '.$_POST['connectname'].'.");</script>';
                }
            }
        }
        ?>
        <div id="2fa-area">
        <?php if (!isset($_SESSION['user']['2fa'])) { ?> 
            <button id="2fa-button" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" @click="getQRCode(),resetPassword = false, disconnectLeague = false, connectLeague = false, confirmLeague = false, add2FA = true, remove2FA = false, deleteAccount = false">Add Two-Factor Authentication</button>
            <div id="2fa-description" x-cloak x-transition x-show="add2FA">
                <div class='text-xl mb-2'>Add Two-Factor Authentication</div>
                <span class='text-sm text-justify block' id='2fa-desc'>To enable 2FA for this account scan the QR Code below with your Google Authenticator App and confirm with the in-app code.</span>
                <form method="post" id="set-twofa-form">
                    <div><label for='twofa' id='twofa-label'>Two-Factor Authentication Code: </label></div>
                    <div><input type='text' name='twofa-input' id='twofa-input' placeholder='Enter 2FA Code' required oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');" maxlength='6' class="pl-1"></div>
                    <div class="h-10"><button type="submit" name="twofa-confirm" id="twofa-button" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base">Submit</button>
                    <button type="button" id="twofa-cancel" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" @click="add2FA = false" >Cancel</button></div>
                </form>
                <span class='text-sm text-justify block' id="auth-desc">By clicking on one of the images below you will be redirected to the Google Authenticator app store page.</span>
                <div class="m-4" id="auth-images">
                    <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" class="no-underline inline-block my-auto mx-1">
                        <img src="/clashapp/data/misc/webp/playstore.webp" width="140">
                    </a>
                    <a href="itms-apps://apps.apple.com/de/app/google-authenticator/id388497605" class="no-underline inline-block my-auto mx-1">
                        <img src="/clashapp/data/misc/webp/appstore.webp" width="140">
                    </a>
                </div>
            </div>
            <?php } else if($_SESSION['user']['2fa']) { ?>
            <button id="remove-2fa-button" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" @click="resetPassword = false, disconnectLeague = false, connectLeague = false, confirmLeague = false, add2FA = false, remove2FA = true, deleteAccount = false">Remove Two-Factor Auth</button>
            <div id="remove-2fa-description" x-cloak x-transition x-show="remove2FA">
                <div class='text-xl mb-2'>Remove Two-Factor Authentication</div>
                <span class='text-sm text-justify block' id='remove-2fa-desc'>To disable 2FA for this account you have to confirm with the current in-app code of your Authenticator App.</span>
                <form method="post" id="remove-twofa-form">
                    <div><label for='remove-twofa' id='remove-twofa-label'>Two-Factor Authentication Code: </label></div>
                    <div><input type='text' name='remove-twofa-input' id='remove-twofa-input' placeholder='Enter 2FA Code' required oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');" maxlength='6' class="pl-1"></div>
                    <div class="h-10"><button type="submit" name="remove-twofa-confirm" id="remove-twofa-button" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base">Submit</button>
                    <button type="button" id="remove-twofa-cancel" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base"  @click="remove2FA = false">Cancel</button></div>
                </form>
                <small id="remove-2fa-notice">If you don't have access to your Authenticator App anymore please reach out to an administrator to have your 2FA removed.</small>
            </div>
            <?php } ?>
        </div>
        <div id="account-delete-area">
            <button id="account-delete-button" class="mt-3 mb-3 h-8 text-base w-64 bg-light text-white" @click="resetPassword = false, disconnectLeague = false, connectLeague = false, confirmLeague = false, add2FA = false, remove2FA = false, deleteAccount = true">Delete Account</button>
            <div id="delete-desc" x-cloak x-transition x-show="deleteAccount">
                <div class='text-xl mb-2'>Delete your account</div>
                <span class='text-sm text-justify block'>Deleting your account has to be confirmed with your current password. Deleting the account will mean that upon clicking the confirm button:</span>
                <ul class='password-conditions'>
                    <li>The account will be disabled immediately</li>
                    <li>The account will stay disabled until deletion 2-3 days after</li>
                    <li>All of the data associated with this account will then be deleted from our database too</li>
                    <li>The 2-3 day delay is used to ensure the short-term possibility to restore accounts upon wrongly-submitted deletions.</li>
                </ul>
                <small>If you wish to re-create an account associated with your data again you will have to wait the deactivation delay or
                    reach out to an administrator.</small>
                <form method="post" id="account-delete-form">
                    <div><label for="password">Password: </label></div>
                    <input type="password" name="password" id="password" placeholder="Confirm with password" required />
                    <div class="flow-root"><button type="submit" id="account-delete-confirm" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base">Confirm</button>
                    <button type="button" id="account-delete-cancel" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" @click="deleteAccount = false">Cancel</button></div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php 
include('/hdd2/clashapp/templates/footer.php');
?>