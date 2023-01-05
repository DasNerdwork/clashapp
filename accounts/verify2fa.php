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
        echo '<div class="bg-[#ff000040] -mb-12 text-base text-center leading-[3rem]">
            <strong>'. $er .'</strong>
        </div>';
    }
} 
?>
<div class="h-[calc(100%-5rem)] w-full flex justify-center items-center -mb-16">
    <form method="post" class="mb-44 max-w-[330px] py-10 px-9 h-fit w-fit bg-dark text-center box-border">
        <div class="mb-4 text-xl">Verify with Two-Factor Auth</div>
        <div class="text-left ml-2"><label for='verifier'>Two-Factor Authentication Code: </label></div>
        <div><input type='text' class="text-base color-white text-left w-64 bg-darker mt-1 mb-4 h-8 pl-1 focus:text-base placeholder:text-[#353950]" name='verifier' placeholder='Enter 2FA Code' required oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\..*)\./g, '$1');" maxlength='6'></div>
        <div class="h-10 -mt-2"><button type="submit" name="submit" class="float-right ml-0 mt-1 h-8 mb-4 w-24 bg-[#27358b] text-white text-base">Submit</button>
        <button type="button" class="float-left ml-0 mt-1 h-8 mb-4 w-24 bg-[#2a2d40] text-white text-base" onclick="window.location.pathname = '/login';">Cancel</button></div>
        
        <small class="inline-block text-justify text-[#353950]">If you don't have access to your Authenticator App anymore please reach out to an administrator to have your 2FA removed.</small>
    </form>
</div>
<?php 
include('/hdd2/clashapp/templates/footer.php');
?>