<?php
include_once('update.php');

/** updateTeam.php updates a whole clash team consiting of 5 players by running the update.php for each player.
 * 
 * @param int $id => A counter
 * @param string $playerName => The name of a player, e.g. DasNerdwork
 * 
 */

$id = 0;

if(isset($_POST["usernames"])){
    echo "{\"$id\":";
    foreach($_POST["usernames"] as $playerName){
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