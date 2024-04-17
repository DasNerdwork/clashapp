<?php
use PHPUnit\Framework\TestCase;
require_once('/hdd1/clashapp/db/mongo-db.php');

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
     * @covers MongoDBHelper::createProjection
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

    /**
     * @covers MongoDBHelper::insertDocument
     * @covers MongoDBHelper::getDocumentField
     * @covers MongoDBHelper::deleteDocumentByField
     * @covers MongoDBHelper::addElementToDocument
     * @uses MongoDBHelper
     */
    public function testHandleDocuments() {
        $mongoDBHelper = new MongoDBHelper();
        $testDocument = [
            'PlayerData' => ['GameName' => 'PHPUnitTest', 'Tag' => 'EUW', 'Code' => 'pjHFoMsLXIeY2O0n'],
            'RankData' => ["Queue" => "RANKED_FLEX_SR", "Tier" => "EMERALD", "Rank" => "IV", "LP" => 11, "Wins" => 77, "Losses" => 71]
        ];

        $insertResult = $mongoDBHelper->insertDocument('players', $testDocument);

        $this->assertTrue($insertResult['success'], 'Insertion should be successful.');

        $insertAgain = $mongoDBHelper->insertDocument('players', $testDocument);

        $this->assertFalse($insertAgain['success'], 'Insertion should not be successful.');

        $testRetrieveResult = $mongoDBHelper->getDocumentField('players', 'PlayerData.GameName', 'PHPUnitTest', 'RankData');
        $testFailRetrieveResult = $mongoDBHelper->getDocumentField('players', 'PlayerData.WrongField', 'PHPUnitTest', 'RankData');
        $testFailRetrieveResult2 = $mongoDBHelper->getDocumentField('players', 'PlayerData.Code', 'PHPUnitTestLabel', 'RankData');
        $testFailRetrieveResult3 = $mongoDBHelper->getDocumentField('players', 'PlayerData.Code', 'pjHFoMsLXIeY2O0n', 'InexistentField');
        $testRetrieveWhole = $mongoDBHelper->getDocumentField('players', 'PlayerData.GameName', 'PHPUnitTest');

        $this->assertTrue($testRetrieveResult['success'], 'Retrieval should be successful.');
        $this->assertEquals($testDocument['RankData'], (array)$testRetrieveResult['data'], 'Retrieved field value should match.');
        $this->assertFalse($testFailRetrieveResult['success'], 'Retrieval should not be successful on wrong fields');
        $this->assertFalse($testFailRetrieveResult2['success'], 'Retrieval should not be successful on wrong field content');
        $this->assertFalse($testFailRetrieveResult3['success'], 'Retrieval should not be successful on wrong field');

        $this->assertTrue($testRetrieveWhole['success'], 'Retrieval should be successful.');
        $this->assertArrayHasKey('data', $testRetrieveWhole, 'Retrieved document should have data attribute');

        $testAddElement = $mongoDBHelper->addElementToDocument('players', 'PlayerData.Code', 'pjHFoMsLXIeY2O0n', 'PlayerData.WrongField', 'PHPUnitTest');
        
        $this->assertTrue($testAddElement['success'], 'Adding a custom field to a document should return successful.');

        $testSuccessRetrieveAgain = $mongoDBHelper->getDocumentField('players', 'PlayerData.WrongField', 'PHPUnitTest', 'RankData');
        $this->assertTrue($testSuccessRetrieveAgain['success'], 'Getting document should be successful on newly added field');

        $testDelete = $mongoDBHelper->deleteDocumentByField('players', 'PlayerData.Code', 'pjHFoMsLXIeY2O0n');

        $this->assertTrue($testDelete['success'], 'Deleting a document should be successful.');

        $testDeleteAgain = $mongoDBHelper->deleteDocumentByField('players', 'PlayerData.Code', 'pjHFoMsLXIeY2O0n');
        
        $this->assertFalse($testDeleteAgain['success'], 'Deleting an already deleted document should not be successful.');
    }

    /**
     * @covers MongoDBHelper::getPlayerBySummonerId
     * @covers MongoDBHelper::getPlayerByRiotId
     * @covers MongoDBHelper::getPlayerByPUUID
     */
    public function testGetPlayerBy() {
        $mongoDBHelper = new MongoDBHelper();

        $testFailBySumID = $mongoDBHelper->getPlayerBySummonerId('wrongsumid');
        $testSuccessBySumID = $mongoDBHelper->getPlayerBySummonerId('kLIAKUzGnotwLAJbl-rdqOu_CQYjwW7OOMloEtRyM6oP-uw');
        $testFailByRiotID = $mongoDBHelper->getPlayerByRiotId('wronggamename', 'wrongtag');
        $testSuccessByRiotID = $mongoDBHelper->getPlayerByRiotId('DasNerdwork', 'nerdy');
        $testFailByPUUID = $mongoDBHelper->getPlayerByPUUID('wrongpuuid');
        $testSuccessByPUUID = $mongoDBHelper->getPlayerByPUUID('wZzROfU21vgztiGFq_trTZDeG89Q1CRGAKPktG83VKS-fkCISXhAWUptVVftbtVNIHMvgJo6nIlOyA');
        
        $this->assertFalse($testFailBySumID['success'], 'Getting a document by sumid should return successful.');
        $this->assertTrue($testSuccessBySumID['success'], 'Getting a document by a non-existing sumid should not return successful.');
        $this->assertFalse($testFailByRiotID['success'], 'Getting a document by riotid+tag should return successful.');
        $this->assertTrue($testSuccessByRiotID['success'], 'Getting a document by a non-existing riotid+tag should not return successful.');
        $this->assertFalse($testFailByPUUID['success'], 'Getting a document by puuid should return successful.');
        $this->assertTrue($testSuccessByPUUID['success'], 'Getting a document by a non-existing puuid should not return successful.');
        $this->assertArrayHasKey('data', $testSuccessBySumID, 'The returned element should contain the necessary data attribute.');
        $this->assertArrayHasKey('data', $testSuccessByRiotID, 'The returned element should contain the necessary data attribute.');
        $this->assertArrayHasKey('data', $testSuccessByPUUID, 'The returned element should contain the necessary data attribute.');
        $this->assertEquals($testSuccessBySumID['data'], $testSuccessByRiotID['data'], 'Returned data should be the same regardless of operation.');
        $this->assertEquals($testSuccessByRiotID['data'], $testSuccessByPUUID['data'], 'Returned data should be the same regardless of operation.');
        $this->assertEquals($testSuccessBySumID['data'], $testSuccessByPUUID['data'], 'Returned data should be the same regardless of operation.');
    }

    /**
     * @covers MongoDBHelper::getAutosuggestAggregate
     */
    public function testGetAutosuggestAggregate() {
        $mongoDBHelper = new MongoDBHelper();

        $testAggregate = $mongoDBHelper->getAutosuggestAggregate();
        $this->assertTrue($testAggregate['success'], 'Retrieving the autosuggest aggregate should be successful.');
        $this->assertArrayHasKey('data', $testAggregate, 'The returned element should contain the necessary data attribute.');
        $this->assertNotEmpty($testAggregate['data'], 'The returned elements data attribute should not be empty.');
    }

    /**
     * @covers MongoDBHelper::aggregate
     */
    public function testAggregate() {
        $mongoDBHelper = new MongoDBHelper();

        $testSuccessAggregation = $mongoDBHelper->aggregate("teams", [['$project' => ['TeamID'  => 1]]], []);

        foreach ($testSuccessAggregation as $singleData) {
            $this->assertArrayHasKey('TeamID', (array)$singleData, 'Aggregation did not successfully retrieve pipelined data.');
        }

        $testFailAggregation = $mongoDBHelper->aggregate("teams", [['$project' => ['WrongProjection'  => 1]]], []);

        foreach ($testFailAggregation as $singleData) {
            $this->assertArrayNotHasKey('TeamID', (array)$singleData, 'Somehow aggregation has key even though it should not have.');
        }

        $testFailAggregationAgain = $mongoDBHelper->aggregate("wrongdatabase", [['$project' => ['WrongProjection'  => 1]]], []);

        $this->assertNull($testFailAggregationAgain, 'Aggregation of non-existing database does not return null.');
    }
}
?>
