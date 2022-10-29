<?php
session_start();
 
if (!isset($_SESSION['user'])) {
    header('Location: login');
}

require_once 'clash-db.php';

$db = new DB();
$account_status = $db->check_status($_SESSION['user']['id'], $_SESSION['user']['username']);
$account_status_message = $account_status['message'];

if (!empty($account_status_message)) { ?>
<div class="account_status">
    <strong><?php echo $account_status_message; ?></strong>
</div>
<?php } ?>
<strong><?php echo 'Welcome, '. $_SESSION['user']['username']; ?></strong>
<p>
    <a href="logout">Log Out</a>
</p>