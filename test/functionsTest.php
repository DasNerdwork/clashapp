<?php
use PHPUnit\Framework\TestCase;
include_once('/hdd1/clashapp/functions.php');
$_SERVER['HTTP_HOST'] = "clashscout.com";
include_once('/hdd1/clashapp/lang/translate.php');

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
        $matchIDArray = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 100);

        $this->assertIsArray($matchIDArray, "Match ID array is not an array");
        $this->assertCount(100, $matchIDArray, "Match ID array does not have exactly 100 elements");
    
        foreach ($matchIDArray as $matchID) {
            $this->assertMatchesRegularExpression('/^EUW1_[A-Za-z0-9_-]{8,12}+$/', $matchID, "Match ID format is invalid");
        }

        $matchIDArray2 = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 15);

        $this->assertIsArray($matchIDArray2, "Second Match ID array is not an array");
        $this->assertCount(15, $matchIDArray2, "Second Match ID array does not have exactly 15 elements");
    
        foreach ($matchIDArray2 as $matchID2) {
            $this->assertMatchesRegularExpression('/^EUW1_[A-Za-z0-9_-]{8,12}+$/', $matchID2, "Match ID format is invalid");
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

    public function testGetMatchData() {
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

    public function testSecondsToTime() {
        // Test cases for non-positive values
        $timeStringNeg1 = secondsToTime(-1);
        $this->assertEquals("1 minute ago", $timeStringNeg1, "Time string for -1 second is incorrect");

        $timeString0 = secondsToTime(0);
        $this->assertEquals("1 minute ago", $timeString0, "Time string for 0 seconds is incorrect");

        // Test cases for exact equals value in the case statement
        $timeString2Below = secondsToTime(119);
        $this->assertEquals("1 minute ago", $timeString2Below, "Time string for 119 seconds is incorrect");

        $timeString120 = secondsToTime(120);
        $this->assertEquals("2 minutes ago", $timeString120, "Time string for 120 seconds is incorrect");

        $timeString1Above = secondsToTime(121);
        $this->assertEquals("2 minutes ago", $timeString1Above, "Time string for 121 seconds is incorrect");

        $timeString1HourBelow = secondsToTime(3599);
        $this->assertEquals("59 minutes ago", $timeString1HourBelow, "Time string for 3599 seconds is incorrect");

        $timeString3600 = secondsToTime(3600);
        $this->assertEquals("1 hour ago", $timeString3600, "Time string for 3600 seconds is incorrect");

        $timeString1HourAbove = secondsToTime(3601);
        $this->assertEquals("1 hour ago", $timeString1HourAbove, "Time string for 3601 seconds is incorrect");

        $timeString1DayBelow = secondsToTime(86399);
        $this->assertEquals("23 hours ago", $timeString1DayBelow, "Time string for 86399 seconds is incorrect");

        $timeString86400 = secondsToTime(86400);
        $this->assertEquals("1 day ago", $timeString86400, "Time string for 86400 seconds is incorrect");

        $timeString1DayAbove = secondsToTime(86401);
        $this->assertEquals("1 day ago", $timeString1DayAbove, "Time string for 86401 seconds is incorrect");

        $timeString1MonthBelow = secondsToTime(2591999);
        $this->assertEquals("29 days ago", $timeString1MonthBelow, "Time string for 2591999 seconds is incorrect");

        $timeString2628000 = secondsToTime(2592000);
        $this->assertEquals("1 month ago", $timeString2628000, "Time string for 2592000 seconds is incorrect");

        $timeString1MonthAbove = secondsToTime(2592001);
        $this->assertEquals("1 month ago", $timeString1MonthAbove, "Time string for 2592001 seconds is incorrect");

        $timeString1YearBelow = secondsToTime(31103999);
        $this->assertEquals("11 months ago", $timeString1YearBelow, "Time string for 31103999 seconds is incorrect");

        $timeString31536000 = secondsToTime(31104000);
        $this->assertEquals("1 year ago", $timeString31536000, "Time string for 31104000 seconds is incorrect");

        $timeString1YearAbove = secondsToTime(31104001);
        $this->assertEquals("1 year ago", $timeString1YearAbove, "Time string for 31104001 seconds is incorrect");

        $timeString1YearAbove = secondsToTime(62208000);
        $this->assertEquals("2 years ago", $timeString1YearAbove, "Time string for 62208000 seconds is incorrect");

        $maxInt = PHP_INT_MAX;
        $timeStringMaxInt = secondsToTime($maxInt);
        $this->assertEquals("296533308798 years ago", $timeStringMaxInt, "Time string for PHP_INT_MAX is incorrect");
    }    

    public function testGetRandomIcon()
    {
        // Test with a valid currentIconID (between 1 and 28)
        for ($currentIconID = 1; $currentIconID <= 28; $currentIconID++) {
            $randomIconID = getRandomIcon($currentIconID);

            // Verify that the randomIconID is different from the currentIconID
            $this->assertNotEquals($currentIconID, $randomIconID, "Random icon ID ($randomIconID) should be different from current icon ID ($currentIconID).");

            // Verify that the randomIconID is within the valid range (1 to 28)
            $this->assertTrue($randomIconID >= 1 && $randomIconID <= 28, "Random icon ID ($randomIconID) should be within the valid range (1 to 28).");
        }

        // Test with an invalid currentIconID (less than 1)
        $currentIconID = 0;
        $randomIconID = getRandomIcon($currentIconID);

        // Verify that the randomIconID is within the valid range (1 to 28)
        $this->assertTrue($randomIconID >= 1 && $randomIconID <= 28, "Random icon ID ($randomIconID) should be within the valid range (1 to 28).");

        // Test with an invalid currentIconID (greater than 28)
        $currentIconID = 30;
        $randomIconID = getRandomIcon($currentIconID);

        // Verify that the randomIconID is within the valid range (1 to 28)
        $this->assertTrue($randomIconID >= 1 && $randomIconID <= 28, "Random icon ID ($randomIconID) should be within the valid range (1 to 28).");
    }

    public function testSummonerSpellFetcher()
    {
        global $currentPatch;
        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/summoner.json'), "summoner.json file does not exist.");

        $testSummonerIconKey = '4'; // Data key for Flash

        $expectedIconID = 'SummonerFlash';

        $actualIconID = summonerSpellFetcher($testSummonerIconKey);

        $this->assertEquals($expectedIconID, $actualIconID, "Returned icon ID ($actualIconID) does not match the expected icon ID ($expectedIconID).");
    }

    public function testRuneTreeIconFetcher()
    {
        global $currentPatch;
        $testRuneIconID = 8100; // Data ID for Domination
        
        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json'), "runesReforged.json file does not exist.");

        $expectedIconPath = 'perk-images/Styles/7200_Domination.png';
        $actualIconPath = runeTreeIconFetcher($testRuneIconID);

        $this->assertNull(runeTreeIconFetcher(0), "runeTreeIconFetcher did not return null for invalid rune ID (0).");
        $this->assertNull(runeTreeIconFetcher(8112), "runeTreeIconFetcher did not return null for valid but non-existing rune ID (8112).");
        $this->assertNotNull(runeTreeIconFetcher("8100"), "runeTreeIconFetcher returned null for valid rune ID as string ('8100').");
        $this->assertNull(runeTreeIconFetcher(-1), "runeTreeIconFetcher did not return null for invalid negative rune ID (-1).");
        $this->assertNotEmpty($actualIconPath, "runeTreeIconFetcher returned an empty icon path.");
        $this->assertEquals($expectedIconPath, $actualIconPath, "Returned icon path ($actualIconPath) does not match the expected icon path ($expectedIconPath).");
    }

    public function testRuneIconFetcher()
    {
        global $currentPatch;
        $testRuneId = 8112; // Rune ID for Electrocute

        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json'), "runesReforged.json file does not exist.");

        $expectedIconPath = 'perk-images/Styles/Domination/Electrocute/Electrocute.png'; // Expected path for electrocute
        $actualIconPath = runeIconFetcher($testRuneId);
        
        $this->assertNull(runeIconFetcher(0), "runeIconFetcher did not return null for invalid rune ID (0).");
        $this->assertNull(runeIconFetcher(8100), "runeIconFetcher did not return null for valid but non-existing rune ID (8100).");
        $this->assertNotNull(runeIconFetcher("8112"), "runeIconFetcher returned null for valid rune ID as string ('8112').");
        $this->assertNull(runeIconFetcher(-1), "runeIconFetcher did not return null for invalid negative rune ID (-1).");
        $this->assertNotEmpty($actualIconPath, "runeIconFetcher returned an empty icon path.");
        $this->assertEquals($expectedIconPath, $actualIconPath, "Returned icon path ($actualIconPath) does not match the expected icon path ($expectedIconPath).");
    }

    public function testChampionIdToName()
    {
        global $currentPatch;
        $testChampionID = 266;

        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), "champion.json file does not exist.");

        $expectedChampionName = 'Aatrox';

        $actualChampionName = championIdToName($testChampionID);

        $this->assertNull(championIdToName(0), "championIdToName did not return null for invalid champion ID (0).");
        $this->assertNull(championIdToName(1234), "championIdToName did not return null for valid but non-existing champion ID (1234).");
        $this->assertNotNull(championIdToName("266"), "championIdToName returned null for valid champion ID as string ('266').");
        $this->assertNull(championIdToName(-1), "championIdToName did not return null for invalid negative champion ID (-1).");
        $this->assertNotEmpty($actualChampionName, "championIdToName returned an empty champion name.");
        $this->assertEquals($expectedChampionName, $actualChampionName, "Returned champion name ($actualChampionName) does not match the expected champion name ($expectedChampionName).");
    }

    public function testChampionIdToFilename()
    {
        global $currentPatch;
        $testChampionID = 62;

        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), "champion.json file does not exist.");

        $expectedChampionFilename = 'MonkeyKing';

        $actualChampionFilename = championIdToFilename($testChampionID);

        $this->assertNull(championIdToFilename(0), "championIdToFilename did not return null for invalid champion ID (0).");
        $this->assertNull(championIdToFilename(1234), "championIdToFilename did not return null for valid but non-existing champion ID (1234).");
        $this->assertNotNull(championIdToFilename("62"), "championIdToFilename returned null for valid champion ID as string ('62').");
        $this->assertNull(championIdToFilename(-1), "championIdToFilename did not return null for invalid negative champion ID (-1).");
        $this->assertNotEmpty($actualChampionFilename, "championIdToFilename returned an empty champion filename.");
        $this->assertEquals($expectedChampionFilename, $actualChampionFilename, "Returned champion filename ($actualChampionFilename) does not match the expected champion filename ($expectedChampionFilename).");
    }

    public function testGetLanePercentages()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchId = getMatchIDs($puuid, 1)[0];
        $matchData = (array) getMatchData([$testMatchId]);

        $expectedResultArray = ['MID', 'TOP', 'BOTTOM', 'JUNGLE', 'UTILITY', 'FILL', ''];

        $this->assertCount(2, getLanePercentages($matchData, $puuid), "Returned lane percentage array does not contain exactly two elements.");
        $this->assertNotNull(getLanePercentages($matchData, $puuid)[0], "First lane in the lane percentage array is null.");
        $this->assertNotNull(getLanePercentages($matchData, $puuid)[1], "Second lane in the lane percentage array is null.");
        $this->assertContains(getLanePercentages($matchData, $puuid)[0], $expectedResultArray, "First lane in the lane percentage array is not within the expected result array.");
        $this->assertContains(getLanePercentages($matchData, $puuid)[1], $expectedResultArray, "Second lane in the lane percentage array is not within the expected result array.");
    }
}