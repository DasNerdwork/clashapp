<?php

// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

session_start();

require_once '/hdd1/clashapp/vendor/autoload.php';
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

require_once '/hdd1/clashapp/clash-db.php';

function generateQR($username){
    $db = new DB();
    $google2fa = new Google2FA();
    $QRsecret = $google2fa->generateSecretKey();
    $qrCodeUrl = $google2fa->getQRCodeUrl(
        "clashscout.com",
        $username,
        $QRsecret
    );
    $renderer = new ImageRenderer(
        new RendererStyle(400),
        new ImagickImageBackEnd()
    );
    $writer = new Writer($renderer);
    // $db->set_2fa_code($email, $QRsecret);
    return array("qr" => base64_encode($writer->writeString($qrCodeUrl)), "secret" => $QRsecret);
}

function verifyEntered2FA($email, $userInput){
    $db = new DB();
    $google2fa = new Google2FA();
    $secret = $db->get_2fa_code($email);
    if ($google2fa->verifyKey($secret, $userInput)) {
        return true;
    } else {
        return false;
    }
}

function verifyLocal2FA($secret, $userInput){
    $google2fa = new Google2FA();
    if ($google2fa->verifyKey($secret, $userInput)) {
        return true;
    } else {
        return false;
    }
}

if (isset($_POST['twofa'])){
    $generateQRArray = generateQR($_SESSION['user']['username']);
    $qrSecret = $generateQRArray['secret'];
    $_SESSION['user']['secret'] = $qrSecret;
    echo $generateQRArray['qr'];
}
?>