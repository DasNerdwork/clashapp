<?php
/**
 * 1. API request to get SumID by username
 * 2. API request to get player.by-summoner (to get the "teamId"
 * 3. API request to get the other SumIDs via team.by-teami
 * 4. Call update on all users and get their contents
 *      5x for general user Info
 *      5x for Mastery
 *      5x for rankData
 *      5x for matchIDlist
 *      => 100-23= 77
 *      => 77/5 = 15,4 -> 15
 * 5. Download data for 15 newest matches via updateProfile($_POST["username"], 15);
 */

$output = json_decode(file_get_contents('/hdd1/clashapp/misc/player.by-summoner.json'), true);
echo "<pre>";
print_r($output);
echo "</pre>";
?>