<?php
if (!isset($_SESSION)) session_start();
 
if (isset($_SESSION['user'])) {
    setcookie("stay-logged-in", "", time() - 3600);
    unset($_SESSION['user']);
}
 
if(isset($_GET['location'])) {
    header('Location: '.$_GET['location']);
} else {
    header('Location: /');
}
?>