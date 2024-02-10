<?php
if (session_status() === PHP_SESSION_NONE) session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_SESSION['user'])) {
    header('Location: /');
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/hdd1/clashapp/plugins/phpmailer/src/Exception.php';
require '/hdd1/clashapp/plugins/phpmailer/src/PHPMailer.php';
require '/hdd1/clashapp/plugins/phpmailer/src/SMTP.php';
require_once '/hdd1/clashapp/clash-db.php';
include_once('/hdd1/clashapp/functions.php');
 
$error_message = array();
$db = new DB();

if (isset($_POST['submit'])) {
    if(isset($_POST['stay-logged-in'])) {
        $stayCode = bin2hex(random_bytes(5));
        if($db->set_stay_code($stayCode, $_POST['mailorname'])){
            setcookie("stay-logged-in", $stayCode, time() + (86400 * 30), "/"); // 86400 = 1 day | Set cookie for 30 days
        }
    }
    
    $response = $db->check_credentials($_POST['mailorname'], $_POST['password']);

    if ($response['status'] == 'success') {
        if($db->get_2fa_code($_POST['mailorname']) != NULL){
            $_SESSION['temp'] = array('email' => $_POST['mailorname']);
            header('Location: verify2fa');
        } else {
            $_SESSION['user'] = array('id' => $response['id'], 'region' => $response['region'], 'username' => $response['username'], 'email' => $response['email'], 'puuid' => $response['puuid']);
            if(isset($_GET['location'])) {
                header('Location: '.$_GET['location']);
            } else {
                header('Location: /');
            }
        }
       }
 
    if($response['status'] == 'error'){
        $error_message[] = $response['message'].'<script type="text/javascript">enablePWR();</script>';
    }
}

if (isset($_POST['reset'])) {
    $error_message[] = "This feature is currently unavailable. Please contact an administrator.";
}

if (isset($_GET['password'])) {
    $success_message[] = "Password successfully reset! You may now login with your new credentials.";
}

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Login', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');

if (!empty($success_message)) { 
    foreach($success_message as $su){
        echo '<div class="bg-[#00ff0040] -mb-12 text-base text-center leading-[3rem]">
                <strong>'. $su .'</strong>
              </div>';
    }
} else if (!empty($error_message)) { 
    foreach($error_message as $er){
        echo '<div class="bg-[#ff000040] -mb-12 text-base text-center leading-[3rem]">
            <strong>'. $er .'</strong>
        </div>';
    }
}
?>
<div class="h-[calc(100vh-5rem)] w-full flex justify-center items-center -mb-16">
    <form method="post" class="py-8 px-7 h-fit w-fit bg-dark box-border max-w-[22rem] text-center mb-32">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <div class="text-center text-xl mb-4">Login to your account</div>
        <div><label for="mailorname" class="text-xs font-bold block text-left ml-1">Email/Username: </label></div>
        <div><input type="text" name="mailorname" class="text-base color-white text-left w-64 bg-darker mt-1 mb-4 h-8 pl-1.5 focus:text-base placeholder:text-[#353950] autofill:shadow-[0_0_0_50px_#0e0f18_inset] placeholder:text-left" placeholder="Enter Email or Username" required /></div>
        <div><label for="password" class="text-xs font-bold block text-left ml-1">Password: </label></div>
        <div><input type="password" name="password" class="text-base color-white text-left w-64 bg-darker mt-1 mb-4 h-8 pl-1.5 focus:text-base placeholder:text-[#353950] autofill:shadow-[0_0_0_50px_#0e0f18_inset] placeholder:text-left" placeholder="Enter Password" required /></div>
        <div class="cursor-default"><input type="checkbox" class="cursor-pointer accent-[#27358b]" id="stay-logged-in" name="stay-logged-in">
        <label for="stay-logged-in" class="stay-logged-in"> Stay logged in for a month</label></div>
        <div><input type="submit" name="submit" class="float-center ml-0 mt-4 h-8 mb-4 w-64 bg-[#27358b] text-white text-base cursor-pointer focus:text-base" value="Login" /></div>
        <div>Don't have an account yet? <a href="/register" class="text-[#bbb] hover:text-white hover:underline">Register</a>.</div>
        <!-- <div><input type="submit" name="test" id="test-mail" value="Test Mail" /></div> -->
    </form>
</div>

<?php 
include('/hdd1/clashapp/templates/footer.php');
?>