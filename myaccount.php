<?php
session_start();
 
if (!isset($_SESSION['user'])) {
    header('Location: login');
}
?>
 
<strong><?php echo 'Welcome, '. $_SESSION['user']['username']; ?></strong>
<p>
    <a href="logout">Log Out</a>
</p>