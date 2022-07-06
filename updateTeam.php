<?php
include_once('update.php');

$id = 0;

if(isset($_POST["usernames"])){
    echo "{\"$id\":";
    foreach($_POST["usernames"] as $playerName){
        // echo $playerName."<br>";
        updateProfile($playerName, 15);    
        sleep(1);
        $id++;
        if($id < count($_POST["usernames"])){
            echo ", \"$id\":";
        }
    }
    echo "}";
}
?>