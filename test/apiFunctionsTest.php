<?php
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesFunction;
require_once('/hdd1/clashapp/src/functions.php');
require_once('/hdd1/clashapp/src/apiFunctions.php');
$_SERVER['SERVER_NAME'] = "clashscout.com";
$_SERVER['HTTP_REFERER'] = "https://clashscout.com/";
include_once('/hdd1/clashapp/lang/translate.php');
$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");

#[CoversClass(API::class)]
#[UsesClass(MongoDBHelper::class)]
#[UsesFunction('championIdToFilename')]
#[UsesFunction('championIdToName')]
#[UsesFunction('isValidID')]
#[UsesFunction('isValidPlayerName')]
#[UsesFunction('isValidPlayerTag')]
#[UsesFunction('isValidPosition')]
#[UsesFunction('sanitizeMongoQueryValue')]
class ApiFunctionsTest extends TestCase {
    public function testGetPlayerDataByName() {
        $actualData = API::getPlayerData("riot-id", "thenerdwork#nerdy");

        $this->assertArrayHasKey('Icon', $actualData, "Icon key is missing");
        $this->assertIsNumeric($actualData['Icon'], "Icon ID is not numeric");
        $this->assertGreaterThanOrEqual(0, $actualData['Icon'], "Icon ID is less than 0");

        $this->assertArrayHasKey('Level', $actualData, "Level key is missing");
        $this->assertIsNumeric($actualData['Level'], "Level is not numeric");
        $this->assertGreaterThanOrEqual(0, $actualData['Level'], "Level is less than 0");

        $this->assertArrayHasKey('LastChange', $actualData, "LastChange key is missing");
        $this->assertNotNull($actualData['LastChange'], "LastChange is null");
        $this->assertGreaterThan(1256515200000, $actualData['LastChange'], "LastChange is less than or equal to 27th October 2009");

        $this->assertArrayHasKey('PUUID', $actualData, "PUUID key is missing");
        $this->assertEquals('wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA', $actualData['PUUID'], "PUUID is not equal");

        $this->assertArrayHasKey('GameName', $actualData, "Name key is missing");
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\p{L}]{3,16}$/', $actualData['GameName'], "Name is not in the valid format");

