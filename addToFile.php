<?php
/** addToFile.php adds the "selectableBan"-Array on image click to a json file for live sharing on webpage
 *
 * @param string $championID => The champion ID is the corresponding ingame used ID of a league champion, e.g. ID: MonkeyKing in the case of Name: Wukong
 * @param string $championName => The corresponding champion name of a league champion, e.g. Miss Fortune
 * @param mixed $teamID => Usually consisting of either numbers-only, numbers with dashes inbetween or a combination of letters, numbers and dashes, fetched from the URL
 * @param string $currentPatch => Current live patch grabbed as string from file (generated and checked by daily from patcher.py)
 * @param array $validChamps => The data parent element with all child elements of the champion.json, containing all necessary info for all current league champions
 * @param mixed $searchedID => Either the correct $championID or false if not found
 * @param mixed $searchedName => Either the correct $championName or false if not found
 * @param array $preexistingBanFileContent => Array formatted content of already generated file of selected bans for the team
 * @param array $suggestBanArray => Initialized as empty array but later on filled with necessary info for previous checks, either this array or the above can exist at once
 * 
 * Example data of $_POST from clash.js:
 * $championID = "MonkeyKing";
 * $championName = "Wukong";
 * $teamID = "4892086";
 */

// Grabbing and referencing the posted variables + current patch as string
$championID = $_POST["champid"];
$championName = $_POST["champname"];
$teamID = $_POST["teamid"];
$currentPatch = file_get_contents("/var/www/html/wordpress/clashapp/data/patch/version.txt");

// Array list of all valid champions, e.g. The champion Peter with ID: Peter and Name: Peter is invalid but Ashe with ID: Ashe and Name: Ashe is valid
$validChamps = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), true)["data"];

// Simple check if the url at least contains a teamID, as format from Riot changes no formatting check, just empty check
if($teamID == "" || $teamID == "/"){
    echo '{"status":"InvalidTeamID"}';die(); // Return via javascript json the InvalidTeamID status and stop further processing
} else {
    $searchedID = array_search($championID, array_column($validChamps, 'id'));
    $searchedName = array_search($championName, array_column($validChamps, 'name'));
    // Checks if both ID (key) and password (key2) are valid entries of the $valdiChamps array (champion.json entries)
    if($searchedID !== $searchedName || $searchedID === false || $searchedName === false){
        echo '{"status":"CodeInjectionDetected"}';die();
    } else {
        // If both ID and name are valid
        if(file_exists('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json')){
            // $preexistingBanFileContent = File content of preexisting savefile
            $preexistingBanFileContent = json_decode(file_get_contents('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json'), true);
            if(array_search($championID, array_column($preexistingBanFileContent["SuggestedBans"], 'id')) !== false){
                echo '{"status":"ElementAlreadyInArray"}';die();
            } else if (count($preexistingBanFileContent["SuggestedBans"]) >= 10) { // Maximum of 10 elements in #selectedBans
                echo '{"status":"MaximumElementsExceeded"}';die();
            } else {
                echo '{"status":"Success"}';
                // If all criterias are met open preexisting file for change (adding the entry to the file)
                $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
                if($preexistingBanFileContent["SuggestedBans"] == $championID){ // Double check to make sure
                    fclose($fp);
                } else {
                    $preexistingBanFileContent["SuggestedBans"][] = array("id"=>$championID,"name"=>$championName);
                    $preexistingBanFileContent["Status"]++; // Increase status by 1 for every change made to the file (later on used by javascript in clash.js)
                    fwrite($fp, json_encode($preexistingBanFileContent));
                    fclose($fp);
                }
            }
        } else {
            $suggestBanArray = array(); // Initialize empty array to write into empty file
            echo '{"status":"FileDoesNotExist"}'; // E.g. on first visit or file deletion -> File has to be created
            $suggestBanArray["SuggestedBans"][] = array("id"=>$championID,"name"=>$championName);
            $suggestBanArray["Status"] = 1; // Initial status of 1
            $fp = fopen('/var/www/html/wordpress/clashapp/data/teams/'.$teamID.'.json', 'c');
            fwrite($fp, json_encode($suggestBanArray));
            fclose($fp);
        }
    }
}
?>
