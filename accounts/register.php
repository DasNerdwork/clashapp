<?php
if (session_status() === PHP_SESSION_NONE) session_start();
 
if (isset($_SESSION['user'])) {
    header('Location: settings');
}

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

require_once '/hdd1/clashapp/clash-db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use IconCaptcha\IconCaptcha;

require '/hdd1/clashapp/plugins/phpmailer/src/Exception.php';
require '/hdd1/clashapp/plugins/phpmailer/src/PHPMailer.php';
require '/hdd1/clashapp/plugins/phpmailer/src/SMTP.php';

// ini_set('display_errors', 1); ini_set('display_startup_errors', 1); error_reporting(E_ALL); // TODO rausnehmen

$db = new DB();
// $response = $db->account_exists("john.doe@example.com");

if (isset($_POST['submit'])) {
    // Validate password strength & league username regex
    $uppercase = preg_match('@[A-Z]@', $_POST['password']);
    $lowercase = preg_match('@[a-z]@', $_POST['password']);
    $number    = preg_match('@[0-9]@', $_POST['password']);
    $specialChars = preg_match('@[^\w]@', $_POST['password']);
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
    .'\x{FFCA}-\x{FFCF}\x{FFD2}-\x{FFD7}\x{FFDA}-\x{FFDC}]+$/u', $_POST['username']);

    if (strlen($_POST['username']) > 16 || strlen($_POST['username']) < 3) { // Correct Summoner Name Length
        $error_message[] = 'Summoner Names have to be between 3 and 16 characters long.';
    } else if(!$validName) { // Correct Summoner Name Format & Characters
        $error_message[] = 'Summoner Name incorrect: Allowed characters are a-Z, 0-9 and alphabets of other languages.';
    } else if(!(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL))) { // Correct mail format
        $error_message[] = 'Could not validate E-Mail format. Please use a correct E-Mail.';
    } else if($_POST['password'] !== $_POST['confirm-password']) {
        $error_message[] = 'The entered passwords do not match.';
    } else if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($_POST['password']) < 8 || strlen($_POST['password']) > 32) { // The Password meets current regex settings above
        $error_message[] = 'Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.'; 
    } else if(!(in_array($_POST['region'], array("EUW")))) { // full array: array("EUW", "EUN", "NA", "KR", "BR", "JP", "RU", "OCE", "TR", "LAN", "LAS")
        $error_message[] = 'The selected region is currently not supported.';
    } else if($db->account_exists($_POST['email'], $_POST['username'])) {
        $error_message[] = 'This account already exists. Have you <u type="button" onclick="resetPassword(true);" class="cursor-pointer">forgotten your password</u>?'; // TODO change to password reset mail instead of onclick open 
    } else if(isset($_POST['hnypt']) && $_POST['hnypt'] != ""){
        $error_message[] = 'Honeypot-Detection triggered. If you believe this is an error please contact an administrator.';
    } else {
        $config = require __DIR__ . '/../ajax/captcha-config.php';

        // Create an instance of IconCaptcha.
        $captcha = new IconCaptcha($config);
    
        // Validate the captcha.
        $validation = $captcha->validate($_POST);
    
        // Confirm the captcha was validated.
        if($validation->success()) {
            $verifier = bin2hex(random_bytes(5));
            $response = $db->create_account($_POST['username'], $_POST['region'], $_POST['email'], password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 11]), $verifier);
            $_SESSION['user'] = array('id' => $response['id'], 'region' => $response['region'], 'username' => $response['username'], 'email' => $response['email']);
            if ($response['status'] == 'success') {
                if(isset($_POST['stay-logged-in'])) {
                    $stayCode = bin2hex(random_bytes(5));
                    if($db->set_stay_code($stayCode, $_SESSION['user']['email'])){
                        setcookie("stay-logged-in", $stayCode, time() + (86400 * 30), "/"); // 86400 = 1 day | Set cookie for 30 days
                    }
                }
                try {
                    //Server settings
                    $mail = new PHPMailer();
                    $mail->isSMTP();                                            //Send using SMTP
                    $mail->Host       = 'smtp.sendgrid.net';                    //Set the SMTP server to send through
                    $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                    $mail->Username   = 'apikey';                               //SMTP username
                    $mail->Password   = '***REMOVED***';                     //SMTP password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                    $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
                
                    //Recipients
                    $mail->setFrom('no-reply@clashscout.com', 'ClashScout.com');
                    $mail->addAddress($_POST['email']);              //Add a recipient
                    // $mail->addAddress('p.gnadt@gmx.de');                        //Add a recipient
                    // $mail->addReplyTo('no-reply@dasnerdwork.net');
                    // $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            
                    //Attachments
                    // $mail->addAttachment('/var/tmp/file.tar.gz');            //Add attachments
                    // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');       //Optional name
            
                    //Content
                    $mail->isHTML(true);                                  //Set email format to HTML
                    $mail->Subject = 'Activate your Account';
                    $mail->Body    = '
                    Hey '.$_POST['username'].',<br><br>
                    Thank you for registering and welcome to  <a href="https://clashscout.com/"><b>ClashScout.com</b></a><br><br>
                    To use all of our features to the fullest you can activate your account by visiting the following link:<br><br>
                    <a><b>https://clashscout.com/verify?account='.$verifier.'</b></a><br><br>
                    Best regards,<br>
                    The ClashScout.com Team';
                    // $mail->AltBody = 'You can activate your account by visiting the following link: https://clashscout.com/verify?account='.$verifier;
                    // $mail->addCustomHeader('MIME-Version: 1.0');
                    // $mail->addCustomHeader('Content-Type: text/html; charset=ISO-8859-1');
            
                    $result = $mail->Send();
            
                    if(!$result) {
                        $error_message[] = "Could not deliver mail. Please contact an administrator.";    
                    } else {
                        $success_message[] = "Successfully sent account verify mail! Please also check your spam folder.";
                    }
                } catch (Exception $e) {
                    // $error_message[] = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
                    $error_message[] = "Message could not be sent. Please contact an administrator.";
                }
                $_SESSION['user'] = array('id' => $response['id'], 'username' => $_POST['username']);
                header('Location: settings');
            }

            $error_message[] = ($response['status'] == 'error') ? $response['message'] : '';
        } else {
            $error_message[] = 'Invalid Captcha, please try again.';
        }
    }        
}

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Register', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
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

