<?php 
require_once '/hdd1/clashapp/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
        
class MongoDBHelper {
    private $host;
    private $username;
    private $password;
    private $auth;
    private $tlsPath; 
    private $databaseName;
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
     * @codeCoverageIgnore
     */
    public function __construct() {
        $this->host = getenv('MDB_HOST');
        $this->username = getenv('MDB_USER');
        $this->password = getenv('MDB_PW');
        $this->auth = getenv('MDB_AUTH');
        $this->tlsPath = getenv('MDB_TLS');
        $this->databaseName = getenv('MDB_DB');
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
    public function findDocumentByField($collectionName, $fieldName, $fieldValue) {
        $filter = [$fieldName => $fieldValue];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $this->client->executeQuery("{$this->mdb}.$collectionName", $query);
        
        if ($cursor->isDead()) {
            return array('success' => false, 'code' => '68CSZ1', 'message' => 'Unable to find field in document');
        } else {
            $document = current($cursor->toArray());
            return array('success' => true, 'code' => 'M54ND7', 'message' => 'Successfully found document by field', 'document' => (array)$document);
        }
    }

    /**
     * Count documents in a collection with optional conditions.
     *
     * @param string $collectionName - The name of the collection to query.
     * @param array $conditions - An associative array of conditions to filter by (optional).
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'count'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'count' contains the number of matching documents.
     */
    public function countDocuments($collectionName, $conditions = []) {
        $filter = [];

        if (!empty($conditions)) {
            $filter = $conditions;
        }

        $command = new MongoDB\Driver\Command([
            'count' => $collectionName,
            'query' => (object)$filter
        ]);

        try {
            $cursor = $this->client->executeCommand($this->mdb, $command);
            $result = current($cursor->toArray());

            if ($result->n === 0) {
                return array('success' => false, 'code' => 'LFGB29', 'message' => 'No documents found matching the criteria', 'count' => $result->n);
            } else {
                return array('success' => true, 'code' => '4J532N', 'message' => 'Successfully counted documents with given conditions', 'count' => $result->n);
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return array('success' => false, 'code' => 'DBERR', 'message' => 'Database error: ' . $e->getMessage(), 'count' => 0);
        }
    }

    /**
     * Find documents in a collection by an array of specific match IDs.
     *
     * @param string $collectionName - The name of the collection to query.
     * @param string $fieldName - The field to filter by (e.g., 'metadata.matchId').
     * @param array $matchIds - An array of match IDs to filter by (e.g., ['EUW1_6270020637', 'EUW1_6270020638']).
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'documents'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'documents' contains an array of retrieved documents (if found).
     */
    public function findDocumentsByMatchIds($collectionName, $fieldName, $matchIds, $fieldsToRetrieve = [], $returnAsArray = false) {
        $filter = [$fieldName => ['$in' => $matchIds]];
    
        $pipeline = [
            ['$match' => $filter],
            ['$project' => $this->createProjection($fieldsToRetrieve)],
        ];
    
        $command = new MongoDB\Driver\Command([
            'aggregate' => $collectionName,
            'pipeline' => $pipeline,
            'cursor' => new stdClass,
        ]);
    
        $cursor = $this->client->executeCommand($this->mdb, $command);

        if ($cursor->isDead()) {
            // @codeCoverageIgnoreStart
            return array('success' => false, 'code' => '68CSZ1', 'message' => 'An unexpected error occured, aggregate should have returned something but was completely empty');
            // @codeCoverageIgnoreEnd
        } else {
            $documents = $cursor->toArray();

            $isEmptyInfo = false;
            foreach ($documents as $document) {
                if (empty((array)$document->info)) {
                    $isEmptyInfo = true;
                    break;
                }
            }

            if (!empty($documents)) {
                if ($isEmptyInfo) {
                    return array('success' => true, 'code' => 'D4NG3R', 'message' => 'Documents found, but some have empty "info" key', 'documents' => $documents);
                } else {
                    $result = array_map(function ($document) use ($returnAsArray) {
                        return $returnAsArray ? (array)$document : $document;
                    }, $documents);
                    return array('success' => true, 'code' => 'MO34LAN', 'message' => 'Successfully found documents by match IDs', 'documents' => $result);
                }
            } else {
                return array('success' => false, 'code' => 'KB6DL0', 'message' => 'Document content is empty');
            }
        }
    }
    
    private function createProjection($fieldsToRetrieve) {
        $projection = [];
    
        foreach ($fieldsToRetrieve as $field) {
            // Handle fields that start with '$' in a special way
            $fieldParts = explode('.', $field);
            $modifiedField = implode('.', array_map(function ($part) {
                return strpos($part, '$') === 0 ? substr($part, 1) : $part;
            }, $fieldParts));
    
            // Set the modified field in the projection
            $projection[$modifiedField] = 1;
        }
    
        return $projection;
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
        $documentExists = $this->findDocumentByField($collectionName, $fieldName, $fieldValue)['success'];
        if(!$documentExists) return array('success' => false, 'code' => 'DL4MN2', 'message' => 'Unable to delete non-existent document');
        $filter = [$fieldName => $fieldValue];
        $options = ['limit' => 1]; // Limit to deleting one matching document
    
        $bulkWrite = new MongoDB\Driver\BulkWrite();
        $bulkWrite->delete($filter, $options);
    
        try {
            $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulkWrite);
            if(!$this->findDocumentByField($collectionName, $fieldName, $fieldValue)['success']){
                return array('success' => true, 'code' => 'BD8M4L', 'message' => 'Document deleted successfully');
            } else {
                // @codeCoverageIgnoreStart
                return array('success' => false, 'code' => 'D3NF12', 'message' => 'Unable to delete document because of unknown reason');
            }
        } catch (MongoDB\Driver\Exception\Exception $e) {
            return array('success' => false, 'code' => 'LE84NG', 'message' => 'Error deleting document: ' . $e->getMessage());
            // @codeCoverageIgnoreEnd
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
        $bulk = new MongoDB\Driver\BulkWrite;
        try {
            // Try to insert the document
            $bulk->insert($document);
            $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulk);
    
            return [
                'success' => true,
                'code' => '8AMZLM',
                'message' => 'Successfully inserted document into ' . $collectionName,
            ];
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            if ($e->getCode() == 11000) {
                // Duplicate key error
                return [
                    'success' => false,
                    'code' => 'MXZ4P5',
                    'message' => 'Document with the same key already exists in ' . $collectionName,
                ];
            } else {
                // @codeCoverageIgnoreStart
                return [
                    'success' => false,
                    'code' => 'MXZZLM',
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ];
                // @codeCoverageIgnoreEnd
            }
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
    public function getDocumentField($collectionName, $filterField, $filterValue, $fieldName = null) {
        $filter = [$filterField => $filterValue];
        $options = [];
        if ($fieldName !== null) $options['projection'] = [$fieldName => 1];
        $query = new MongoDB\Driver\Query($filter, $options);
        $cursor = $this->client->executeQuery("{$this->mdb}.$collectionName", $query);

        if (!$cursor->isDead()) {
            $document = current($cursor->toArray());
            
            if ($fieldName !== null && isset($document->{$fieldName})) {
                return array('success' => true, 'code' => 'VZDDEB', 'message' => 'Successfully retrieved field value of document.', 'data' => $document->{$fieldName});
            } 
            elseif ($fieldName === null) {
                return array('success' => true, 'code' => 'DM83BG', 'message' => 'Successfully retrieved whole document.', 'data' => $document);
            }
            else {
                return array('success' => false, 'code' => '5QNYRM', 'message' => 'An unknown error occured with field value.');
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

        $result = $this->findDocumentByField($collectionName, $arrayField, $elementToAdd);
        if($result['success']) {
            return array('success' => true, 'code' => '8AMZLM', 'message' => 'Successfully added or updated element in '.$collectionName);
        } else {
            // @codeCoverageIgnoreStart
            return array('success' => false, 'code' => 'CN4NA1', 'message' => 'Getting document by newly added field was not successful.');
            // @codeCoverageIgnoreEnd
        }
    }
    
    /**
     * Retrieve a player document based on the PlayerData.SumID attribute.
     *
     * @param string $summonerId - The PlayerData.SumID value to search for.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'data' contains the retrieved player document (if found).
     */
    public function getPlayerBySummonerId($summonerId) {
        $filter = ['PlayerData.SumID' => $summonerId];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $this->client->executeQuery("{$this->mdb}.players", $query);

        if (!$cursor->isDead()) {
            $document = current($cursor->toArray());
            return array('success' => true, 'code' => 'AK4MF0', 'message' => 'Successfully retrieved player document.', 'data' => $document);
        } else {
            return array('success' => false, 'code' => '0PPA1', 'message' => 'Player document not found.');
        }
    }
    
    /**
     * Retrieve a player document based on the PlayerData.SumID attribute.
     *
     * @param string $riotId - The PlayerData.GameName value to search for.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'data' contains the retrieved player document (if found).
     */
    public function getPlayerByRiotId($gameName, $tag) {
        // Case-insensitive regular expression for the gameName and tag
        $gameNameRegex = new MongoDB\BSON\Regex("^$gameName$", 'i');
        $tagRegex = new MongoDB\BSON\Regex("^$tag$", 'i');

        $filter = [
            'PlayerData.GameName' => $gameNameRegex,
            'PlayerData.Tag' => $tagRegex
        ];
        
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $this->client->executeQuery("{$this->mdb}.players", $query);

        if (!$cursor->isDead()) {
            $document = current($cursor->toArray());
            return array('success' => true, 'code' => 'AM5A3', 'message' => 'Successfully retrieved player document.', 'data' => $document);
        } else {
            return array('success' => false, 'code' => 'POL4M', 'message' => 'Player document not found.');
        }
    }

    /**
     * Retrieve a player document based on the PlayerData.PUUID attribute.
     *
     * @param string $puuid - The PlayerData.PUUID value to search for.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'data' contains the retrieved player document (if found).
     */
    public function getPlayerByPUUID($puuid) {
        $filter = ['PlayerData.PUUID' => $puuid];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $this->client->executeQuery("{$this->mdb}.players", $query);

        if (!$cursor->isDead()) {
            $document = current($cursor->toArray());
            return array('success' => true, 'code' => 'AK4MF0', 'message' => 'Successfully retrieved player document.', 'data' => $document);
        } else {
            return array('success' => false, 'code' => '0PPA1', 'message' => 'Player document not found.');
        }
    }

    /**
     * Retrieve PlayerData.Name and PlayerData.Icon, sort them alphabetically, and format as specified.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     *   'success' determines the success of the operation.
     *   'code' provides a code for reference.
     *   'message' describes the outcome of the operation.
     *   'data' contains the sorted data.
     */
    public function getAutosuggestAggregate() {
        // Aggregation pipeline
        $pipeline = [
            [
                '$project' => [
                    'PlayerData.GameName' => 1,
                    'PlayerData.Tag' => 1,
                    'PlayerData.Icon' => 1,
                    '_id' => 0
                ]
            ],
            [
                '$sort' => [
                    'PlayerData.GameName' => 1
                ]
            ]
        ];

        // Execute aggregation query
        $cursor = $this->client->executeCommand("{$this->mdb}", new MongoDB\Driver\Command([
            'aggregate' => 'players',
            'pipeline' => $pipeline,
            'cursor' => new stdClass,
        ]));

        $result = [];

        foreach ($cursor as $document) {
            $result[$document->PlayerData->GameName.'#'.$document->PlayerData->Tag] = $document->PlayerData->Icon;
        }

        if (!empty($result)) {
            return array('success' => true, 'code' => 'DJF64L', 'message' => 'Successfully retrieved and sorted PlayerData.', 'data' => $result);
        } else {
            // @codeCoverageIgnoreStart
            return array('success' => false, 'code' => '0DL3MU', 'message' => 'No PlayerData found.');
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Execute an aggregation pipeline on a collection.
     *
     * @param string $collectionName The name of the collection to aggregate.
     * @param array $pipeline The aggregation pipeline.
     * @param array $options The options for the aggregate operation.
     *
     * @return MongoDB\Driver\Cursor The cursor containing the aggregation results.
     */
    public function aggregate($collectionName, $pipeline, $options = []) {
        $command = new MongoDB\Driver\Command([
            'aggregate' => $collectionName,
            'pipeline' => $pipeline,
            'cursor' => new stdClass(),
        ]);

        $cursor = $this->client->executeCommand($this->mdb, $command);

        try {
            $cursor = $this->client->executeCommand($this->mdb, $command);
            $documents = $cursor->toArray();
    
            if (empty($documents)) {
                return null;
            }
    
            return $documents;
            // @codeCoverageIgnoreStart
        } catch (\Exception $e) {
            return null;
            // @codeCoverageIgnoreEnd
        }
    }
}
?>
