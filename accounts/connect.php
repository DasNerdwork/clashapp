<?php
include_once('/hdd1/clashapp/src/functions.php');
require_once '/hdd1/clashapp/db/clash-db.php';

if (isset($_SESSION['user'])) {
    header('Location: /');
}

if (isset($_POST['icon'])) {
    $playerDataArray = API::getPlayerData("riot-id", $_POST['name']); # TODO: PlayerData direkt aus request in settings.php übergeben
    if($playerDataArray["Icon"] == $_POST['icon']){
        $db = new DB();
        if($db->connect_account($playerDataArray["PUUID"], $_POST['sessionUsername'])){
            echo json_encode(array('status' => 'success', 'message' => 'Successfully linked accounts'));
        } else {
            echo json_encode(array('status' => 'error', 'message' => 'Could not connect accounts in database '.$_POST['sessionUsername'].' + '.$playerDataArray["PUUID"]));
        }
    } else {
        echo json_encode(array('status' => 'error', 'message' => 'Icons do not match'));
    }
}
?>