$translations = array(
    'verify' => __('Are you a human?'),
    'loading' => __('Loading challenge...'),
    'header' => __('Select the image displayed the <u>least</u> amount of times'),
    'correct' => __('Verification complete.'),
    'incorrect' => array(
        'title' => __('Uh oh.'),
        'subtitle' => __("You've selected the wrong image.")
    ),
    'timeout' => array(
        'title' => __('Please wait.'),
        'subtitle' => __('You made too many incorrect selections.')
    )
);

echo '<script>var translations = ' . json_encode($translations) . ';</script>';

?>
<div class="h-[calc(100vh-5rem)] w-full flex justify-center items-center -mb-16">
    <form method="post" autocomplete="off" class="py-10 px-9 h-fit w-fit bg-dark box-border max-w-[402px] mb-32">
        <div class="text-center text-xl mb-4">Register your account</div>
        <div><label for="username" class="block text-xs font-bold text-left ml-1">Username: </label></div>
        <div><input type="text" name="username" autofill="custom-username" class="text-base color-white text-left w-[330px] bg-darker mt-1 mb-4 h-8 pl-1.5 focus:text-base placeholder:text-[#353950] autofill:shadow-[0_0_0_50px_#0e0f18_inset] placeholder:text-left" value="<?= isset($_POST["username"]) ? $_POST["username"] : "" ?>" placeholder="Username" maxlength=16 required /></div>
        <div><label for="region" class="block text-xs font-bold text-left ml-1">Region: </label></div>
        <div><select aria-label="Region Selector" name="region" tabindex="-1" class="border-0 outline-none cursor-pointer w-[330px] h-8 mt-1 mb-4 text-base text-center bg-[#2a2d40] text-white" placeholder="Europe West" required>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'EUW') { ?>selected="true" <?php }; ?>value="EUW">Europe West</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'EUN') { ?>selected="true" <?php }; ?>value="EUN">Europe Nordic & East</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'NA') { ?>selected="true" <?php }; ?>value="NA">North America</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'KR') { ?>selected="true" <?php }; ?>value="KR">Korea</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'BR') { ?>selected="true" <?php }; ?>value="BR">Brazil</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'JP') { ?>selected="true" <?php }; ?>value="JP">Japan</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'RU') { ?>selected="true" <?php }; ?>value="RU">Russia</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'OCE') { ?>selected="true" <?php }; ?>value="OCE">Oceania</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'TR') { ?>selected="true" <?php }; ?>value="TR">Turkey</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'LAN') { ?>selected="true" <?php }; ?>value="LAN">Latin America North</option>
                <option <?php if (isset($_POST['region']) && $_POST['region'] == 'LAS') { ?>selected="true" <?php }; ?>value="LAS">Latin America South</option>
            </select>
        </div>
        <div><label for="email" class="block text-xs font-bold text-left ml-1">Email: </label></div>
        <div><input type="email" name="email" class="text-base color-white text-left w-[330px] bg-darker mt-1 mb-4 h-8 pl-1.5 focus:text-base placeholder:text-[#353950] autofill:shadow-[0_0_0_50px_#0e0f18_inset] placeholder:text-left" value="<?= isset($_POST["email"]) ? $_POST["email"] : "" ?>" placeholder="mail@example.com" required /></div>
        <div class="absolute -left-[5000px]" aria-hidden="true"><input type="text" name="hnypt" class="hidden" tabindex="-1"/></div>
        <div><label for="password" class="block text-xs font-bold text-left ml-1">Password:</label></div>
        <div><input type="password" name="password" class="text-base color-white text-left w-[330px] bg-darker mt-1 mb-4 h-8 pl-1.5 focus:text-base placeholder:text-[#353950] autofill:shadow-[0_0_0_50px_#0e0f18_inset] placeholder:text-left" placeholder="Enter Password" maxlength=32 required /></div>
        <div><input type="password" name="confirm-password" class="text-base color-white text-left w-[330px] bg-darker mt-1 mb-4 h-8 pl-1.5 focus:text-base placeholder:text-[#353950] autofill:shadow-[0_0_0_50px_#0e0f18_inset] placeholder:text-left" placeholder="Confirm Password" maxlength=32 required /></div>
        <div class="cursor-default text-center"><input type="checkbox" class="cursor-pointer accent-[#27358b]" id="stay-logged-in" name="stay-logged-in">
        <label for="stay-logged-in" class="stay-logged-in"> Stay logged in for a month</label></div>
        <?php echo \IconCaptcha\Token\IconCaptchaToken::render(); ?>
        <div class="iconcaptcha-widget" data-theme="dark"></div>
        <div><input type="submit" name="submit" class="float-center ml-0 mt-4 h-8 mb-4 w-[330px] bg-[#27358b] text-white text-base cursor-pointer focus:text-base hover:brightness-75 active:brightness-75" value="Register" /></div>
        <div>Already have an account? <a class="text-[#bbb] hover:text-white hover:underline" href="/login">Login</a>.</div>
    </form>
    <link href="/iconcaptcha/iconcaptcha.min.css" rel="stylesheet" type="text/css">
    <script src="/iconcaptcha/iconcaptcha.min.js" type="text/javascript"></script>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            IconCaptcha.init('.iconcaptcha-widget', {
                general: {
                    endpoint: '/ajax/captcha-request.php', // required, change the path accordingly.
                    fontFamily: 'inherit',
                    credits: 'show',
                },
                security: {
                    interactionDelay: 1500,
                    hoverProtection: true,
                    displayInitialMessage: true,
                    initializationDelay: 500,
                    incorrectSelectionResetDelay: 3000,
                    loadingAnimationDuration: 1000,
                },
                locale: {
                    initialization: {
                        verify: translations.verify,
                        loading: translations.loading,
                    },
                    header: translations.header,
                    correct: translations.correct,
                    incorrect: {
                        title: translations.incorrect.title,
                        subtitle: translations.incorrect.subtitle,
                    },
                    timeout: {
                        title: translations.timeout.title,
                        subtitle: translations.timeout.subtitle
                    }
                }
            });
        });
    </script>
</div>

<?php 
include('/hdd1/clashapp/templates/footer.php');
?>