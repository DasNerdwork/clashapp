<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

if (!isset($_SESSION)) session_start();
// print_r($_SESSION);

require_once '/hdd2/clashapp/accounts/qr-codes.php';
require_once '/hdd2/clashapp/clash-db.php';
 
include('/hdd2/clashapp/templates/head.php');
setCodeHeader('Clash', true, true);
include('/hdd2/clashapp/templates/header.php');

$error_message = array();

if (isset($_SESSION['user'])) {
    header('Location: /');
}

if (isset($_POST['submit'])) {
    if (strlen($_POST['verifier']) == 6 && is_numeric($_POST['verifier'])) { // Correct 2FA Length
        if(verifyEntered2FA($_SESSION['temp']['email'], $_POST['verifier'])){
            $db = new DB();
            $response = $db->get_credentials_2fa($_SESSION['temp']['email']);
            if($response['status'] == 'success'){
                $_SESSION['user'] = array('id' => $response['id'], 'region' => $response['region'], 'username' => $response['username'], 'email' => $response['email'], 'sumid' => $response['sumid'], '2fa' => $response['2fa']);
                unset($_SESSION['temp']);
                echo '<script type="text/javascript">window.location.href="/";</script>';
            } else {
                $error_message[] = $response['message'];
            }
        } else {
            $error_message[] = "The entered Two-Factor Code is incorrect or expired.";
        }
    } else {
        $error_message[] = "Two-Factor Codes have to be all numbers and 6 characters long.";
    }
}

if (!empty($error_message)) { 
    foreach($error_message as $er){
        echo '<div class="error">
            <strong>'. $er .'</strong>
        </div>';
    }
} 
?>
<div class="outer-form">
    <form method="post" class="clash-form login-form" style="max-width: 350px;">
        <div class="clash-form-title" style="margin-bottom: 20px;">Verify with Two-Factor Auth</div>
        <div><label for='verifier' id='verifier-label'>Two-Factor Authentication Code: </label></div>
        <div><input type='text' name='verifier' id='verifier' placeholder='Enter 2FA Code' required oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');" maxlength='6'></div>
        <div style="height: 50px; margin-top: -10px;"><button type="submit" name="submit" id="verifier-button" class="small-button">Submit</button>
        <button type="button" id="verifier-cancel" class="small-button" onclick="window.location.pathname = '/login';">Cancel</button></div>
        
        <small id="verifier-notice" style="display: inline-block; text-align: justify;">If you don't have access to your Authenticator App anymore please reach out to an administrator to have your 2FA removed.</small>
    </form>
</div>
<?php 
include('/hdd2/clashapp/templates/footer.php');
?>