<?php
session_start();
 
if (isset($_SESSION['user'])) {
    header('Location: myaccount');
}

require_once 'clash-db.php';
 
$error_message = '';
if (isset($_POST['submit'])) {
    $db = new DB();
    $response = $db->check_credentials($_POST['mailorname'], $_POST['password']);

    if ($response['status'] == 'success') {
        $_SESSION['user'] = array('id' => $response['id'], 'region' => $response['region'], 'username' => $response['username'], 'email' => $response['email']);
        header('Location: myaccount');
    }
 
    $error_message = ($response['status'] == 'error') ? $response['message'] : '';
}

?>
 
<?php if (!empty($error_message)) { ?>
    <div class="error">
        <strong><?php echo $error_message; ?></strong>
    </div>
<?php } ?>
<form method="post">
    <p>
        <label for="mailorname">Email/Username: </label>
        <input type="text" name="mailorname" id="mailorname" placeholder="Enter Email or Username" required />
    </p>
    <p>
        <label for="password">Password: </label>
        <input type="password" name="password" id="password" placeholder="Enter Password" required />
    </p>
    <input type="submit" name="submit" value="Login" />
</form>