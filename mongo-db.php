<?php 
require_once '/hdd1/clashapp/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
        
class MongoDBHelper {
    private $host = '***REMOVED***';
    private $username ***REMOVED***
    private $password = '***REMOVED***';
    private $auth = '***REMOVED***';
    private $tlsPath = '***REMOVED***'; 
    private $databaseName ***REMOVED***
    private $client;
    private $mdb;
    
    /**
     * Constructor for MongoDBHelper.
     *
     * @param string $username - The MongoDB username for authentication.
     * @param string $password - The MongoDB password for authentication.
     * @param string $host - The MongoDB host and port.
     * @param string $auth - The authentication parameters.
     * @param string $tlsPath - The path to the TLS CA file.
     * @param string $databaseName - The name of the MongoDB database.
     */
    public function __construct() {
        $connectionString = 'mongodb://'.$this->username.':'.$this->password.'@'.$this->host.'/'.$this->auth.'&'.$this->tlsPath;
        $this->client = new MongoDB\Driver\Manager($connectionString);
        $this->mdb = $this->databaseName;
    }

    /**
     * Find a document in a collection by a specific field.
     *
     * @param string $collectionName - The name of the collection to query.
     * @param string $fieldName - The field to filter by (e.g., 'metadata.matchId').
     * @param mixed $fieldValue - The value to filter by (e.g., 'EUW1_6270020637').
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'document'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'document' contains the retrieved document (if found).
     */
    public function findDocumentByField($collectionName, $fieldName, $fieldValue, $returnAsArray = false) {
        $filter = [$fieldName => $fieldValue];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $this->client->executeQuery("{$this->mdb}.$collectionName", $query);
        
        if ($cursor->isDead()) {
            return array('success' => false, 'code' => '68CSZ1', 'message' => 'Unable to find field in document');
        } else {
            $document = current($cursor->toArray());
            if ($returnAsArray) {
                return array('success' => true, 'code' => 'M2GJCU', 'message' => 'Successfully found document by field', 'document' => (array)$document);
            } else {
                return array('success' => true, 'code' => 'M2GJCU', 'message' => 'Successfully found document by field', 'document' => $document);
            }
        }
    }

    /**
     * Deletes a document from a MongoDB collection based on a specified field's value.
     *
     * @param string $collectionName The name of the MongoDB collection.
     * @param string $fieldName The field name used as a filter for deletion.
     * @param mixed $fieldValue The field value to match for deletion.
     *
     * @return array An associative array containing the result of the delete operation:
     *   - 'success' (bool): Indicates whether the operation was successful.
     *   - 'code' (string): A code representing the result of the operation.
     *   - 'message' (string): A message providing additional information about the operation result.
     */
    public function deleteDocumentByField($collectionName, $fieldName, $fieldValue) {
        $filter = [$fieldName => $fieldValue];
        $options = ['limit' => 1]; // Limit to deleting one matching document
    
        $bulkWrite = new MongoDB\Driver\BulkWrite();
        $bulkWrite->delete($filter, $options);
    
        try {
            $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulkWrite);
            return array('success' => true, 'code' => 'BD8M4L', 'message' => 'Document deleted successfully');
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return array('success' => false, 'code' => 'LE84NG', 'message' => 'Error deleting document: ' . $e->getMessage());
        }
    }

    /**
     * Insert a document into a collection, checking for and preventing duplicates based on 'metadata.matchId'.
     *
     * @param string $collectionName - The name of the collection to insert into (e.g., 'matches', 'players', 'teams').
     * @param array $document - The document to insert (e.g., 'EUW1_.json', 'playerdata.json', 'team.json').
     *
     * @return array - An associative array with 'success', 'code', and 'message' keys.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the insert operation.
     */
    public function insertDocument($collectionName, $document) {
        if ($collectionName === 'matches') {
            if (isset($document['metadata']['matchId'])) {
                $alreadyExists = $this->findDocumentByField('matches', 'metadata.matchId', $document['metadata']['matchId'])['success'];
            }
        } elseif ($collectionName === 'players') {
            if (isset($document['PlayerData']['PUUID'])) {
                $alreadyExists = $this->findDocumentByField('players', 'PlayerData.PUUID', $document['PlayerData']['PUUID'])['success'];
            }
        } elseif ($collectionName === 'teams') {
            if (isset($document['TeamID'])) {
                $alreadyExists = $this->findDocumentByField('teams', 'TeamID', $document['TeamID'])['success'];
            }
        }

        if (!$alreadyExists) {
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->insert($document);
            $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulk);
            return array('success' => true, 'code' => '8AMZLM', 'message' => 'Successfully inserted document into '.$collectionName);
        } else {
            return array('success' => false, 'code' => 'MXZ4P5', 'message' => 'A document already exists');
        }
    }

    /**
     * Retrieve a specific field from a specific document in a specific collection.
     *
     * @param string $collectionName - The name of the collection to query (e.g., 'matches' or 'players').
     * @param string $filterField - The field to filter by (e.g., 'metadata.matchId' or 'playerdata.puuid').
     * @param mixed $filterValue - The value to filter by (e.g., 'EUW1_6270020637' or '3pA0ZcAi8b5LOgjZ...').
     * @param string $fieldName - The field to retrieve from the document (e.g., 'info.gameDuration' or 'playerdata.name').
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'data' contains the retrieved data (if found).
     */
    public function getDocumentField($collectionName, $filterField, $filterValue, $fieldName) {
        $filter = [$filterField => $filterValue];
        $options = ['projection' => [$fieldName => 1]];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $this->client->executeQuery("{$this->mdb}.$collectionName", $query);

        if (!$cursor->isDead()) {
            $document = current($cursor->toArray());
            if (isset($document->{$fieldName})) {
                return array('success' => true, 'code' => 'VZDDEB', 'message' => 'Successfully retrieved field value of document.', 'data' => $document->{$fieldName});
            } else {
                return array('success' => false, 'code' => '5QNYRM', 'message' => 'Field not found.');
            }
        } else {
            return array('success' => false, 'code' => 'FMLYAW', 'message' => 'Document not found or not identifiable.');
        }
    }

    /**
     * Add an element to a document in a specific collection.
     *
     * @param string $collectionName - The name of the collection where the document exists.
     * @param string $filterField - The field to filter by (e.g., 'metadata.matchId' or 'playerdata.puuid').
     * @param mixed $filterValue - The value to filter by (e.g., 'EUW1_6270020637' or '3pA0ZcAi8b5LOgjZ...').
     * @param string $arrayField - The field in the document where the element will be added.
     * @param mixed $elementToAdd - The value to add to the element field.
     *
     * @return array - An associative array with 'success', 'code', and 'message' keys.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the insert operation.
     */
    public function addElementToDocument($collectionName, $filterField, $filterValue, $arrayField, $elementToAdd) {
        $filter = [$filterField => $filterValue];
        $update = ['$set' => [$arrayField => $elementToAdd]];
        $options = ['upsert' => true]; // Create the document if it doesn't exist
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update($filter, $update, $options);
    
        $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulk);
    
        return array('success' => true, 'code' => '8AMZLM', 'message' => 'Successfully added or updated element in '.$collectionName);
    }
    
}
?>
