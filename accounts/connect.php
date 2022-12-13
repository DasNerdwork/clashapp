<?php
include_once('/hdd2/clashapp/functions.php');
require_once '/hdd2/clashapp/clash-db.php';

if (isset($_SESSION['user'])) {
    header('Location: /');
}

if (isset($_POST['icon'])) {
    $playerDataArray = getPlayerData("name", $_POST['name']);
    if($playerDataArray["Icon"] == $_POST['icon']){
        $db = new DB();
        if($db->connect_account($_POST['sessionUsername'], $playerDataArray["SumID"])){
            echo json_encode(array('status' => 'success', 'message' => 'Successfully linked accounts'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Could not connect accounts in database'.$_POST['sessionUsername'].' + '.$playerDataArray["SumID"]));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Icons do not match'));
    }
}
?>