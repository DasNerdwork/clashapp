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
 
$error_message = '';
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
 
    $error_message = ($response['status'] == 'error') ? $response['message'] : '';
    $error_message .= '<script type="text/javascript">enablePWR();</script>';
}

if (isset($_POST['reset'])) {
    $get_reset = $db->get_reset_code($_POST['mailorname']);
    if($get_reset['resetter'] == NULL || (time() - $get_reset['timestamp']) > 60){
        $reset = bin2hex(random_bytes(5));
        if($db->set_reset_code($_POST['mailorname'], $reset)){
            try {
                //Server settings
                $mail = new PHPMailer();
                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                       //Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                $mail->Username   = 'dasnerdwork@gmail.com';                //SMTP username
                $mail->Password   = 'omhuzidgbpvqrnfu';                     //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
            
                //Recipients
                $mail->setFrom('dasnerdwork@gmail.com');
                $mail->addAddress('dasnerdwork@gmail.com');              //Add a recipient
                // $mail->addAddress('p.gnadt@gmx.de');                        //Add a recipient
                // $mail->addReplyTo('no-reply@dasnerdwork.net');
                // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output

                //Attachments
                // $mail->addAttachment('/var/tmp/file.tar.gz');            //Add attachments
                // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');       //Optional name

                //Content
                $mail->isHTML(true);                                  //Set email format to HTML
                $mail->Subject = 'Reset your password';
                $mail->Body    = 'You can reset your password by visiting the following link: <b>https://clash.dasnerdwork.net/reset?code='.$reset.'</b>';
                $mail->Body   .= ' The link will only be valid for ~60 minutes.';
                // $mail->AltBody = 'You can activate your account by visiting the following link: https://clash.dasnerdwork.net/verify?account='.$verifier;
                // $mail->addCustomHeader('MIME-Version: 1.0');
                // $mail->addCustomHeader('Content-Type: text/html; charset=ISO-8859-1');

                $result = $mail->Send();

                if(!$result) {
                    // There was an error
                    // Do some error handling things here
                    echo "Could not deliver mail. Please contact an administrator.";
                } else {
                    echo "Successfully sent password reset mail";
                }
            } catch (Exception $e) {
                echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }
        } else {
            echo "A given account for ".$_POST['mailorname']." does not exist.";
        }
    } else {
        if((($get_reset['timestamp']+63)-time()) > 0){
            echo "A passwort reset mail has already been sent. Please wait ".($get_reset['timestamp']+63)-time()." seconds more before requesting another.";
        }
    }
}

if (isset($_GET['password'])) {
    echo "Password successfully reset! You may now login with your new credentials.";
}

include('head.php');
setCodeHeader('Login', true, true);
include('header.php');
?>

<?php if (!empty($error_message)) { ?>
    <div class="error">
        <strong><?php echo $error_message; ?></strong>
    </div>
<?php } ?>
<div class="outer-form">
    <form method="post" class="clash-form login-form">
        <div class="clash-form-title">Login to your account</div>
        <div><label for="mailorname">Email/Username: </label></div>
        <div><input type="text" name="mailorname" id="mailorname" placeholder="Enter Email or Username" required /></div>
        <div><label for="password">Password: </label></div>
        <div><input type="password" name="password" id="password" placeholder="Enter Password" required /></div>
        <div><input type="checkbox" id="stay-logged-in" name="stay-logged-in">
        <label for="stay-logged-in"> Stay logged in for a month</label></div>
        <div><input type="submit" name="submit" id="login-button" value="Login" /></div>
        <div>Don't have an account yet? <a href="/register">Register</a>.</div>
    </form>
</div>

<?php 
include('footer.php');
?>