<?php
include_once('update.php');

if(isset($_POST["usernames"])){
    foreach($_POST["usernames"] as $playerName){
        updateProfile($_POST["usernames"], 15);      
    }
}
?>