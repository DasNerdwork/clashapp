<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '/hdd1/clashapp/clash-db.php';
if(isset($_POST['username'], $_POST['points'])){
    $db = new DB();
    $success = $db->addPoints($_POST['username'], $_POST['points']);
    if ($success) {
        echo 'success';
    } else {
        echo 'error';
    }
}
?>