<?php
include_once('/hdd1/clashapp/functions.php');

if(isset($_POST['match']) && isset($_POST['playerName'])){
    downloadMatchByID($_POST['match'], $_POST['playerName']);
    echo "Matches successfully updated";
}
?>