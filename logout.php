<?php
session_start();
 
if (isset($_SESSION['user'])) {
    setcookie("stay-logged-in", "", time() - 3600);
    unset($_SESSION['user']);
}
 
header('Location: /');
?>