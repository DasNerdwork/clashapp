<?php
if (session_status() === PHP_SESSION_NONE) session_start();
 
if (isset($_SESSION['user'])) {
    header('Location: settings');
}

require_once '/hdd1/clashapp/clash-db.php';
 
$return_message = '';

if (isset($_GET["code"])) {
    include('/hdd1/clashapp/templates/head.php');
    setCodeHeader('Reset Password', $css = true, $javascript = true, $alpinejs = false, $websocket = false);
    include('/hdd1/clashapp/templates/header.php');

    $db = new DB();
    $response = $db->check_reset_code($_GET["code"]);
    $return_message = $response['message'];

    if ($response['status'] == 'success') {
        

    if (!empty($return_message)) { ?>
        <div class="bg-[#ff000040] -mb-12 text-base text-center leading-[3rem]">
            <strong><?php echo $return_message; ?></strong>
        </div>
   <?php 
        } 
        // echo "<div>Code for pw reset: ".$_GET["code"]."</div>";
    ?>
    <h1>Reset Password:</h1>
        <form method="post">
            <p>
                <label for="password">Password: </label>
                <input type="password" name="password" id="password" placeholder="Enter New Password" required />
            </p>
            <p>
                <label for="password">Password: </label>
                <input type="password" name="password-confirm" id="password-confirm" placeholder="Confirm New Password" required />
            </p>
            <input type="submit" name="submit" value="Reset Password" />
        </form>
    <?php
    } else {
        header('Location: 404');
    }
}

if (isset($_POST['submit'])) {
    $uppercase = preg_match('@[A-Z]@', $_POST['password']);
    $lowercase = preg_match('@[a-z]@', $_POST['password']);
    $number    = preg_match('@[0-9]@', $_POST['password']);
    $specialChars = preg_match('@[^\w]@', $_POST['password']);
    if(!$uppercase || !$lowercase || !$number || !$specialChars || strlen($_POST['password']) < 8 || strlen($_POST['password']) > 32) { // The Password meets current regex settings above
        echo "<strong><div>Password should be at least 8 characters in length and should include at least one upper case letter, one number, and one special character.</strong></div>"; 
    } else if ($_POST["password"] !== $_POST["password-confirm"]) {
        echo "<strong><div>Passwords do not match.</strong></div>"; 
    } else {
        $options = [
            'cost' => 11,
        ];
        $reset = $db->reset_password($response['id'], $response['username'], $response['email'], password_hash($_POST['password'], PASSWORD_BCRYPT, $options));
        if($reset['status'] == 'success'){
            header('Location: https://clashscout.com/login?password=reset');
        }
    }
}
include('/hdd1/clashapp/templates/footer.php');
?>
 
