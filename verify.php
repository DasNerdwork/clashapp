<?php
session_start();
 
if (isset($_SESSION['user'])) {
    header('Location: settings');
}

require_once 'clash-db.php';
 
$return_message = '';
if (isset($_GET["account"])) {
    $db = new DB();
    $response = $db->verify_account($_GET["account"]);
    $return_message = $response['message'];
}

?>
 
<?php if (!empty($return_message)) { ?>
    <div class="error">
        <strong><?php echo $return_message; ?></strong>
    </div>
<?php } echo "<div>Account to be verified: ".$_GET["account"]."</div>"?>