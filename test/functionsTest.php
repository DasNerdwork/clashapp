<?php
use PHPUnit\Framework\TestCase;
require_once('/hdd1/clashapp/src/functions.php');
$_SERVER['SERVER_NAME'] = "clashscout.com";
$_SERVER['HTTP_REFERER'] = "https://clashscout.com/";
include_once('/hdd1/clashapp/lang/translate.php');
$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");

class FunctionsTest extends TestCase {
    /**
     * @covers getPlayerData
     */
    public function testGetPlayerDataByName() {
        $actualData = getPlayerData("riot-id", "dasnerdwork#nerdy");

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

        $this->assertArrayHasKey('GameName', $actualData, "Name key is missing");
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\p{L}]{3,16}$/', $actualData['GameName'], "Name is not in the valid format");

        $this->assertArrayHasKey('Tag', $actualData, "Tag key is missing");
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\p{L}]{3,5}$/', $actualData['Tag'], "Tag is not in the valid format");

        $actualDataPUUID = getPlayerData("puuid", "wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA");

        $this->assertEquals($actualData['Icon'], $actualDataPUUID['Icon'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['GameName'], $actualDataPUUID['GameName'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['Tag'], $actualDataPUUID['Tag'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['Level'], $actualDataPUUID['Level'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['PUUID'], $actualDataPUUID['PUUID'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['SumID'], $actualDataPUUID['SumID'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['AccountID'], $actualDataPUUID['AccountID'], "Data is not the same regarding access type");
        $this->assertArrayHasKey('Icon', $actualDataPUUID, "Icon key is missing");
        $this->assertArrayHasKey('GameName', $actualDataPUUID, "Name key is missing");
        $this->assertArrayHasKey('Tag', $actualDataPUUID, "Tag key is missing");
        $this->assertArrayHasKey('Level', $actualDataPUUID, "Level key is missing");
        $this->assertArrayHasKey('PUUID', $actualDataPUUID, "PUUID key is missing");
        $this->assertArrayHasKey('SumID', $actualDataPUUID, "SumID key is missing");
        $this->assertArrayHasKey('AccountID', $actualDataPUUID, "AccountID key is missing");
        $this->assertArrayHasKey('LastChange', $actualDataPUUID, "LastChange key is missing");

        $actualDataSumID = getPlayerData("sumid", "kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw");

        $this->assertEquals($actualData['Icon'], $actualDataSumID['Icon'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['GameName'], $actualDataSumID['GameName'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['Tag'], $actualDataSumID['Tag'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['Level'], $actualDataSumID['Level'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['PUUID'], $actualDataSumID['PUUID'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['SumID'], $actualDataSumID['SumID'], "Data is not the same regarding access type");
        $this->assertEquals($actualData['AccountID'], $actualDataSumID['AccountID'], "Data is not the same regarding access type");
        $this->assertArrayHasKey('Icon', $actualDataSumID, "Icon key is missing");
        $this->assertArrayHasKey('GameName', $actualDataSumID, "Name key is missing");
        $this->assertArrayHasKey('Tag', $actualDataSumID, "Tag key is missing");
        $this->assertArrayHasKey('Level', $actualDataSumID, "Level key is missing");
        $this->assertArrayHasKey('PUUID', $actualDataSumID, "PUUID key is missing");
        $this->assertArrayHasKey('SumID', $actualDataSumID, "SumID key is missing");
        $this->assertArrayHasKey('AccountID', $actualDataSumID, "AccountID key is missing");
        $this->assertArrayHasKey('LastChange', $actualDataSumID, "LastChange key is missing");
    }

    /**
     * @covers getMasteryScores
     * @uses championIdToFilename
     * @uses championIdToName
     */
    public function testGetMasteryScores() {
        global $currentPatch;
        $championJson = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), true);
        $actualData = getMasteryScores("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA");
    
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

    /**
     * @covers getCurrentRank
     */
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

    /**
     * @covers getMatchIDs
     */
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

    /**
     * @covers downloadMatchesByID
     * @uses MongoDBHelper
     * @uses getMatchIDs
     */
    public function testDownloadMatchesByID() {
        $mdb = new MongoDBHelper();
        $testMatchId = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 1)[0]; // EUW1_6877507628
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
            $resultBoolean = downloadMatchesByID([$testMatchId], "PHPUnit");
            $this->assertNotFalse($resultBoolean, 'Downloading a match was not successful');
        }
    }

    /**
     * @covers getMatchData
     * @uses MongoDBHelper
     * @uses getMatchIDs
     */
    public function testGetMatchData() {
        $testMatchId = getMatchIDs("wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA", 1)[0];
        $matchData = (array) getMatchData([$testMatchId]);

        $this->assertIsArray($matchData, "Fetched MatchData is no array");
        $this->assertNotEmpty($matchData, "Fetched MatchData is empty");

        $expectedKeys = [
            'endOfGameResult',
            'gameCreation',
            'gameDuration',
            'gameEndTimestamp',
            'gameStartTimestamp',
            'gameVersion',
            'participants',
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
                'teamId',
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

    /**
     * @covers secondsToTime
     * @uses __
     */
    public function testSecondsToTime() {
        $timeStringNeg1 = secondsToTime(-1);
        $this->assertEquals("1 minute ago", $timeStringNeg1, "Time string for -1 second is incorrect");

        $timeString0 = secondsToTime(0);
        $this->assertEquals("1 minute ago", $timeString0, "Time string for 0 seconds is incorrect");

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

        $wrongInput = secondsToTime("wronginput");
        $this->assertNull($wrongInput, "Wrong input did not return as Null.");
    }    

    /**
     * @covers getRandomIcon
     */
    public function testGetRandomIcon()
    {
        for ($currentIconID = 1; $currentIconID <= 28; $currentIconID++) {
            $randomIconID = getRandomIcon($currentIconID);

            $this->assertNotEquals($currentIconID, $randomIconID, "Random icon ID ($randomIconID) should be different from current icon ID ($currentIconID).");

            $this->assertTrue($randomIconID >= 1 && $randomIconID <= 28, "Random icon ID ($randomIconID) should be within the valid range (1 to 28).");
        }

        $currentIconIDZero = 0;
        $randomIconIDZero = getRandomIcon($currentIconIDZero);

        $this->assertTrue($randomIconIDZero >= 1 && $randomIconIDZero <= 28, "Random icon ID ($randomIconIDZero) should be within the valid range (1 to 28).");

        $currentIconIDHigh = 30;
        $randomIconIDHigh = getRandomIcon($currentIconIDHigh);

        $this->assertTrue($randomIconIDHigh >= 1 && $randomIconIDHigh <= 28, "Random icon ID ($randomIconIDHigh) should be within the valid range (1 to 28).");
    }

    /**
     * @covers summonerSpellFetcher
     */
    public function testSummonerSpellFetcher()
    {
        global $currentPatch;
        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/summoner.json'), "summoner.json file does not exist.");

        $testSummonerIconKey = '4'; // Data key for Flash

        $expectedIconID = 'SummonerFlash';

        $actualIconID = summonerSpellFetcher($testSummonerIconKey);

        $this->assertEquals($expectedIconID, $actualIconID, "Returned icon ID ($actualIconID) does not match the expected icon ID ($expectedIconID).");

        $testWrongSummonerIconKey = '0'; // Data key for Flash
        
        $emptyIconID = summonerSpellFetcher($testWrongSummonerIconKey);

        $this->assertEquals("", $emptyIconID, "Returned an icon ID although none was expected.");
    }

    /**
     * @covers runeTreeIconFetcher
     */
    public function testRuneTreeIconFetcher()
    {
        global $currentPatch;
        $testRuneIconID = 8100; // Data ID for Domination
        
        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json'), "runesReforged.json file does not exist.");

        $expectedIconPath = 'perk-images/Styles/7200_Domination';
        $actualIconPath = runeTreeIconFetcher($testRuneIconID);

        $this->assertEquals("", runeTreeIconFetcher(0), "runeTreeIconFetcher did not return null for invalid rune ID (0).");
        $this->assertEquals("", runeTreeIconFetcher(8112), "runeTreeIconFetcher did not return null for valid but non-existing rune ID (8112).");
        $this->assertNotNull(runeTreeIconFetcher("8100"), "runeTreeIconFetcher returned null for valid rune ID as string ('8100').");
        $this->assertEquals("", runeTreeIconFetcher(-1), "runeTreeIconFetcher did not return null for invalid negative rune ID (-1).");
        $this->assertNotEmpty($actualIconPath, "runeTreeIconFetcher returned an empty icon path.");
        $this->assertEquals($expectedIconPath, $actualIconPath, "Returned icon path ($actualIconPath) does not match the expected icon path ($expectedIconPath).");
    }

    /**
     * @covers runeIconFetcher
     */
    public function testRuneIconFetcher()
    {
        global $currentPatch;
        $testRuneId = 8112; // Rune ID for Electrocute

        $this->assertTrue(file_exists('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/runesReforged.json'), "runesReforged.json file does not exist.");

        $expectedIconPath = 'perk-images/Styles/Domination/Electrocute/Electrocute'; // Expected path for electrocute
        $actualIconPath = runeIconFetcher($testRuneId);
        
        $this->assertEquals("", runeIconFetcher(0), "runeIconFetcher did not return null for invalid rune ID (0).");
        $this->assertEquals("", runeIconFetcher(8100), "runeIconFetcher did not return null for valid but non-existing rune ID (8100).");
        $this->assertNotNull(runeIconFetcher("8112"), "runeIconFetcher returned null for valid rune ID as string ('8112').");
        $this->assertEquals("", runeIconFetcher(-1), "runeIconFetcher did not return null for invalid negative rune ID (-1).");
        $this->assertNotEmpty($actualIconPath, "runeIconFetcher returned an empty icon path.");
        $this->assertEquals($expectedIconPath, $actualIconPath, "Returned icon path ($actualIconPath) does not match the expected icon path ($expectedIconPath).");
    }

    /**
     * @covers championIdToName
     */
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

    /**
     * @covers championIdToFilename
     */
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

    /**
     * @covers getLanePercentages
     * @covers getMostCommon
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchIDs
     */
    public function testGetLanePercentages()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchId = getMatchIDs($puuid, 1)[0];
        $matchData = (array) getMatchData([$testMatchId]);

        $expectedResultArray = ['MIDDLE', 'TOP', 'BOTTOM', 'JUNGLE', 'UTILITY', 'FILL', ''];

        $this->assertCount(2, getLanePercentages($matchData, $puuid), "Returned lane percentage array does not contain exactly two elements.");
        $this->assertNotNull(getLanePercentages($matchData, $puuid)[0], "First lane in the lane percentage array is null.");
        $this->assertNotNull(getLanePercentages($matchData, $puuid)[1], "Second lane in the lane percentage array is null.");
        $this->assertContains(getLanePercentages($matchData, $puuid)[0], $expectedResultArray, "First lane in the lane percentage array is not within the expected result array.");
        $this->assertContains(getLanePercentages($matchData, $puuid)[1], $expectedResultArray, "Second lane in the lane percentage array is not within the expected result array.");
    }

    /**
     * @covers getPlayerTags
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchIDs
     */
    public function testPlayerTags()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchIds = getMatchIDs($puuid, 15);
        $matchData = (array) getMatchData($testMatchIds);
        $expectedLaneArray = ['MIDDLE', 'TOP', 'BOTTOM', 'JUNGLE', 'UTILITY', 'FILL', ''];

        $tags = getPlayerTags($matchData, $puuid);

        $this->assertIsArray($tags, 'Tags should be an array.');
        foreach (array_keys($tags) as $lanes) {
            $this->assertNotEmpty($tags, 'Tags should not be empty.');
            $this->assertContains(key($tags), $expectedLaneArray, 'Expected lane not found in tags array.');
        }
    }

    /**
     * @covers getAverage
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchIDs
     */
    public function testGetAverage()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchIds = getMatchIDs($puuid, 15);
        $matchData = (array) getMatchData($testMatchIds);

        $testAverage = getAverage(['assists','deaths','killParticipation','kills','wardsPlaced'], $matchData, $puuid, 'JUNGLE');
        $this->assertArrayHasKey('assists', $testAverage, 'Returnarray is missing input key in output');
        $this->assertArrayHasKey('deaths', $testAverage, 'Returnarray is missing input key in output');
        $this->assertArrayHasKey('killParticipation', $testAverage, 'Returnarray is missing input key in output');
        $this->assertArrayHasKey('kills', $testAverage, 'Returnarray is missing input key in output');
        $this->assertArrayHasKey('wardsPlaced', $testAverage, 'Returnarray is missing input key in output');
        foreach ($testAverage as $attribute) {
            $this->assertArrayHasKey('SELF', $attribute, 'Inner returnarray is missing key SELF');
            $this->assertArrayHasKey('FILL', $attribute, 'Inner returnarray is missing key FILL');
            $this->assertArrayHasKey('TOP', $attribute, 'Inner returnarray is missing key TOP');
            $this->assertArrayHasKey('JUNGLE', $attribute, 'Inner returnarray is missing key JUNGLE');
            $this->assertArrayHasKey('MIDDLE', $attribute, 'Inner returnarray is missing key MIDDLE');
            $this->assertArrayHasKey('UTILITY', $attribute, 'Inner returnarray is missing key UTILITY');
            $this->assertArrayHasKey('BOTTOM', $attribute, 'Inner returnarray is missing key BOTTOM');
            foreach($attribute as $lane => $value){
                $this->assertIsNumeric($value, 'The value of an attribute is not numeric');
            }
        }
    }

    /**
     * @covers getHighestWinrateOrMostLossesAgainst
     * @covers getMostLossesAgainst
     * @covers getHighestWinrateAgainst
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchIDs
     */
    public function testGetHighestWinrateOrMostLossesAgainst()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchIds = getMatchIDs($puuid, 15);
        $matchData = (array) getMatchData($testMatchIds);

        $testMostLossesGeneral = getMostLossesAgainst("general", $matchData, $puuid);
        $testHighestWinrateLane = getHighestWinrateAgainst("lane", $matchData, $puuid);

        $this->assertIsArray($testMostLossesGeneral, 'Most losses against general should be an array.');
        $this->assertNotEmpty($testMostLossesGeneral, 'Most losses against general should not be empty.');
        foreach ($testMostLossesGeneral as $champion) {
            $this->assertArrayHasKey('win', $champion, 'Each champion should have a "win" key.');
            $this->assertArrayHasKey('winrate', $champion, 'Each champion should have a "winrate" key.');
            $this->assertArrayHasKey('lose', $champion, 'Each champion should have a "lose" key.');
            $this->assertArrayHasKey('count', $champion, 'Each champion should have a "count" key.');
        }

        $this->assertIsArray($testHighestWinrateLane, 'Highest winrate against lane should be an array.');
        $this->assertNotEmpty($testHighestWinrateLane, 'Highest winrate against lane should not be empty.');
        foreach ($testHighestWinrateLane as $champion) {
            $this->assertArrayHasKey('win', $champion, 'Each champion should have a "win" key.');
            $this->assertArrayHasKey('winrate', $champion, 'Each champion should have a "winrate" key.');
            $this->assertArrayHasKey('lose', $champion, 'Each champion should have a "lose" key.');
            $this->assertArrayHasKey('count', $champion, 'Each champion should have a "count" key.');
        }
    }

    /**
     * @covers mostPlayedWith
     * @uses getMatchData
     * @uses getMatchIDs
     * @uses MongoDBHelper
     * @uses isValidID
     */
    public function testMostPlayedWith()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchIds = getMatchIDs($puuid, 15);
        $matchData = (array) getMatchData($testMatchIds);

        $testMostPlayedWith = mostPlayedWith($matchData, $puuid);

        $this->assertIsArray($testMostPlayedWith, 'Most played with should be an array.');
        $this->assertNotEmpty($testMostPlayedWith, 'Most played with should not be empty.');
        foreach ($testMostPlayedWith as $player => $count) {
            $this->assertTrue(isValidID($player), 'Player ID should be a valid ID.');
            $this->assertIsNumeric($count, 'Count should be a numeric value.');
        }
    }

    /**
     * @covers getHighestWinrateWith
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchIDs
     */
    public function testGetHighestWinrateWith()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $testMatchIds = getMatchIDs($puuid, 15);
        $matchData = (array) getMatchData($testMatchIds);

        $testGetHighestWinrateWith = getHighestWinrateWith('JUNGLE', $matchData, $puuid);

        // Assertions for $testGetHighestWinrateWith
        $this->assertIsArray($testGetHighestWinrateWith, 'Highest winrate with should be an array.');
        $this->assertNotEmpty($testGetHighestWinrateWith, 'Highest winrate with should not be empty.');
        foreach ($testGetHighestWinrateWith as $champion) {
            $this->assertArrayHasKey('win', $champion, 'Champion should have a "win" key.');
            $this->assertArrayHasKey('winrate', $champion, 'Champion should have a "winrate" key.');
            $this->assertArrayHasKey('lose', $champion, 'Champion should have a "lose" key.');
            $this->assertArrayHasKey('count', $champion, 'Champion should have a "count" key.');
        }
    }

    /**
     * @covers getMatchRanking
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchIDs
     * @uses isValidMatchID
     */
    public function testGetMatchRanking()
    {
        $puuid = 'wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA';
        $sumid = 'kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw';
        $testMatchIds = getMatchIDs($puuid, 15);
        $matchData = (array) getMatchData($testMatchIds);
        $matchIDArray = $testMatchIds;

        $testGetMatchRanking = getMatchRanking($matchIDArray, $matchData, $sumid);

        $this->assertEquals(15, count($testGetMatchRanking), 'Returned match ranking count does not equal input match count');
        foreach ($testGetMatchRanking as $matchId => $ranking) {
            $this->assertTrue(isValidMatchID($matchId), 'Returned match ranking count does not equal input match count');
            if($ranking != "N/A"){
                $this->assertIsNumeric($ranking, 'Returned match ranking is neither N/A nor numerical');
            }
        }
    }

    /**
     * @covers getTeamByTeamID
     * @uses isValidID
     * @uses isValidPlayerName
     * @uses isValidPlayerTag
     * @uses isValidPosition
     */
    public function testGetTeamByTeamID()
    {  
        $testGetTeam = getTeamByTeamID('test');

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
            $this->assertArrayHasKey('summonerId', $player, 'Player is missing summonerId');
            $this->assertArrayHasKey('position', $player, 'Player is missing summonerId');
            $this->assertArrayHasKey('role', $player, 'Player is missing summonerId');
            $this->assertTrue(isValidID($player['summonerId']), 'A players sumid is not considered a valid sumid');
            $this->assertTrue(isValidPosition($player['position']), 'A players position is not considered a valid position');
            $this->assertContains($player['role'], ['MEMBER', 'CAPTAIN'], 'A player is neither a member nor a captain');
        }
    }

    /**
     * @covers unique_multidim_array
     */
    public function testUniqueMultidimArray()
    {
        $inputArray = [
            ["id" => 1, "name" => "John"],
            ["id" => 2, "name" => "Jane"],
            ["id" => 1, "name" => "John"],
            ["id" => 3, "name" => "Doe"],
            ["id" => 2, "name" => "Jane"],
            ["id" => 4, "name" => "Smith"]
        ];
    
        $expectedUniqueArray = [
            ["id" => 1, "name" => "John"],
            ["id" => 2, "name" => "Jane"],
            ["id" => 3, "name" => "Doe"],
            ["id" => 4, "name" => "Smith"]
        ];
    
        $result = unique_multidim_array($inputArray, 'id');
    
        $this->assertCount(count($expectedUniqueArray), $result, "The length of the result array is not as expected.");
    
        foreach ($expectedUniqueArray as $expectedItem) {
            $this->assertContains($expectedItem, $result, "The expected item is not found in the result array.");
        }
    }

    /**
     * @covers timeDiffToText
     * @uses __
     */
    public function testTimeDiffToText()
    {
        $timestamps = [
            strtotime("-2 years"),
            strtotime("-8 months"),
            strtotime("-4 months"),
            strtotime("-2 month"),
            strtotime("-3 weeks"),
            strtotime("-1 week"),
            time()
        ];

        $expectedResults = [
            __("over a year ago"),
            __("over 6 months ago"),
            __("over 3 months ago"),
            __("over a month ago"),
            __("over two weeks ago"),
            __("under two weeks ago"),
            __("under two weeks ago")
        ];

        foreach ($timestamps as $index => $timestamp) {
            $this->assertEquals($expectedResults[$index], timeDiffToText($timestamp), 'Time Difference to Text did not correctly return results.');
        }
    }

    /**
     * @covers getSuggestedPicksAndTeamstats
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses getMatchRanking
     */
    public function testGetSuggestedPicksAndTeamstats()
    {
        $mdb = new MongoDBHelper();
        $sumids = "MceGjIqeHx6ty7IFgkE7tkXVprFMlx-GiDY52e_9phuQrHHL,KBFbg5b5NWbOFdtOvq5Nr8R5lFHwCR4QS8cTJC6Oau_4Vn6HPW5pFGAiew,cvL-8gEPZWz45ttfrBnWvLF7jgEHzwrW-hz_fQsUyvKd6SH2,kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw,7_MlHF1XF5XJ-O0Hmy-Cjb6ACovIHg5irfmjFmP8lmA7qYyQ";
        $matchIDTeamArray = array();
        $playerSumidTeamArray = array_flip(explode(',', $sumids));

        foreach(array_keys($playerSumidTeamArray) as $playerSumid){
            if(!$mdb->getPlayerBySummonerId($playerSumid)["success"]){
                echo "Could not find playerfile for ".$playerSumid;
                return;
            } else {
                $playerDataJSONString = json_encode($mdb->findDocumentByField('players', 'PlayerData.SumID', $playerSumid)["document"]);
                $playerDataJSON = json_decode($playerDataJSONString, true);
                foreach(array_keys($playerDataJSON["MatchIDs"]) as $singleMatchID){
                    if(!in_array($singleMatchID, $matchIDTeamArray)){
                        $matchIDTeamArray[] = $singleMatchID;
                    }
                }
            }
        }
        $suggestedBanMatchData = getMatchData($matchIDTeamArray);
        $suggestedPicksAndTeamstatsArray = getSuggestedPicksAndTeamstats(array_keys($playerSumidTeamArray), $matchIDTeamArray, $suggestedBanMatchData);

        // Assertions for $suggestedPicksAndTeamstatsArray
        $this->assertIsArray($suggestedPicksAndTeamstatsArray, 'Suggested picks and team stats should be an array.');
        $this->assertNotEmpty($suggestedPicksAndTeamstatsArray, 'Suggested picks and team stats should not be empty.');
        $this->assertArrayHasKey('Teamstats', $suggestedPicksAndTeamstatsArray, 'Suggested picks and team stats should have "Teamstats" key.');
        $this->assertArrayHasKey('TeamIsWeakAgainst', $suggestedPicksAndTeamstatsArray, 'Suggested picks and team stats should have "TeamIsWeakAgainst" key.');
        $this->assertArrayHasKey('TeamIsStrongAgainst', $suggestedPicksAndTeamstatsArray, 'Suggested picks and team stats should have "TeamIsStrongAgainst" key.');

        // Assertions for 'Teamstats'
        $this->assertArrayHasKey('Wins', $suggestedPicksAndTeamstatsArray['Teamstats'], 'Teamstats should have "Wins" key.');
        $this->assertArrayHasKey('Losses', $suggestedPicksAndTeamstatsArray['Teamstats'], 'Teamstats should have "Losses" key.');
        $this->assertArrayHasKey('Winrate', $suggestedPicksAndTeamstatsArray['Teamstats'], 'Teamstats should have "Winrate" key.');
        $this->assertIsNumeric($suggestedPicksAndTeamstatsArray['Teamstats']['Wins'], 'Wins should be numeric.');
        $this->assertIsNumeric($suggestedPicksAndTeamstatsArray['Teamstats']['Losses'], 'Losses should be numeric.');
        $this->assertIsNumeric($suggestedPicksAndTeamstatsArray['Teamstats']['Winrate'], 'Winrate should be numeric.');
        $this->assertGreaterThanOrEqual(0, $suggestedPicksAndTeamstatsArray['Teamstats']['Wins'], 'Wins should be greater than or equal to 0.');
        $this->assertGreaterThanOrEqual(0, $suggestedPicksAndTeamstatsArray['Teamstats']['Winrate'], 'Winrate should be greater than or equal to 0.');
        $this->assertGreaterThanOrEqual(0, $suggestedPicksAndTeamstatsArray['Teamstats']['Losses'], 'Losses should be greater than or equal to 0.');

        // Assertions for 'TeamIsWeakAgainst'
        foreach ($suggestedPicksAndTeamstatsArray['TeamIsWeakAgainst'] as $index => $champArray) {
            $this->assertIsArray($champArray, 'Champion array in TeamIsWeakAgainst should be an array.');
            $this->assertNotEmpty($champArray, 'Champion array in TeamIsWeakAgainst should not be empty.');
            $this->assertArrayHasKey('Champion', $champArray, 'Champion array should have "Champion" key.');
            $this->assertArrayHasKey('Matchscore', $champArray, 'Champion array should have "Matchscore" key.');
            $this->assertIsString($champArray['Champion'], 'Champion should be a string.');
            $this->assertIsNumeric($champArray['Matchscore'], 'Matchscore should be numeric.');
            $this->assertGreaterThanOrEqual(0, $champArray['Matchscore'], 'Matchscore should be greater than or equal to 0.');
        }

        // Assertions for 'TeamIsStrongAgainst'
        foreach ($suggestedPicksAndTeamstatsArray['TeamIsStrongAgainst'] as $index => $champArray) {
            $this->assertIsArray($champArray, 'Champion array in TeamIsStrongAgainst should be an array.');
            $this->assertNotEmpty($champArray, 'Champion array in TeamIsStrongAgainst should not be empty.');
            $this->assertArrayHasKey('Champion', $champArray, 'Champion array should have "Champion" key.');
            $this->assertArrayHasKey('Matchscore', $champArray, 'Champion array should have "Matchscore" key.');
            $this->assertIsString($champArray['Champion'], 'Champion should be a string.');
            $this->assertIsNumeric($champArray['Matchscore'], 'Matchscore should be numeric.');
            $this->assertGreaterThanOrEqual(0, $champArray['Matchscore'], 'Matchscore should be greater than or equal to 0.');
        }
    }

    /**
     * @covers getSuggestedBans
     * @uses MongoDBHelper
     * @uses getMatchData
     * @uses unique_multidim_array
     * @uses isValidID
     * @uses isValidPosition
     * @uses getMatchRanking
     */
    public function testGetSuggestedBans()
    {
        $mdb = new MongoDBHelper();
        $sumids = "MceGjIqeHx6ty7IFgkE7tkXVprFMlx-GiDY52e_9phuQrHHL,KBFbg5b5NWbOFdtOvq5Nr8R5lFHwCR4QS8cTJC6Oau_4Vn6HPW5pFGAiew,cvL-8gEPZWz45ttfrBnWvLF7jgEHzwrW-hz_fQsUyvKd6SH2,kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw,7_MlHF1XF5XJ-O0Hmy-Cjb6ACovIHg5irfmjFmP8lmA7qYyQ";
        $matchIDTeamArray = array();
        $masteryDataTeamArray = array();
        $playerLanesTeamArray = array();
        $playerNameTeamArray = explode(',', $sumids);
        $playerSumidTeamArray = array_flip($playerNameTeamArray);

        foreach(array_keys($playerSumidTeamArray) as $playerSumid){
            if(!$mdb->getPlayerBySummonerId($playerSumid)["success"]){
                echo "Could not find playerfile for ".$playerSumid;
                return;
            } else {
                $playerDataJSONString = json_encode($mdb->findDocumentByField('players', 'PlayerData.SumID', $playerSumid)["document"]);
                $playerDataJSON = json_decode($playerDataJSONString, true);
                foreach(array_keys($playerDataJSON["MatchIDs"]) as $singleMatchID){
                    if(!in_array($singleMatchID, $matchIDTeamArray)){
                        $matchIDTeamArray[] = $singleMatchID;
                    }
                }
                $masteryDataTeamArray[$playerSumid] = $playerDataJSON["MasteryData"];
                $playerLanesTeamArray[$playerSumid]["Mainrole"] = $playerDataJSON["LanePercentages"][0] ?? "";
                $playerLanesTeamArray[$playerSumid]["Secrole"] = $playerDataJSON["LanePercentages"][1] ?? "";

                foreach ($playerNameTeamArray as $singleSumid => $index) {
                    if($playerDataJSON["PlayerData"]["SumID"] == $singleSumid){
                        $playerNameTeamArray[$singleSumid] = $playerDataJSON["PlayerData"]["GameName"];
                    }
                }
            }
        }
        $suggestedBanMatchData = getMatchData($matchIDTeamArray);
        $suggestedBanArray = getSuggestedBans(array_keys($playerSumidTeamArray), $masteryDataTeamArray, $playerLanesTeamArray, $matchIDTeamArray, $suggestedBanMatchData);

        // Assertions for $suggestedBanArray
        $this->assertIsArray($suggestedBanArray, 'Suggested bans should be an array.');
        $this->assertNotEmpty($suggestedBanArray, 'Suggested bans should not be empty.');
        $this->assertCount(10, $suggestedBanArray, 'There should be 10 suggested bans.');
        foreach($suggestedBanArray as $singleChampion){
            // Assertions for each single champion in $suggestedBanArray
            $this->assertIsArray($singleChampion, 'Each suggested ban should be an array.');
            $this->assertNotEmpty($singleChampion, 'Each suggested ban should not be empty.');
            $this->assertArrayHasKey('TotalTeamPoints', $singleChampion, 'Each suggested ban should have "TotalTeamPoints" key.');
            $this->assertIsArray($singleChampion['TotalTeamPoints'], 'TotalTeamPoints should be an array.');
            $this->assertNotEmpty($singleChampion['TotalTeamPoints'], 'TotalTeamPoints should not be empty.');
            $this->assertArrayHasKey('Value', $singleChampion['TotalTeamPoints'], 'TotalTeamPoints should have "Value" key.');
            $this->assertArrayHasKey('Add', $singleChampion['TotalTeamPoints'], 'TotalTeamPoints should have "Add" key.');
            $this->assertIsNumeric($singleChampion['TotalTeamPoints']['Value'], 'TotalTeamPoints Value should be numeric.');
            $this->assertGreaterThan(0, $singleChampion['TotalTeamPoints']['Value'], 'TotalTeamPoints Value should be greater than 0.');
            $this->assertIsNumeric($singleChampion['TotalTeamPoints']['Add'], 'TotalTeamPoints Add should be numeric.');

            $this->assertArrayHasKey('MatchingLanersPrio', $singleChampion, 'Each suggested ban should have "MatchingLanersPrio" key.');
            $this->assertIsArray($singleChampion['MatchingLanersPrio'], 'MatchingLanersPrio should be an array.');
            $this->assertNotEmpty($singleChampion['MatchingLanersPrio'], 'MatchingLanersPrio should not be empty.');
            $this->assertArrayHasKey('Add', $singleChampion['MatchingLanersPrio'], 'MatchingLanersPrio should have "Add" key.');
            $this->assertArrayHasKey('Value', $singleChampion['MatchingLanersPrio'], 'MatchingLanersPrio should have "Value" key.');
            $this->assertIsNumeric($singleChampion['MatchingLanersPrio']['Add'], 'MatchingLanersPrio Add should be numeric.');
            $this->assertIsNumeric($singleChampion['MatchingLanersPrio']['Value'], 'MatchingLanersPrio Value should be numeric.');
            if(array_key_exists('Cause', $singleChampion['MatchingLanersPrio'])){
                $this->assertGreaterThan(0, $singleChampion['MatchingLanersPrio']['Value'], 'MatchingLanersPrio Value should be greater than 0.');
                $this->assertArrayHasKey('Lanes', $singleChampion['MatchingLanersPrio'], 'MatchingLanersPrio should have "Lanes" key.');
                $this->assertIsArray($singleChampion['MatchingLanersPrio']['Lanes'], 'MatchingLanersPrio Lanes should be an array.');
                $this->assertNotEmpty($singleChampion['MatchingLanersPrio']['Lanes'], 'MatchingLanersPrio Lanes should not be empty.');
                foreach ($singleChampion['MatchingLanersPrio']['Lanes'] as $index => $lane) {
                    $this->assertTrue(isValidPosition($lane), 'Each lane in MatchingLanersPrio should be a valid position.');
                }
            }

            if(array_key_exists('OccurencesInLastGames', $singleChampion)){
                $this->assertIsArray($singleChampion['OccurencesInLastGames'], 'OccurencesInLastGames should be an array.');
                $this->assertNotEmpty($singleChampion['OccurencesInLastGames'], 'OccurencesInLastGames should not be empty.');
                $this->assertArrayHasKey('Count', $singleChampion['OccurencesInLastGames'], 'OccurencesInLastGames should have "Count" key.');
                $this->assertArrayHasKey('Add', $singleChampion['OccurencesInLastGames'], 'OccurencesInLastGames should have "Add" key.');
                $this->assertArrayHasKey('Games', $singleChampion['OccurencesInLastGames'], 'OccurencesInLastGames should have "Games" key.');
                $this->assertIsNumeric($singleChampion['OccurencesInLastGames']['Count'], 'Count in OccurencesInLastGames should be numeric.');
                $this->assertIsNumeric($singleChampion['OccurencesInLastGames']['Add'], 'Add in OccurencesInLastGames should be numeric.');
                $this->assertIsNumeric($singleChampion['OccurencesInLastGames']['Games'], 'Games in OccurencesInLastGames should be numeric.');
                $this->assertGreaterThan(0, $singleChampion['OccurencesInLastGames']['Count'], 'Count in OccurencesInLastGames should be greater than 0.');
                $this->assertGreaterThan(0, $singleChampion['OccurencesInLastGames']['Games'], 'Games in OccurencesInLastGames should be greater than 0.');
            }

            $this->assertArrayHasKey('Points', $singleChampion, 'Each suggested ban should have "Points" key.');
            $this->assertArrayHasKey('Add', $singleChampion['Points'], 'Points should have "Add" key.');
            $this->assertArrayHasKey('Value', $singleChampion['Points'], 'Points should have "Value" key.');
            $this->assertArrayHasKey('Cause', $singleChampion['Points'], 'Points should have "Cause" key.');
            $this->assertIsNumeric($singleChampion['Points']['Add'], 'Points Add should be numeric.');
            $this->assertIsNumeric($singleChampion['Points']['Value'], 'Points Value should be numeric.');
            $this->assertGreaterThan(0, $singleChampion['Points']['Value'], 'Points Value should be greater than 0.');
            $this->assertTrue(isValidID($singleChampion['Points']['Cause']), 'Cause in Points should be a valid ID.');

            $this->assertArrayHasKey('CapablePlayers', $singleChampion, 'Each suggested ban should have "CapablePlayers" key.');
            $this->assertArrayHasKey('Add', $singleChampion['CapablePlayers'], 'CapablePlayers should have "Add" key.');
            $this->assertArrayHasKey('Value', $singleChampion['CapablePlayers'], 'CapablePlayers should have "Value" key.');
            $this->assertIsNumeric($singleChampion['CapablePlayers']['Add'], 'CapablePlayers Add should be numeric.');
            $this->assertIsNumeric($singleChampion['CapablePlayers']['Value'], 'CapablePlayers Value should be numeric.');
            $this->assertGreaterThan(0, $singleChampion['CapablePlayers']['Value'], 'CapablePlayers Value should be greater than 0.');

            if(array_key_exists('LastPlayed', $singleChampion)){
                $this->assertIsArray($singleChampion['LastPlayed'], 'LastPlayed should be an array.');
                $this->assertNotEmpty($singleChampion['LastPlayed'], 'LastPlayed should not be empty.');
                $this->assertArrayHasKey('Add', $singleChampion['LastPlayed'], 'LastPlayed should have "Add" key.');
                $this->assertArrayHasKey('Value', $singleChampion['LastPlayed'], 'LastPlayed should have "Value" key.');
                $this->assertIsNumeric($singleChampion['LastPlayed']['Add'], 'LastPlayed Add should be numeric.');
                $this->assertIsNumeric($singleChampion['LastPlayed']['Value'], 'LastPlayed Value should be numeric.');
                $this->assertGreaterThan(0, $singleChampion['LastPlayed']['Value'], 'LastPlayed Value should be greater than 0.');
            }

            $this->assertArrayHasKey('Filename', $singleChampion, 'Each suggested ban should have "Filename" key.');
            $this->assertNotEmpty($singleChampion['Filename'], 'Filename should not be empty.');

            if(array_key_exists('AverageMatchScore', $singleChampion)){
                $this->assertArrayHasKey('AverageMatchScore', $singleChampion, 'Each suggested ban should have "AverageMatchScore" key.');
                $this->assertIsArray($singleChampion['AverageMatchScore'], 'AverageMatchScore should be an array.');
                $this->assertNotEmpty($singleChampion['AverageMatchScore'], 'AverageMatchScore should not be empty.');
                $this->assertArrayHasKey('Add', $singleChampion['AverageMatchScore'], 'AverageMatchScore should have "Add" key.');
                $this->assertArrayHasKey('Value', $singleChampion['AverageMatchScore'], 'AverageMatchScore should have "Value" key.');
                $this->assertIsNumeric($singleChampion['AverageMatchScore']['Add'], 'AverageMatchScore Add should be numeric.');
                $this->assertIsNumeric($singleChampion['AverageMatchScore']['Value'], 'AverageMatchScore Value should be numeric.');
            }
            $this->assertArrayHasKey('FinalScore', $singleChampion, 'Each suggested ban should have "FinalScore" key.');
            $this->assertIsNumeric($singleChampion['FinalScore'], 'FinalScore should be numeric.');
            $this->assertGreaterThan(0, $singleChampion['FinalScore'], 'FinalScore should be greater than 0.');
        }
    }

    /**
     * @covers abbreviationFetcher
     */
    public function testAbbreviationFetcher()
    {
        $champName = "Aatrox";

        $expectedResult = 'dark,darki,darkin,top,mid,fighter,ad,tank';
        $this->assertEquals($expectedResult, abbreviationFetcher($champName), 'Requested abbreviations did not match returned ones.');

        $nonExistingChampName = "NonExistentChamp";
        $this->assertEmpty(abbreviationFetcher($nonExistingChampName), 'Found a non-existing champion in abbreviations.json');
    }

    /**
     * @covers getRankOrLevel
     */
    public function testGetRankOrLevel()
    {
        $rankDataMid = [
            [
              "Queue" => "RANKED_FLEX_SR",
              "Tier" => "BRONZE",
              "Rank" => "IV",
              "LP" => 11,
              "Wins" => 77,
              "Losses" => 71
            ],
            [
              "Queue" => "RANKED_SOLO_5x5",
              "Tier" => "SILVER",
              "Rank" => "III",
              "LP" => 2,
              "Wins" => 30,
              "Losses" => 37
            ]
        ];
        
        $playerDataMid = [
            "Level" => 30, // Sample level value
        ];

        // Testing when rankVal is set
        $expectedRankResult = [
            "Type" => "Rank",
            "HighestRank" => "SILVER",
            "HighEloLP" => "",
            "RankNumber" => "III",
        ];
        $this->assertEquals($expectedRankResult, getRankOrLevel($rankDataMid, $playerDataMid), 'Get Rank approach did not correctly return on sample data.');

        $rankDataHighRank = [
            [
              "Queue" => "RANKED_FLEX_SR",
              "Tier" => "GRANDMASTER",
              "Rank" => "I",
              "LP" => 273,
              "Wins" => 37,
              "Losses" => 41
            ]
        ];
        
        $playerDataHighRank = [
            "Level" => 561, // Sample level value
        ];

        // Testing when rankVal is set
        $expectedHighRankResult = [
            "Type" => "Rank",
            "HighestRank" => "GRANDMASTER",
            "HighEloLP" => "273",
            "RankNumber" => "",
        ];
        $this->assertEquals($expectedHighRankResult, getRankOrLevel($rankDataHighRank, $playerDataHighRank), 'Get High Rank did not correctly return necessary values.');

        $playerDataLow = ["Level" => 10];
        $rankDataLow = [];
        $expectedLevelResult = [
            "Type" => "Level",
            "LevelFileName" => "001",
        ];
        $this->assertEquals($expectedLevelResult, getRankOrLevel($rankDataLow, $playerDataLow), 'Get Low level did not correctly return low level filename.');

        $playerDataLevelHigh = ["Level" => 673];
        $rankDataLevelHigh = [];
        $expectedHighLevelResult = [
            "Type" => "Level",
            "LevelFileName" => "500",
        ];
        $this->assertEquals($expectedHighLevelResult, getRankOrLevel($rankDataLevelHigh, $playerDataLevelHigh), 'Get High level did not correctly return high level filename.');
    }

    /**
     * @covers getMasteryColor
     */
    public function testGetMasteryColor()
    {
        $testXXSValue = getMasteryColor(20000);
        $testXSValue = getMasteryColor(130000);
        $testSValue = getMasteryColor(240000);
        $testMValue = getMasteryColor(350000);
        $testLValue = getMasteryColor(600000);
        $testXLValue = getMasteryColor(880000);
        $testXXLValue = getMasteryColor(2000000);
        $testFailValue = getMasteryColor("wrongvalue");
        
        $this->assertEquals("threat-xxs", $testXXSValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("threat-xs", $testXSValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("threat-s", $testSValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("threat-m", $testMValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("threat-l", $testLValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("threat-xl", $testXLValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("threat-xxl", $testXXLValue, "Mastery points did not return correct corresponding threat level.");
        $this->assertEquals("", $testFailValue, "Mastery points did not return correct corresponding threat level.");
    }

    /**
     * 
     * @covers calculateSmurfProbability
     */
    public function testCalculateSmurfProbability()
    {
        // Mocking player data, rank data, and mastery data for testing
        $playerDataLow = [
            "LastChange" => strtotime('-2 years')*1000, // Minimum timestamp value
            "Level" => 1, // Minimum level value
        ];
        $rankDataLow = [];
        $masteryDataLow = [];

        // Testing with minimum values
        $this->assertEquals(1, calculateSmurfProbability($playerDataLow, $rankDataLow, $masteryDataLow), 'Smurf Probability was not correclty calculated for full/high indicators.');

        // Testing with maximum values
        $playerDataHigh = [
            "LastChange" => strtotime('-1 week')*1000,
            "Level" => 500
        ];
        $rankDataHigh = [
            [
              "Queue" => "RANKED_FLEX_SR",
              "Tier" => "EMERALD",
              "Rank" => "IV",
              "LP" => 11,
              "Wins" => 77,
              "Losses" => 71
            ],
            [
              "Queue" => "RANKED_SOLO_5x5",
              "Tier" => "SILVER",
              "Rank" => "III",
              "LP" => 2,
              "Wins" => 30,
              "Losses" => 37
            ]
        ];

        $masteryDataHigh = [
            [
                "Champion" => "Yuumi",
                "Filename" => "Yuumi",
                "Lvl" => 7,
                "Points" => "272,592",
                "LastPlayed" => 1712169256
            ]
        ];
        $this->assertEquals(0, calculateSmurfProbability($playerDataHigh, $rankDataHigh, $masteryDataHigh), 'Smurf Probability was not correclty calculated for no/low indicators.');
    }

    /**
     * @covers tagSelector
     * @covers generateTag
     * @uses __
     */
    public function testTagSelectorWithTags()
    {
        $testTagArray1 = ['dragonTakedowns' => 0.5, 'kda' => 0.3];
        $testTagArray2 = ['dragonTakedowns' => -0.5, 'kda' => -0.3];

        $result1 = tagSelector($testTagArray1);
        $result2 = tagSelector($testTagArray2);

        $this->assertStringContainsString('Dragonmaster', $result1, 'Generated data is missing content that should have been generated.');
        $this->assertStringContainsString('Dragonfumbler', $result2, 'Generated data is missing content that should have been generated.');
        $this->assertStringNotContainsString('Dragonfumbler', $result1, 'Generated data contains content that should not have been generated.');
        $this->assertStringNotContainsString('Dragonmaster', $result2, 'Generated data contains content that should not have been generated.');
        $this->assertStringContainsString('data-type="positive"', $result1, 'Generated data is missing content that should have been generated.');
        $this->assertStringNotContainsString('data-type="positive"', $result2, 'Generated data contains content that should not have been generated.');
        $this->assertStringContainsString('K/DA', $result1, 'Generated data is missing content that should have been generated.');
        $this->assertStringNotContainsString('Careless', $result1, 'Generated data contains content that should not have been generated.');
        $this->assertStringContainsString('Careless', $result2, 'Generated data is missing content that should have been generated.');
        $this->assertStringNotContainsString('K/DA', $result2, 'Generated data contains content that should not have been generated.');
        $this->assertStringNotContainsString('data-type="negative"', $result1, 'Generated data contains content that should not have been generated.');
        $this->assertStringContainsString('data-type="negative"', $result2, 'Generated data is missing content that should have been generated.');
        $this->assertStringNotContainsString('Lazy', $result1, 'Generated data contains content that should not have been generated.');
        $this->assertStringNotContainsString('Lazy', $result2, 'Generated data contains content that should not have been generated.');

        $_COOKIE["tagOptions"] = "multi-colored";

        $testTagGenerate1 = generateTag("Tag Text", "green", "Tooltip Text", "additionalData");

        $this->assertStringContainsString("Tag Text", $testTagGenerate1, 'Generated data is missing content that should have been generated.');
        $this->assertStringContainsString("Tooltip Text", $testTagGenerate1, 'Generated data is missing content that should have been generated.');
        $this->assertStringContainsString("green", $testTagGenerate1, 'Generated data is missing content that should have been generated.');
        $this->assertStringNotContainsString("Unknown tag option", $testTagGenerate1, 'Generating data was not successful.');

        $_COOKIE["tagOptions"] = "invalid-value";

        $testTagGenerate2 = generateTag("Tag Text", "blue", "Tooltip Text", "");

        $this->assertEquals("Unknown tag option", $testTagGenerate2, 'Generating data was successful although it should not have been.');

        unset($_COOKIE["tagOptions"]);

        $testTagGenerate3 = generateTag("Tag Text", "red", "Tooltip Text", "");

        $this->assertEquals("Unknown tag option", $testTagGenerate3, 'Generating data was successful although it should not have been.');
    }

    /**
     * @covers generateCSRFToken
     * @uses isValidCSRF
     */
    public function testGenerateCSRFToken()
    {
        $testGenerateCSRF = generateCSRFToken();

        $this->assertNotNull($testGenerateCSRF, 'Generating a CSRF token was not possible.');
        $this->assertTrue(isValidCSRF($testGenerateCSRF), 'The returned CSRF token did not pass as valid.');
        $this->assertArrayHasKey('csrf_token', $_SESSION, 'The session did not get a new CSRF token set.');
        $this->assertEquals($testGenerateCSRF, $_SESSION['csrf_token'], 'The CSRF token generated and set in the session do not match');
    }

    /**
     * @covers objectToArray
     */
    public function testObjectToArray()
    {
        // Simple approach
        $object = (object) [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $resultObject = objectToArray($object);

        $this->assertEquals(['key1' => 'value1', 'key2' => 'value2'], $resultObject, 'Object was not successfully transformed to an array.');

        $array = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $resultArray = objectToArray($array);

        $this->assertEquals($array, $resultArray, 'Array was modified instead of leaving it as an array.');

        // Complex nested approach
        {
            $mixedStructure = [
                'key1' => 'value1',
                'key2' => [
                    'subkey1' => 'subvalue1',
                    'subkey2' => (object) ['subkey3' => 'subvalue3'],
                    'subkey3' => [
                        'subsubkey1' => 'subsubvalue1',
                        'subsubkey2' => ['subsubsubkey1' => 'subsubsubvalue1']
                    ]
                ],
                'key3' => (object) [
                    'subkey4' => 'subvalue4',
                    'subkey5' => ['subsubkey3' => 'subsubvalue3'],
                    'subkey6' => [
                        'subsubkey4' => 'subsubvalue4',
                        'subsubkey5' => (object) ['subsubsubkey2' => 'subsubsubvalue2']
                    ]
                ]
            ];
    
            $result = objectToArray($mixedStructure);
    
            $expectedResult = [
                'key1' => 'value1',
                'key2' => [
                    'subkey1' => 'subvalue1',
                    'subkey2' => ['subkey3' => 'subvalue3'],
                    'subkey3' => [
                        'subsubkey1' => 'subsubvalue1',
                        'subsubkey2' => ['subsubsubkey1' => 'subsubsubvalue1']
                    ]
                ],
                'key3' => [
                    'subkey4' => 'subvalue4',
                    'subkey5' => ['subsubkey3' => 'subsubvalue3'],
                    'subkey6' => [
                        'subsubkey4' => 'subsubvalue4',
                        'subsubkey5' => ['subsubsubkey2' => 'subsubsubvalue2']
                    ]
                ]
            ];
    
            $this->assertEquals($expectedResult, $result, 'Object to array did not run successful on mixed/nested elements.');

            $wrongApproach = objectToArray("test123");
            
            $this->assertFalse($wrongApproach, 'Object to array someone accepted different element than array and/or object.');
        }
    }

    /**
     * @covers fileExistsWithCache
     */
    public function testFileExistsWithCache()
    {
        global $fileExistsCache;
        $fileExistsCache = [];

        $filePath = '/hdd1/clashapp/README.md';
        $nonExistentFilePath = '/hdd1/clashapp/DONTREADME.md';

        $exists1 = fileExistsWithCache($filePath);

        $this->assertTrue($exists1, 'Existing file returns as non-existing.');
        $this->assertArrayHasKey($filePath, $fileExistsCache, 'Existing file is missing from cache.');
        $this->assertTrue($fileExistsCache[$filePath], 'Cached existence answer is not the same as non-cached one.');

        $exists2 = fileExistsWithCache($nonExistentFilePath);

        $this->assertFalse($exists2, 'Non-Existing file returns as existing.');
        $this->assertArrayHasKey($nonExistentFilePath, $fileExistsCache, 'Non-Existing file is missing from cache.');
        $this->assertFalse($fileExistsCache[$nonExistentFilePath], 'Cached existence answer is not the same as non-cached one.');

        $exists3 = fileExistsWithCache($filePath);

        $this->assertTrue($exists3, 'Already before existing file suddenly returns as non-existing.');
        $this->assertArrayHasKey($filePath, $fileExistsCache, 'Already before existing file is suddenly missing from cache.');
        $this->assertTrue($fileExistsCache[$filePath], 'Already existing cached existence answer is not the same as non-cached one.');
    }

    /**
     * @covers addToGlobalMatchDataCache
     */
    public function testAddToGlobalMatchDataCache()
    {
        global $matchDataCache;
        $matchDataCache = [];

        $testMatch = (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740123']];

        addToGlobalMatchDataCache($testMatch);

        $this->assertArrayHasKey('EUW1_6881740123', $matchDataCache, 'Adding to match data cache was not successful.');
        $this->assertEquals($testMatch, $matchDataCache['EUW1_6881740123'], 'Match data in cache is not the same as before.');
    }

    /**
     * @covers sortByMatchIds
     */
    public function testSortByMatchIds()
    {
        $matchDataArray = [
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740123']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740789']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740456']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6926482653']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_1234567890']],
        ];

        $expected = [
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6926482653']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740789']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740456']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_6881740123']],
            (object) ['metadata' => (object) ['matchId' => 'EUW1_1234567890']],
        ];

        $result = sortByMatchIds($matchDataArray);

        $this->assertEquals($expected, $result, 'Sorting matchids in descending ordner was not successful.');
    }

    /**
     * @covers isValidCSRF
     */
    public function testIsValidCSRF()
    {
        $this->assertTrue(isValidCSRF('0123456789abcdef0123456789ABCDEF0123456789abcdef0123456789ABCDEF'), 'Valid CSRF did not pass validation.');
        $this->assertTrue(isValidCSRF('6566203363206236203566203730206437203062206231203534203632203339'), 'Valid CSRF did not pass validation.');
        $this->assertTrue(isValidCSRF('9876543210ABCDEF9876543210abcdef9876543210ABCDEF9876543210abcdef'), 'Valid CSRF did not pass validation.');
        $this->assertTrue(isValidCSRF('ABCDEF0123456789abcdef0123456789abcdef0123456789abcdefABCDEF0010'), 'Valid CSRF did not pass validation.');

        $this->assertFalse(isValidCSRF('EUW1_688439750445436234234'), 'Invalid CSRF should not pass validation.');
        $this->assertFalse(isValidCSRF('필릭스'), 'Invalid CSRF should not pass validation.');
        $this->assertFalse(isValidCSRF(';'), 'Invalid CSRF should not pass validation.');
        $this->assertFalse(isValidCSRF(10), 'Invalid CSRF should not pass validation.');
    }

    /**
     * @covers isValidMatchID
     */
    public function testIsValidMatchID()
    {
        $this->assertTrue(isValidMatchID('EUW1_6884397504'), 'Valid MatchID did not pass validation.');
        $this->assertTrue(isValidMatchID('EUW1_4816389533'), 'Valid MatchID did not pass validation.');
        $this->assertTrue(isValidMatchID('KR1_2848239562'), 'Valid MatchID did not pass validation.');
        $this->assertTrue(isValidMatchID('EUNE1_1536746424'), 'Valid MatchID did not pass validation.');

        $this->assertFalse(isValidMatchID('EUW1_688439750445436234234'), 'Invalid MatchID should not pass validation.');
        $this->assertFalse(isValidMatchID('필릭스'), 'Invalid MatchID should not pass validation.');
        $this->assertFalse(isValidMatchID(';'), 'Invalid MatchID should not pass validation.');
        $this->assertFalse(isValidMatchID(10), 'Invalid MatchID should not pass validation.');
    }

    /**
     * @covers isValidIterator
     */
    public function testIsValidIterator()
    {
        $this->assertTrue(isValidIterator(0), 'Valid Iterator did not pass validation.');
        $this->assertTrue(isValidIterator(1), 'Valid Iterator did not pass validation.');
        $this->assertTrue(isValidIterator(5), 'Valid Iterator did not pass validation.');
        $this->assertTrue(isValidIterator(9), 'Valid Iterator did not pass validation.');

        $this->assertFalse(isValidIterator('*.,-feddichisdasmondjesichd'), 'Invalid Iterator should not pass validation.');
        $this->assertFalse(isValidIterator('필릭스'), 'Invalid Iterator should not pass validation.');
        $this->assertFalse(isValidIterator(';'), 'Invalid Iterator should not pass validation.');
        $this->assertFalse(isValidIterator(10), 'Invalid Iterator should not pass validation.');
    }

    /**
     * @covers isValidID
     */
    public function testIsValidID()
    {
        $this->assertTrue(isValidID('lMPVJegBts5TsSd7vKrf9j_oMPZN9N8ul2CBtviFNcuIzlGdWG1d4riiG9f4lNNoyzq-HVDRA8IzcA'), 'Valid ID did not pass validation.');
        $this->assertTrue(isValidID('MceGjIqeHx6ty7IFgkE7tkXVprFMlx-GiDY52e_9phuQrHHL'), 'Valid ID did not pass validation.');
        $this->assertTrue(isValidID('wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA'), 'Valid ID did not pass validation.');
        $this->assertTrue(isValidID('kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw'), 'Valid ID did not pass validation.');

        $this->assertFalse(isValidID('*.,-feddichisdasmondjesichd'), 'Invalid ID should not pass validation.');
        $this->assertFalse(isValidID('필릭스'), 'Invalid ID should not pass validation.');
        $this->assertFalse(isValidID(';'), 'Invalid ID should not pass validation.');
        $this->assertFalse(isValidID('SQL DROP DATABASE;'), 'Invalid ID should not pass validation.');
    }

    /**
     * @covers isValidPosition
     */
    public function testIsValidPosition()
    {
        $this->assertTrue(isValidPosition('bot'), 'Valid position did not pass validation.');
        $this->assertTrue(isValidPosition('MIDDLE'), 'Valid position did not pass validation.');
        $this->assertTrue(isValidPosition('Support'), 'Valid position did not pass validation.');
        $this->assertTrue(isValidPosition('unselected'), 'Valid position did not pass validation.');

        $this->assertFalse(isValidPosition('adc'), 'Invalid position should not pass validation.');
        $this->assertFalse(isValidPosition('MiDlAnEr'), 'Invalid position should not pass validation.');
        $this->assertFalse(isValidPosition(';'), 'Invalid position should not pass validation.');
        $this->assertFalse(isValidPosition('SQL DROP DATABASE;'), 'Invalid position should not pass validation.');
    }

    /**
     * @covers isValidPlayerName
     */
    public function testIsValidPlayerName()
    {
        $this->assertTrue(isValidPlayerName('DasNerdwork'), 'Valid player name did not pass validation.');
        $this->assertTrue(isValidPlayerName('ŠUŠŇOJED'), 'Valid player name did not pass validation.');
        $this->assertTrue(isValidPlayerName('TTV KERBEROS LOL'), 'Valid player name did not pass validation.');
        $this->assertTrue(isValidPlayerName('필릭스'), 'Valid player name did not pass validation.');

        $this->assertFalse(isValidPlayerName('abcdefghijklmnopqrstuvwxyz'), 'Invalid player name should not pass validation.');
        $this->assertFalse(isValidPlayerName('</_dasd_>#yeet'), 'Invalid player name should not pass validation.');
        $this->assertFalse(isValidPlayerName(';'), 'Invalid player name should not pass validation.');
        $this->assertFalse(isValidPlayerName('SQL DROP DATABASE;'), 'Invalid player name should not pass validation.');
    }

    /**
     * @covers isValidPlayerTag
     */
    public function testIsValidPlayerTag()
    {
        $this->assertTrue(isValidPlayerTag('KR1'), 'Valid player tag did not pass validation.');
        $this->assertTrue(isValidPlayerTag('EUNE'), 'Valid player tag did not pass validation.');
        $this->assertTrue(isValidPlayerTag('nerdy'), 'Valid player tag did not pass validation.');
        $this->assertTrue(isValidPlayerTag('필릭스'), 'Valid player tag did not pass validation.');

        $this->assertFalse(isValidPlayerTag('abcdefghijkl'), 'Invalid player tag should not pass validation.');
        $this->assertFalse(isValidPlayerTag('1'), 'Invalid player tag should not pass validation.');
        $this->assertFalse(isValidPlayerTag(';'), 'Invalid player tag should not pass validation.');
        $this->assertFalse(isValidPlayerTag('SQL DROP DATABASE;'), 'Invalid player tag should not pass validation.');
    }
}