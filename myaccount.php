<?php
session_start();
 
if (!isset($_SESSION['user'])) {
    header('Location: login');
}

require_once 'clash-db.php';

$db = new DB();
$account_status = $db->check_status($_SESSION['user']['id'], $_SESSION['user']['username']);
$account_status_message = $account_status['message'];

if (isset($_POST['password'])) {
    $response = $db->delete_account($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['region'], $_SESSION['user']['email'], $_POST['password']);
    if ($response['status'] == 'success') {
        header('Location: logout');
    } else {
        $account_status_message = $response['message'];
    }
}

?>
<head>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<!-- <link rel="stylesheet" href="/clashapp/clash.css"> -->
<script type="text/javascript" src="../clashapp/clash.js"></script>
</head>
<?php if (!empty($account_status_message)) { ?>
<div class="account_status">
    <strong><?php echo $account_status_message; ?></strong>
</div>
<?php } ?>
<strong><?php echo 'Welcome, '. $_SESSION['user']['username']; ?></strong>
<p>
    <a href="logout">Log Out</a>
</p>
<button id="account-delete-button" onclick="deleteAccount(true)">Delete Account</button>
<form method="post" id="account-delete-form" style="display: none;">
    <p><label for="password">Password: </label></p>
    <input type="password" name="password" id="password" placeholder="Confirm with password" required />
    <p><button type="submit" id="account-delete-confirm" style="display: none;">Confirm</button>
    <button id="account-delete-cancel" style="display: none;" onclick="deleteAccount(false)">Cancel</button></p>
</form>

