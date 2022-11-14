<?php
session_start();
 
if (isset($_SESSION['user'])) {
    header('Location: /');
}

require_once 'clash-db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/var/www/html/clash/phpmailer/src/Exception.php';
require '/var/www/html/clash/phpmailer/src/PHPMailer.php';
require '/var/www/html/clash/phpmailer/src/SMTP.php';
 
$error_message = array();
$db = new DB();

if (isset($_POST['submit'])) {
    if(isset($_POST['stay-logged-in'])) {
        $stayCode = bin2hex(random_bytes(5));
        if($db->set_stay_code($_POST['mailorname'], $stayCode)){
            setcookie("stay-logged-in", $stayCode, time() + (86400 * 30), "/"); // 86400 = 1 day | Set cookie for 30 days
        }
    }
    
    $response = $db->check_credentials($_POST['mailorname'], $_POST['password']);

    if ($response['status'] == 'success') {
        $_SESSION['user'] = array('id' => $response['id'], 'region' => $response['region'], 'username' => $response['username'], 'email' => $response['email'], 'sumid' => $response['sumid']);
        header('Location: /');
    }
 
    if($response['status'] == 'error'){
        $error_message[] = $response['message'].'<script type="text/javascript">enablePWR();</script>';
    }
}

if (isset($_POST['reset'])) {
    $error_message[] = "This feature is currently unavailable. Please contact an administrator.";
}
//     $get_reset = $db->get_reset_code($_POST['mailorname']);
//     if($get_reset['resetter'] == null || (time() - $get_reset['timestamp']) > 60){
//         $reset = bin2hex(random_bytes(5));
//         if($db->set_reset_code($_POST['mailorname'], $reset)){
//             try {
//                 $template = file_get_contents("/var/www/html/clash/phpmailer/templates/reset-password.html");
//                 //Server settings
//                 $mail = new PHPMailer();
//                 $mail->isMail();                                            //Send using SMTP
//                 $mail->Host       = '***REMOVED***';                       //Set the SMTP server to send through
//                 // $mail->SMTPDebug = SMTP::DEBUG_SERVER;
//                 // $mail->SMTPAuth   = false;                                   //Enable SMTP authentication
//                 // $mail->Username   = 'mail';                //SMTP username
//                 // $mail->Password   = '***REMOVED***';                     //SMTP password
//                 // $mail->SMTPSecure = 'ssl';            //Enable implicit TLS encryption
//                 $mail->Port       = 25;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            
//                 $mail->CharSet = 'UTF-8';
//                 $mail->Encoding = 'base64';

//                 // $mail->DKIM_domain = 'dasnerdwork.net';
//                 // $mail->DKIM_private = '/var/www/key.private'; // Make sure to protect the key from being publicly accessible!
//                 // $mail->DKIM_selector = 'dkim';
//                 // $mail->DKIM_passphrase = '***REMOVED***DK';
//                 // $mail->DKIM_identity = $mail->From;

//                 //Recipients
//                 $mail->setFrom('no-reply@dasnerdwork.net');
//                 $mail->addAddress('dasnerdwork@gmail.com');              //Add a recipient
//                 // $mail->addAddress('p.gnadt@gmx.de');                        //Add a recipient
//                 // $mail->addReplyTo('no-reply@dasnerdwork.net');
//                 // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output

//                 //Attachments
//                 // $mail->addAttachment('/var/tmp/file.tar.gz');            //Add attachments
//                 // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');       //Optional name

//                 //Content
//                 $mail->isHTML(true);                                  //Set email format to HTML
//                 $variables = array();
//                 $variables['reset'] = $reset;
//                 $mail->Subject = 'Reset your password';
//                 foreach($variables as $key => $value) {
//                     $template = str_replace('{{ '.$key.' }}', $value, $template);
//                 }
//                 $mail->Body = $template;
//                 // $mail->Body    = 'You can reset your password by visiting the following link: <b>https://clash.dasnerdwork.net/reset?code='.$reset.'</b>';
//                 // $mail->Body   .= ' The link will only be valid for ~60 minutes.';
//                 // $mail->AltBody = 'You can activate your account by visiting the following link: https://clash.dasnerdwork.net/verify?account='.$verifier;
//                 // $mail->addCustomHeader('MIME-Version: 1.0');
//                 // $mail->addCustomHeader('Content-Type: text/html; charset=ISO-8859-1');

//                 $result = $mail->Send();

//                 if(!$result) {
//                     $error_message[] = "Could not deliver mail. Please contact an administrator.";
//                 } else {
//                     $success_message[] = "Successfully sent password reset mail! Please also check your spam folder.";
//                 }
//             } catch (Exception $e) {
//                 // $error_message[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
//                 $error_message[] = "Message could not be sent. Please contact an administrator.";
//             }
//         } else {
//             $error_message[] = "A given account for ".$_POST['mailorname']." does not exist.";
//         }
//     } else {
//         if((($get_reset['timestamp']+63)-time()) > 0){
//             $error_message[] = "A passwort reset mail has already been sent. Please wait ".($get_reset['timestamp']+63)-time()." seconds more before requesting another.";
//         }
//     }
// }

if (isset($_GET['password'])) {
    $success_message[] = "Password successfully reset! You may now login with your new credentials.";
}

include('head.php');
setCodeHeader('Login', true, true);
include('header.php');

if (!empty($success_message)) { 
    foreach($success_message as $su){
        echo '<div class="account_status">
                <strong>'. $su .'</strong>
              </div>';
    }
} else if (!empty($error_message)) { 
    foreach($error_message as $er){
        echo '<div class="error">
            <strong>'. $er .'</strong>
        </div>';
    }
}
?>
<div class="outer-form">
    <form method="post" class="clash-form login-form">
        <div class="clash-form-title">Login to your account</div>
        <div><label for="mailorname">Email/Username: </label></div>
        <div><input type="text" name="mailorname" id="mailorname" placeholder="Enter Email or Username" required /></div>
        <div><label for="password" id="password-label">Password: </label></div>
        <div><input type="password" name="password" id="password" placeholder="Enter Password" required /></div>
        <div><input type="checkbox" class="login-checkbox" id="stay-logged-in" name="stay-logged-in">
        <label for="stay-logged-in" class="stay-logged-in"> Stay logged in for a month</label></div>
        <div><input type="submit" name="submit" id="login-button" value="Login" /></div>
        <div>Don't have an account yet? <a href="/register">Register</a>.</div>
        <!-- <div><input type="submit" name="test" id="test-mail" value="Test Mail" /></div> -->
    </form>
</div>

<?php 
include('footer.php');
?>