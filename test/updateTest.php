<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesFunction;
require_once('/hdd1/clashapp/src/update.php');
require_once('/hdd1/clashapp/src/functions.php');


#[CoversFunction('updateProfile')]
#[CoversFunction('processResponseData')]
#[CoversFunction('callAllFinish')]
#[UsesClass('MongoDBHelper')]
#[UsesClass('API')]
#[UsesFunction('isValidID')]
#[UsesFunction('isValidMatchID')]
#[UsesFunction('championIdToFilename')]
#[UsesFunction('championIdToName')]
#[UsesFunction('objectToArray')]
#[UsesFunction('sanitizeMongoQueryValue')]
class UpdateTest extends TestCase {
    public function testUpdateProfile() {
        $mdb = new MongoDBHelper();
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $tryUpdateAgain = false;
        $returnData = updateProfile($puuid, "test", "puuid");
        
        $this->assertIsString($returnData, 'Returned data is no a string');
        $this->assertNotEmpty($returnData, 'Returned data is empty or an empty string');
        if(str_contains($returnData, '{"status":"up-to-date"}')){
            $this->assertEquals('{"status":"up-to-date"}', $returnData, 'Up-To-Date data does not return the up-to-date status');
            $playerData = $mdb->findDocumentByField('players', 'PlayerData.PUUID', $puuid);
            if($playerData['success']){
                $matchIDArray = objectToArray($playerData['document'])['MatchIDs'];
                $matchDataBeforeTest = $matchIDArray;
                $matchToDelete = array_key_first($matchIDArray);
                unset($matchIDArray[$matchToDelete]);
                $removeMatch = $mdb->addElementToDocument('players', 'PlayerData.PUUID', $puuid, 'MatchIDs', $matchIDArray); // Remove MatchID from playerdata
                $this->assertTrue($removeMatch['success'], 'Removing the MatchID from playerdata was not successful');
                $deleteMatch = $mdb->deleteDocumentByField('matches', 'metadata.matchId', $matchToDelete);  // Remove match from collection
                $this->assertTrue($deleteMatch['success'], 'Deleteing the match from the collection was not successful');
                $tryUpdateAgain = true;
            }
        } 
        if(!str_contains($returnData, '{"status":"up-to-date"}') || $tryUpdateAgain){
            if($tryUpdateAgain){
                $returnData = updateProfile($puuid, "test", "puuid");
                $this->assertIsString($returnData, 'Returned data is no a string');
                $this->assertNotEmpty($returnData, 'Returned data is empty or an empty string');
            }
            $this->assertStringContainsString("xhr.open('POST', '/ajax/downloadMatch.php', true);", $returnData, 'Returnstring does not contain necessary parts');
            $puuidPattern = "/requests\['(.*?)'\] = 'Done'/";
            preg_match($puuidPattern, $returnData, $matchingID);
            $this->assertNotEmpty($matchingID[1], 'Matching ID should not be empty');
            $this->assertTrue(isValidID($matchingID[1]), 'Matching ID should be a valid ID');

            $matchIDpattern = '/matchids=({".*?"})/';
            preg_match($matchIDpattern, $returnData, $matchIDsJson);
            $matchIDsArray = json_decode($matchIDsJson[1], true);
            $this->assertNotEmpty($matchIDsArray, 'MatchID array should not be empty');
            $this->assertIsArray($matchIDsArray, 'MatchID array should be an array');
            foreach ($matchIDsArray as $matchID => $value) {
                $this->assertTrue(isValidMatchID($matchID), 'One or more matchIDs do not meet validation checks');
            }

            // Check if it contains processReponseData
            // $this->assertStringContainsString("var playerColumns = document.getElementsByClassName('single-player-column');", $returnData, 'Returnstring does not contain necessary parts');
            // $this->assertStringContainsString("historyColumn.innerHTML = response.matchHistory;", $returnData, 'Returnstring does not contain necessary parts');

            // Check if it contains callAllFinish
            $this->assertStringContainsString("console.log('ALL PLAYERS FINISHED');", $returnData, 'Returnstring does not contain necessary parts');
            $this->assertStringContainsString("var data = 'puuids=' + puuids + '&teamid=' + teamID;", $returnData, 'Returnstring does not contain necessary parts');

            $matchesPattern = '/matches=(\[[^\]]+\])/';
            preg_match($matchesPattern, $returnData, $matchingMatches);
            $matchesArray = json_decode($matchingMatches[1]);
            $this->assertNotEmpty($matchesArray, 'Matches array should not be empty');
            $this->assertIsArray($matchesArray, 'Matches array should be an array');
            foreach ($matchesArray as $matchID) {
                $this->assertTrue(isValidMatchID($matchID), 'One ore more matchIDs do not meet validation checks');
            }

            if($tryUpdateAgain){ // If we manually removed data for test, reset to previous
                $this->assertTrue(API::downloadMatchesByID([$matchToDelete], 'PHPUnit'), 'Downloading the match we deleted for testing before was not successful');
                $resetMatchData = $mdb->addElementToDocument('players', 'PlayerData.PUUID', $puuid, 'MatchIDs', $matchDataBeforeTest); // Reset matchdata in playerdata
                $this->assertTrue($resetMatchData['success'], 'Resetting playerdata matchdata was not successful');
            }
        }
    }
}
