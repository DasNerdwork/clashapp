<?php
use PHPUnit\Framework\TestCase;
require_once('/hdd1/clashapp/mongo-db.php');

class MongoDBTest extends TestCase {
    /**
     * @covers MongoDBHelper::findDocumentByField
     */
    public function testFindDocumentByField() {
        $mongoDBHelper = new MongoDBHelper();

        $failFindByField = $mongoDBHelper->findDocumentByField('players', 'PlayerData.SumID', 'wrongsumid');
        $successFindByField = $mongoDBHelper->findDocumentByField('players', 'PlayerData.SumID', 'kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw');

        $this->assertFalse($failFindByField['success'], 'Searching for a non-existing document returned successful, which it is not supposed to do');
        $this->assertTrue($successFindByField['success'], 'Unable to find player Document for test sumid');
        $this->assertNotNull($successFindByField['document'], 'Successfully found document, but content is null');
        $this->assertIsArray($successFindByField['document'], 'Successfully found document, but content is not an array');
    }

    /**
     * @covers MongoDBHelper::findDocumentsByMatchIds
     * @uses MongoDBHelper::findDocumentByField
     * @uses MongoDBHelper
     */
    public function testFindDocumentsByMatchIds() {
        $mongoDBHelper = new MongoDBHelper();

        $testPlayerData = $mongoDBHelper->findDocumentByField('players', 'PlayerData.SumID', 'kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw');
        $testMatchIDsArray = array_keys(array_slice((array)$testPlayerData['document']['MatchIDs'], 0, 3)); // Gets the first thee match ids in a simple array (0 => euw_1, 1 => euw_2, ...)
        
        $failFindDocumentsByMatchIds = $mongoDBHelper->findDocumentsByMatchIds('matches', 'metadata.matchId', [0 => "EUW_12345", 1 => "EUW_23456"], ['info.gameDuration', 'info.queueId', 'info.gameEndTimestamp']);
        $successFindDocumentsByMatchIds = $mongoDBHelper->findDocumentsByMatchIds('matches', 'metadata.matchId', $testMatchIDsArray, ['info.gameDuration', 'info.queueId', 'info.gameEndTimestamp']);
        $wrongFieldFindDocumentsByMatchIds = $mongoDBHelper->findDocumentsByMatchIds('matches', 'metadata.matchId', $testMatchIDsArray, ['info.wrongField']);
        
        $this->assertFalse($failFindDocumentsByMatchIds['success'], 'Expected unsuccessful operation for invalid matchIDs');
        $this->assertTrue($successFindDocumentsByMatchIds['success'], 'Expected successful operation for valid matchIDs');
        $this->assertNotEmpty($successFindDocumentsByMatchIds['documents'], 'Expected non-empty documents return value for successful operation');
        $this->assertNotNull($successFindDocumentsByMatchIds['documents'], 'Expected non-null documents return value for successful operation');
        $this->assertTrue($wrongFieldFindDocumentsByMatchIds['success'], 'Expected successful operation for invalid fields');
        $this->assertEquals('D4NG3R', $wrongFieldFindDocumentsByMatchIds['code'], 'Expected error code D4NG3R for invalid fields operation');
    }
}
?>
