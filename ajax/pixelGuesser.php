<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '/hdd1/clashapp/db/clash-db.php';
include_once('/hdd1/clashapp/src/functions.php');
if(isset($_POST['username'], $_POST['points'])){
    if(!isValidPlayerName($_POST['username'])){
        die("Invalid username: " . $_POST['username']);
    }
    if(!is_numeric($_POST['points'])){
        die("Invalid points value: " . $_POST['points']);
    }
    $db = new DB();
    $success = $db->addPoints($_POST['username'], $_POST['points']);
    if ($success) {
        echo 'success';
    } else {
        echo 'error';
    }
}
?>