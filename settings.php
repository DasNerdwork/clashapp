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

if (isset($_POST['current-password']) && isset($_POST['new-password']) && isset($_POST['confirm-new-password'])) {
    $uppercase = preg_match('@[A-Z]@', $_POST['new-password']);
    $lowercase = preg_match('@[a-z]@', $_POST['new-password']);
    $number    = preg_match('@[0-9]@', $_POST['new-password']);
    $specialChars = preg_match('@[^\w]@', $_POST['new-password']);
    if ($db->check_credentials($_SESSION['user']['email'], $_POST['current-password'])['status'] != 'success') {
        echo '<strong><div>Incorrect password. You can try again or <u type="button" onclick="resetPassword(true);" style="cursor: pointer;">reset</u> your password.</strong></div>';
    } else {
         if ($_POST["new-password"] !== $_POST["confirm-new-password"]) {
            echo "<strong><div>New passwords do not match.</strong></div>";
         } else {
            if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($_POST['new-password']) < 8 || strlen($_POST['new-password']) > 32) {
                echo "<strong><div>Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.</strong></div>"; 
            } else {
                $options = [
                    'cost' => 11,
                ];
                $reset = $db->reset_password($_SESSION['user']['id'], $_SESSION['user']['username'], $_SESSION['user']['email'], password_hash($_POST['new-password'], PASSWORD_BCRYPT, $options));
                $account_status_message = $reset['message'];
            }
        }
    }
}

// if (isset($_POST['current-password']) || isset($_POST['new-password']) || isset($_POST['confirm-new-password'])) {
//     echo '<script type="text/javascript">resetPassword(true);</script>';
// }

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
<div>
    <p><input type="button" onclick="location.href='/logout';" value="Log Out"></button></p>
</div>
<div>
    <button id="reset-password-button" onclick="resetPassword(true)">Reset Password</button>
    <form method="post" id="reset-password-form" style="display: none;">
        <p><label for="password">Password: </label></p>
        <p><input type="password" name="current-password" id="current-password" placeholder="Current Password" required /></p>
        <p><input type="password" name="new-password" id="new-password" placeholder="New Password" required /></p>
        <p><input type="password" name="confirm-new-password" id="confirm-new-password" placeholder="Confirm Password" required /></p>
        <p><button type="submit" id="reset-password-confirm" style="display: none;">Confirm</button>
        <button type="button" id="reset-password-cancel" style="display: none;" onclick="resetPassword(false)">Cancel</button></p>
    </form>
</div>
<div>
    <button id="account-delete-button" onclick="deleteAccount(true)" style="margin-top: 20px;">Delete Account</button>
    <form method="post" id="account-delete-form" style="display: none;">
        <p><label for="password">Password: </label></p>
        <input type="password" name="password" id="password" placeholder="Confirm with password" required />
        <p><button type="submit" id="account-delete-confirm" style="display: none;">Confirm</button>
        <button type="button" id="account-delete-cancel" style="display: none;" onclick="deleteAccount(false);">Cancel</button></p>
    </form>
</div>
