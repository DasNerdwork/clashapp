<?php
use PHPUnit\Framework\TestCase;
include_once('/hdd1/clashapp/functions.php');

$currentPatch = file_get_contents("/hdd1/clashapp/data/patch/version.txt");

class FunctionsTest extends TestCase {
    public function testGetPlayerDataByName() {
        $actualData = getPlayerData("name", "Flokrastinator");

        $this->assertArrayHasKey('Icon', $actualData);
        $this->assertIsNumeric($actualData['Icon']);
        $this->assertGreaterThanOrEqual(0, $actualData['Icon']);

        $this->assertArrayHasKey('Level', $actualData);
        $this->assertIsNumeric($actualData['Level']);
        $this->assertGreaterThanOrEqual(0, $actualData['Level']);

        $this->assertArrayHasKey('LastChange', $actualData);
        $this->assertNotNull($actualData['LastChange']);
        $this->assertGreaterThan(1256515200000, $actualData['LastChange']); // 1256515200000 = 27. Oktober 2009 (Release Date of LOL)

        $this->assertArrayHasKey('PUUID', $actualData);
        $this->assertEquals('wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA', $actualData['PUUID']);

        $this->assertArrayHasKey('SumID', $actualData);
        $this->assertEquals('kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw', $actualData['SumID']);

        $this->assertArrayHasKey('AccountID', $actualData);
        $this->assertEquals('NoudYpU8MTqtQ7BvYx4kbQt8boAaDeemjWwOv42nQpH4q98', $actualData['AccountID']);

        $this->assertArrayHasKey('Name', $actualData);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9\p{L}]{3,16}$/', $actualData['Name']);
    }

    public function testGetMasteryScores() {
        global $currentPatch;
        $championJson = json_decode(file_get_contents('/hdd1/clashapp/data/patch/'.$currentPatch.'/data/de_DE/champion.json'), true);
        $actualData = getMasteryScores("kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw");

        foreach ($actualData as $masteryData) {
            $this->assertArrayHasKey("Champion", $masteryData);
            $this->assertTrue($this->doesChampionNameExist($masteryData['Champion'], $championJson['data']));

            $this->assertArrayHasKey("Filename", $masteryData);
            $this->assertNotNull($masteryData['Filename']);

            $this->assertArrayHasKey("Lvl", $masteryData);
            $this->assertGreaterThanOrEqual(0, $masteryData['Lvl']);
            $this->assertLessThanOrEqual(7, $masteryData['Lvl']);

            $this->assertArrayHasKey("Points", $masteryData);
            $this->assertIsNumeric(str_replace(',', '.', $masteryData['Points']));
            $this->assertGreaterThanOrEqual(0, str_replace(',', '.', $masteryData['Points']));

            $this->assertArrayHasKey("LastPlayed", $masteryData);
            $this->assertNotNull($masteryData["LastPlayed"]);
            $this->assertGreaterThan(1256515200, $masteryData['LastPlayed']); // 1256515200 = 27. Oktober 2009 (Release Date of LOL)

            if (array_key_exists('LvlUpTokens', $masteryData)) {
                $this->assertIsNumeric($masteryData['LvlUpTokens']);
                $this->assertGreaterThanOrEqual(0, $masteryData['LvlUpTokens']);
                $this->assertLessThanOrEqual(3, $masteryData['LvlUpTokens']);
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
}