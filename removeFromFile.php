<?php
/** removeFromFile.php removes the specified champion from the local teamid.json file on the server by clicking on the given image on the webpage
 *
 * @param string $championID => The champion ID is the corresponding ingame used ID of a league champion, e.g. ID: MonkeyKing in the case of Name: Wukong
 * @param mixed $teamID => Usually consisting of either numbers-only, numbers with dashes inbetween or a combination of letters, numbers and dashes, fetched from the URL
 * @param string $currentPatch => Current live patch grabbed as string from file (generated and checked by daily from patcher.py)
 * @param array $preexistingBanFileContent => Array formatted content of local teamid.json file of selected bans for the team
 * @param array $validChamps => The data parent element with all child elements of the champion.json, containing all necessary info for all current league champions
 * @param mixed $searchedID => Either the correct $championID or false if not found
 * 
 * Example data of $_POST from clash.js:
 * $championID = "MonkeyKing";
 * $teamID = "4892086";
 */

// Grabbing and referencing the posted variables + current patch as string
$championID = $_POST["champid"];
$teamID = $_POST["teamid"];
$currentPatch = file_get_contents("/var/www/html/wordpress/clashapp/data/patch/version.txt");

// Only proceed if file with bans exists and grab content
if(file_exists('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json')){
    $preexistingBanFileContent = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json'), true);
    // Array list of all valid champions, e.g. The champion Peter with ID: Peter is invalid but Ashe with ID: Ashe is valid
    $validChamps = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), true)["data"];
    // Check if posted ID is part of champion.json
    $searchedID = array_search($championID, array_column($validChamps, 'id'));
    if($searchedID !== false){
        unset($preexistingBanFileContent["SuggestedBans"][array_search($championID, array_column($preexistingBanFileContent["SuggestedBans"], 'id'))]); // Remove Champion
        if(array_search($championID, array_column($preexistingBanFileContent["SuggestedBans"], 'id')) === false){ // Check if removal was successful
            $preexistingBanFileContent["Status"]++; // Increment status counter so website updates the live display
            $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'w'); // Clear file, add old status+1 and updated SuggestedBans
            $preexistingBanFileContent["SuggestedBans"] = array_values($preexistingBanFileContent["SuggestedBans"]);
            fwrite($fp, json_encode($preexistingBanFileContent));
            fclose($fp);
            echo '{"status":"Success"}';
        } else { // -- Error Handling --
            echo '{"status":"RemovalUnsuccessful"}';die();
        }
    } else {
        echo '{"status":"UnknownChampion"}';die();
    }
} else {
    echo '{"status":"FileDoesNotExist"}';die();
}
?> 