        $this->assertArrayHasKey('Tag', $actualData, "Tag key is missing");
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\p{L}]{3,5}$/', $actualData['Tag'], "Tag is not in the valid format");

        $actualDataPUUID = API::getPlayerData("puuid", "wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA");

        $this->assertEquals($actualData['Icon'], $actualDataPUUID['Icon'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['GameName'], $actualDataPUUID['GameName'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['Tag'], $actualDataPUUID['Tag'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['Level'], $actualDataPUUID['Level'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['PUUID'], $actualDataPUUID['PUUID'], "Data is not the same regarding access type");
        $this->assertArrayHasKey('Icon', $actualDataPUUID, "Icon key is missing");
        $this->assertArrayHasKey('GameName', $actualDataPUUID, "Name key is missing");
        $this->assertArrayHasKey('Tag', $actualDataPUUID, "Tag key is missing");
        $this->assertArrayHasKey('Level', $actualDataPUUID, "Level key is missing");
        $this->assertArrayHasKey('PUUID', $actualDataPUUID, "PUUID key is missing");
        $this->assertArrayHasKey('LastChange', $actualDataPUUID, "LastChange key is missing");
    }

    public function testGetMasteryScores() {
        global $currentPatch;
        $championJson = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), true);
        $actualData = API::getMasteryScores("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA");
    
        foreach ($actualData as $masteryData) {
            $this->assertArrayHasKey("Champion", $masteryData, "Champion key is missing");
            $this->assertTrue($this->doesChampionNameExist($masteryData['Champion'], $championJson['data']), "Champion name does not exist");
    
            $this->assertArrayHasKey("Filename", $masteryData, "Filename key is missing");
            $this->assertNotNull($masteryData['Filename'], "Filename is null");
    
            $this->assertArrayHasKey("Lvl", $masteryData, "Lvl key is missing");
            $this->assertGreaterThanOrEqual(0, $masteryData['Lvl'], "Lvl is less than 0");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $masteryData['Lvl'], "Lvl is greater than PHP_INT_MAX");
    
            $this->assertArrayHasKey("Points", $masteryData, "Points key is missing");
            $this->assertIsNumeric(str_replace(',', '.', $masteryData['Points']), "Points is not numeric");
            $this->assertGreaterThanOrEqual(0, str_replace(',', '.', $masteryData['Points']), "Points is less than 0");
    
            $this->assertArrayHasKey("LastPlayed", $masteryData, "LastPlayed key is missing");
            $this->assertNotNull($masteryData["LastPlayed"], "LastPlayed is null");
            $this->assertGreaterThan(1256515200, $masteryData['LastPlayed'], "LastPlayed is less than or equal to 27th October 2009");
    
            if (array_key_exists('LvlUpTokens', $masteryData)) {
                $this->assertIsNumeric($masteryData['LvlUpTokens'], "LvlUpTokens is not numeric");
                $this->assertGreaterThanOrEqual(0, $masteryData['LvlUpTokens'], "LvlUpTokens is less than 0");
                $this->assertLessThanOrEqual(PHP_INT_MAX, $masteryData['LvlUpTokens'], "LvlUpTokens is greater than PHP_INT_MAX");
            }
        }
    }

    private function doesChampionNameExist($championName, $championData)
    {
        foreach ($championData as $singleChampion) {
            if ($singleChampion['name'] === $championName) {
                return true;
            }
        }
        return false;
    }

    public function testGetCurrentRank() {
        $rankReturnArray = API::getCurrentRank("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA");

        if(empty($rankReturnArray)){
            $this->assertEmpty($rankReturnArray);
        } else {
            foreach ($rankReturnArray as $rankDataArray) {
                $this->assertArrayHasKey("Queue", $rankDataArray, "Queue is missing or null");

                $this->assertArrayHasKey("Tier", $rankDataArray, "Tier is missing or null");
                $this->assertMatchesRegularExpression('/^[A-Za-z]+$/', $rankDataArray["Tier"], "Tier is not alphabetical");

                $this->assertArrayHasKey("Rank", $rankDataArray, "Rank is missing or null");
                $validRanks = array("I", "II", "III", "IV");
                $this->assertContains($rankDataArray["Rank"], $validRanks, "Rank is invalid");

                $this->assertArrayHasKey("LP", $rankDataArray, "LP is missing or null");
                $this->assertIsNumeric($rankDataArray["LP"], "LP is not numeric");
                $this->assertGreaterThanOrEqual(0, $rankDataArray["LP"], "LP is less than 0");
                $this->assertLessThanOrEqual(100, $rankDataArray["LP"], "LP is greater than 100");

                $this->assertArrayHasKey("Wins", $rankDataArray, "Wins is missing or null");
                $this->assertIsNumeric($rankDataArray["Wins"], "Wins is not numeric");
                $this->assertGreaterThanOrEqual(0, $rankDataArray["Wins"], "Wins is negative");

                $this->assertArrayHasKey("Losses", $rankDataArray, "Losses is missing or null");
                $this->assertIsNumeric($rankDataArray["Losses"], "Losses is not numeric");
                $this->assertGreaterThanOrEqual(0, $rankDataArray["Losses"], "Losses is negative");
            }
        }
    }

    public function testGetMatchIDs() {
        $matchIDArray = API::getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 100);

        $this->assertIsArray($matchIDArray, "Match ID array is not an array");
        $this->assertCount(100, $matchIDArray, "Match ID array does not have exactly 100 elements");
    
        foreach ($matchIDArray as $matchID) {
            $this->assertMatchesRegularExpression('/^EUW1_[A-Za-z0-9_-]{8,12}+$/', $matchID, "Match ID format is invalid");
        }

        $matchIDArray2 = API::getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 15);

        $this->assertIsArray($matchIDArray2, "Second Match ID array is not an array");
        $this->assertCount(15, $matchIDArray2, "Second Match ID array does not have exactly 15 elements");
    
        foreach ($matchIDArray2 as $matchID2) {
            $this->assertMatchesRegularExpression('/^EUW1_[A-Za-z0-9_-]{8,12}+$/', $matchID2, "Match ID format is invalid");
        }
    }

    public function testGetTeamByTeamID()
    {  
        $testGetTeam = API::getTeamByTeamID('test');

        $this->assertArrayHasKey('Status', $testGetTeam, 'Returnarray is missing key Status');
        $this->assertArrayHasKey('TeamID', $testGetTeam, 'Returnarray is missing key TeamID');
        $this->assertArrayHasKey('TournamentID', $testGetTeam, 'Returnarray is missing key TournamentID');
        $this->assertArrayHasKey('Name', $testGetTeam, 'Returnarray is missing key Name');
        $this->assertArrayHasKey('Tag', $testGetTeam, 'Returnarray is missing key Tag');
        $this->assertArrayHasKey('Icon', $testGetTeam, 'Returnarray is missing key Icon');
        $this->assertArrayHasKey('Tier', $testGetTeam, 'Returnarray is missing key Tier');
        $this->assertArrayHasKey('Captain', $testGetTeam, 'Returnarray is missing key Captain');
        $this->assertArrayHasKey('Players', $testGetTeam, 'Returnarray is missing key Players');
        $this->assertIsNumeric($testGetTeam['Status'], 'Status is not numeric');
        $this->assertTrue(isValidID($testGetTeam['TeamID']), 'TeamID is not a valid ID');
        $this->assertTrue(isValidPlayerName($testGetTeam['Name']), 'Teamname does not meet the criterias of validation for player names');
        $this->assertTrue(isValidPlayerTag($testGetTeam['Tag']), 'Teamtag does not meet the criterias of validation for player tags');
        $this->assertIsNumeric($testGetTeam['Icon'], 'Icon is not numeric');
        $this->assertIsNumeric($testGetTeam['Tier'], 'Tier is not numeric');
        $this->assertGreaterThanOrEqual(0, $testGetTeam['Tier'], 'Tier is not greater than or equal zero');
        $this->assertLessThanOrEqual(4, $testGetTeam['Tier'], 'Tier is not greater than or equal four');
        $this->assertTrue(isValidID($testGetTeam['Captain']), 'Captain is not a valid ID');
        $this->assertIsArray($testGetTeam['Players'], 'Players is not an array');
        foreach ($testGetTeam['Players'] as $player) {
            $this->assertIsArray($player, 'Player is not an array');
            $this->assertArrayHasKey('puuid', $player, 'Player is missing puuid');
            $this->assertArrayHasKey('position', $player, 'Player is missing position');
            $this->assertTrue(isValidID($player['puuid']), 'A players puuid is not considered a valid puuid');
            $this->assertTrue(isValidPosition($player['position']), 'A players position is not considered a valid position');
            $this->assertContains($player['role'], ['MEMBER', 'CAPTAIN'], 'A player is neither a member nor a captain');
        }
    }

    public function testDownloadMatchesByID() {
        $mdb = new MongoDBHelper();
        $testMatchId = API::getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 1)[0]; // EUW1_6877507628
        $downloadableFlag = false;
        if($mdb->findDocumentByField('matches', 'metadata.matchId', $testMatchId)["success"]) {
            if($mdb->deleteDocumentByField('matches', 'metadata.matchId', $testMatchId)["success"]){
                $downloadableFlag = true;
            } else {
                return;
            }
        } else {
            $downloadableFlag = true;
        }

        if($downloadableFlag) {
            $resultBoolean = API::downloadMatchesByID([$testMatchId], "PHPUnit");
            $this->assertNotFalse($resultBoolean, 'Downloading a match was not successful');
        }
    }

    public function testHandlePagePost() {        
        $successfulResult = API::handlePagePost('thenerdwork#nerdy');
        $failedResult = API::handlePagePost('nonexistentuser#wrong');

        $this->assertNotFalse($successfulResult, 'Handle of page posting should return 404 or a teamid');
        $this->assertEquals('404', $failedResult, 'Handle of wrong page posting should return 404');
    }
}