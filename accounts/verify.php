<?php
if (!isset($_SESSION)) session_start();
 
if (isset($_SESSION['user'])) {
    header('Location: /');
}

require_once '/hdd1/clashapp/clash-db.php';
 
$return_message = '';
if (isset($_GET["account"])) {
    $db = new DB();
    $response = $db->verify_account($_GET["account"]);
    $return_message = $response['message'];
}

include('/hdd1/clashapp/templates/head.php');
setCodeHeader('Verify', $css = true, $javascript = false, $alpinejs = false, $websocket = false);
include('/hdd1/clashapp/templates/header.php');
?>
 
<?php if (!empty($return_message)) { ?>
    <div class="bg-[#ff000040] -mb-12 text-base text-center leading-[3rem]">
        <strong><?php echo $return_message; ?></strong>
    </div>
<?php } 
include('/hdd1/clashapp/templates/footer.php');
?>
