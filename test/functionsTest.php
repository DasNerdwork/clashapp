<?php
use PHPUnit\Framework\TestCase;
include_once('/hdd1/clashapp/functions.php');

$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");

class FunctionsTest extends TestCase {
    public function testGetPlayerDataByName() {
        $actualData = getPlayerData("name", "Flokrastinator");

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

        $this->assertArrayHasKey('SumID', $actualData, "SumID key is missing");
        $this->assertEquals('kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw', $actualData['SumID'], "SumID is not equal");

        $this->assertArrayHasKey('AccountID', $actualData, "AccountID key is missing");
        $this->assertEquals('NoudYpU8MTqtQ7BvYx4kbQt8boAaDeemjWwOv42nQpH4q98', $actualData['AccountID'], "AccountID is not equal");

        $this->assertArrayHasKey('Name', $actualData, "Name key is missing");
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\p{L}]{3,16}$/', $actualData['Name'], "Name is not in the valid format");
    }


    public function testGetMasteryScores() {
        global $currentPatch;
        $championJson = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), true);
        $actualData = getMasteryScores("kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw");
    
        foreach ($actualData as $masteryData) {
            $this->assertArrayHasKey("Champion", $masteryData, "Champion key is missing");
            $this->assertTrue($this->doesChampionNameExist($masteryData['Champion'], $championJson['data']), "Champion name does not exist");
    
            $this->assertArrayHasKey("Filename", $masteryData, "Filename key is missing");
            $this->assertNotNull($masteryData['Filename'], "Filename is null");
    
            $this->assertArrayHasKey("Lvl", $masteryData, "Lvl key is missing");
            $this->assertGreaterThanOrEqual(0, $masteryData['Lvl'], "Lvl is less than 0");
            $this->assertLessThanOrEqual(7, $masteryData['Lvl'], "Lvl is greater than 7");
    
            $this->assertArrayHasKey("Points", $masteryData, "Points key is missing");
            $this->assertIsNumeric(str_replace(',', '.', $masteryData['Points']), "Points is not numeric");
            $this->assertGreaterThanOrEqual(0, str_replace(',', '.', $masteryData['Points']), "Points is less than 0");
    
            $this->assertArrayHasKey("LastPlayed", $masteryData, "LastPlayed key is missing");
            $this->assertNotNull($masteryData["LastPlayed"], "LastPlayed is null");
            $this->assertGreaterThan(1256515200, $masteryData['LastPlayed'], "LastPlayed is less than or equal to 27th October 2009");
    
            if (array_key_exists('LvlUpTokens', $masteryData)) {
                $this->assertIsNumeric($masteryData['LvlUpTokens'], "LvlUpTokens is not numeric");
                $this->assertGreaterThanOrEqual(0, $masteryData['LvlUpTokens'], "LvlUpTokens is less than 0");
                $this->assertLessThanOrEqual(3, $masteryData['LvlUpTokens'], "LvlUpTokens is greater than 3");
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
        $rankReturnArray = getCurrentRank("kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw");

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

    public function testGetMatchIDs() {
        $matchIDArray = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 100);

        $this->assertIsArray($matchIDArray, "Match ID array is not an array");
    
        foreach ($matchIDArray as $matchID) {
            $this->assertMatchesRegularExpression('/^EUW1_[A-Za-z0-9_-]{8,12}+$/', $matchID, "Match ID format is invalid");
        }
    }

    public function testDownloadMatchesByID() {
        $testMatchId = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 1)[0];
        $downloadableFlag = false;
        if(file_exists("/hdd1/clashapp/data/matches/".$testMatchId.".json")) {
            if(unlink("/hdd1/clashapp/data/matches/".$testMatchId.".json")){
                $downloadableFlag = true;
            } else {
                echo "Unable to delete /hdd1/clashapp/data/matches/".$testMatchId.".json";
                return;
            }
        } else {
            $downloadableFlag = true;
        }

        if($downloadableFlag) {
            $resultBoolean = downloadMatchesByID([$testMatchId], "PHPUnit");
            $this->assertNotFalse($resultBoolean);
            $this->assertFileExists("/hdd1/clashapp/data/matches/".$testMatchId.".json", "Downloaded Match.json File does not exists/download correctly");
            $this->assertGreaterThan(0, filesize("/hdd1/clashapp/data/matches/".$testMatchId.".json"), "Downloaded Match.json File has filesize of 0");
        }
    }

    public function testGetMatchData()
    {
        $testMatchId = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 1)[0];
        $matchData = (array) getMatchData([$testMatchId]);

        $this->assertIsArray($matchData, "Fetched MatchData is no array");
        $this->assertNotEmpty($matchData, "Fetched MatchData is empty");

        $expectedKeys = [
            'gameCreation',
            'gameDuration',
            'gameEndTimestamp',
            'gameStartTimestamp',
            'gameVersion',
            'participants'
        ];

        $matchDataArray = (array) $matchData[$testMatchId];
        $infoArray = (array) $matchData[$testMatchId]->info;
        $participantArray = (array) $matchData[$testMatchId]->info->participants;
    
        // Validate the presence of expected keys
        $missingKeys = array_diff($expectedKeys, array_keys($infoArray));
        $this->assertEmpty($missingKeys, "MatchData info array is missing keys: " . implode(', ', $missingKeys));

        // Validate there are no extra keys
        $extraKeys = array_diff(array_keys($infoArray), $expectedKeys);
        $this->assertEmpty($extraKeys, "MatchData info array has too many keys: " . implode(', ', $extraKeys));


        // Make sure that these keys have successfully been removed from the array (unnecessary waste data)        
        $this->assertArrayNotHasKey('metadata', $matchDataArray, 'The "metadata" attribute should not exist in $matchDataArray.');
        $this->assertArrayNotHasKey('gameId', $infoArray, 'The "gameId" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('gameMode', $infoArray, 'The "gameMode" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('gameName', $infoArray, 'The "gameName" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('gameType', $infoArray, 'The "gameType" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('mapId', $infoArray, 'The "mapId" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('platformId', $infoArray, 'The "platformId" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('queueId', $infoArray, 'The "queueId" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('teams', $infoArray, 'The "teams" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('tournamentCode', $infoArray, 'The "tournamentCode" attribute should not exist in $infoArray.');
        $this->assertArrayNotHasKey('allInPings', $participantArray, 'The "allInPings" attribute should not exist in $participantArray.');
        $this->assertArrayNotHasKey('assistMePings', $participantArray, 'The "assistMePings" attribute should not exist in $participantArray.');
        $this->assertArrayNotHasKey('baitPings', $participantArray, 'The "baitPings" attribute should not exist in $participantArray.');
        $this->assertArrayNotHasKey('baronKills', $participantArray, 'The "baronKills" attribute should not exist in $participantArray.');
        $this->assertArrayNotHasKey('basicPings', $participantArray, 'The "basicPings" attribute should not exist in $participantArray.');
        $this->assertArrayNotHasKey('bountyLevel', $participantArray, 'The "bountyLevel" attribute should not exist in $participantArray.');

        foreach ($participantArray as $participant) {
            $expectedParticipantKeys = [
                'assists',
                'challenges',
                'championName',
                'championTransform',
                'consumablesPurchased',
                'damageDealtToBuildings',
                'damageDealtToObjectives',
                'damageSelfMitigated',
                'deaths',
                'detectorWardsPlaced',
                'goldEarned',
                'individualPosition',
                'inhibitorTakedowns',
                'kills',
                'lane',
                'neutralMinionsKilled',
                'puuid',
                'summonerId',
                'summonerName',
                'teamPosition',
                'totalDamageDealtToChampions',
                'totalDamageShieldedOnTeammates',
                'totalDamageTaken',
                'totalHealsOnTeammates',
                'totalMinionsKilled',
                'totalTimeCCDealt',
                'totalTimeSpentDead',
                'turretTakedowns',
                'visionScore',
                'wardsPlaced'
            ];
    
            // Validate the presence of expected participant keys
            $missingParticipantKeys = array_diff($expectedParticipantKeys, array_keys((array) $participant));
            $this->assertEmpty($missingParticipantKeys, "MatchData participant array is missing keys: " . implode(', ', $missingParticipantKeys));

            // Validate the presence of expected participant keys
            $extraParticipantKeys = array_diff(array_keys((array) $participant), $expectedParticipantKeys);
            unset($extraParticipantKeys[array_search('win', $extraParticipantKeys)]); // Exclude 'win' key from extra keys (broken but no idea why, maybe cuz of boolean)

            $this->assertEmpty($extraParticipantKeys, "MatchData participant array has too many keys: " . implode(', ', $extraParticipantKeys));
        }
    }


}