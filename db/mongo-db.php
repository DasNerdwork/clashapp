<?php 
require_once '/hdd1/clashapp/vendor/autoload.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Sanitisiert MongoDB Query-Werte um Injection-Angriffe zu verhindern
 * Erlaubt nur alphanumerische Zeichen, _, -, . und begrenzte Sonderzeichen
 */
function sanitizeMongoQueryValue($value) {
    if ($value === null || $value === '') {
        return null;
    }
    
    // String-Werte
    if (is_string($value)) {
        // Nur alphanumerische Zeichen, _, -, . und begrenzte Sonderzeichen erlauben
        if (preg_match('/^[a-zA-Z0-9_\-\.#\/\@\+\s:]*$/', $value)) {
            return $value;
        }
        return null;
    }
    
    // Integer-Werte
    if (is_int($value) || is_numeric($value)) {
        return (int)$value;
    }
    
    // Array-Werte (jeden Element validieren)
    if (is_array($value)) {
        $sanitized = [];
        foreach ($value as $item) {
            $sanitizedItem = sanitizeMongoQueryValue($item);
            if ($sanitizedItem !== null) {
                $sanitized[] = $sanitizedItem;
            }
        }
        return !empty($sanitized) ? $sanitized : null;
    }
    
    // Boolean-Werte
    if (is_bool($value)) {
        return $value ? 1 : 0;
    }
    
    return null; // Ungültiger Typ
}

/**
 * MongoDB Helper Class
 * 
 * @author DasNerdwork
 * @copyright 2026
 */
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
     */
    public function __construct() {
        $this->host = getenv('MDB_HOST');
        $this->username = getenv('MDB_USER');
        $this->password = getenv('MDB_PW');
        $this->databaseName = getenv('MDB_DB');
        
        // Build connection string for local MongoDB without TLS
        // Format: mongodb://user:pass@host/db
        $connectionString = 'mongodb://'.$this->username.':'.$this->password.'@'.$this->host.'/'.$this->databaseName;
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
     */
    public function findDocumentByField($collectionName, $fieldName, $fieldValue) {
        // Sanitize den Query-Wert
        $sanitizedValue = sanitizeMongoQueryValue($fieldValue);
        if ($sanitizedValue === null) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'Invalid input value', 'document' => null);
        }
        
        $filter = [$fieldName => $sanitizedValue];
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
     */
    public function countDocuments($collectionName, $conditions = []) {
        $filter = [];

        if (!empty($conditions)) {
            // Sanitize alle Query-Werte
            $sanitizedConditions = [];
            foreach ($conditions as $key => $value) {
                $sanitizedValue = sanitizeMongoQueryValue($value);
                if ($sanitizedValue !== null) {
                    $sanitizedConditions[$key] = $sanitizedValue;
                }
            }
            $filter = $sanitizedConditions;
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
     * @param array $matchIds - An array of match IDs to filter by.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'documents'.
     */
    public function findDocumentsByMatchIds($collectionName, $fieldName, $matchIds, $fieldsToRetrieve = [], $returnAsArray = false) {
        // Sanitize alle Match IDs
        $sanitizedMatchIds = [];
        foreach ($matchIds as $matchId) {
            $sanitizedMatchId = sanitizeMongoQueryValue($matchId);
            if ($sanitizedMatchId !== null) {
                $sanitizedMatchIds[] = $sanitizedMatchId;
            }
        }
        
        if (empty($sanitizedMatchIds)) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'No valid match IDs provided', 'documents' => []);
        }
        
        $filter = [$fieldName => ['$in' => $sanitizedMatchIds]];
    
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
     * @return array An associative array containing the result of the delete operation.
     */
    public function deleteDocumentByField($collectionName, $fieldName, $fieldValue) {
        // Sanitize den Query-Wert
        $sanitizedValue = sanitizeMongoQueryValue($fieldValue);
        if ($sanitizedValue === null) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'Invalid input value for deletion');
        }

        if (!$this->findDocumentByField($collectionName, $fieldName, $fieldValue)['success']) {
            return array('success' => false, 'code' => 'NOT_FOUND', 'message' => 'Document not found, nothing to delete');
        }
        
        $filter = [$fieldName => $sanitizedValue];
        $options = ['limit' => 1];
    
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
     * @param string $collectionName - The name of the collection to insert into.
     * @param array $document - The document to insert.
     * @param array $filter - Optional filter for upsert.
     *
     * @return array - An associative array with 'success', 'code', and 'message' keys.
     */
    public function insertDocument($collectionName, $document, $filter = []) {
        $bulk = new MongoDB\Driver\BulkWrite;
        try {
            if (!empty($filter)) {
                // Sanitize Filter-Werte
                $sanitizedFilter = [];
                foreach ($filter as $key => $value) {
                    $sanitizedValue = sanitizeMongoQueryValue($value);
                    if ($sanitizedValue !== null) {
                        $sanitizedFilter[$key] = $sanitizedValue;
                    }
                }
                $filter = !empty($sanitizedFilter) ? $sanitizedFilter : $filter;
                $bulk->update(
                    $filter,
                    ['$set' => $document],
                    ['upsert' => true]
                );
            } else {
                // Insert the document if no filter is provided
                $bulk->insert($document);
            }
    
            $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulk);
    
            return [
                'success' => true,
                'code' => '8AMZLM',
                'message' => 'Successfully inserted or updated document in ' . $collectionName,
            ];
        } catch (MongoDB\Driver\Exception\BulkWriteException $e) {
            if ($e->getCode() == 11000) {
                return [
                    'success' => false,
                    'code' => 'MXZ4P5',
                    'message' => 'Document with the same key already exists in ' . $collectionName,
                ];
            } else {
                return [
                    'success' => false,
                    'code' => 'MXZZLM',
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ];
            }
        }
    }

    /**
     * Retrieve a specific field from a specific document in a specific collection.
     *
     * @param string $collectionName - The name of the collection to query.
     * @param string $filterField - The field to filter by.
     * @param mixed $filterValue - The value to filter by.
     * @param string $fieldName - The field to retrieve from the document.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     */
    public function getDocumentField($collectionName, $filterField, $filterValue, $fieldName = null) {
        // Sanitize den Query-Wert
        $sanitizedValue = sanitizeMongoQueryValue($filterValue);
        if ($sanitizedValue === null) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'Invalid filter value', 'data' => null);
        }
        
        $filter = [$filterField => $sanitizedValue];
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
     * @param string $collectionName - The name of the collection.
     * @param string $filterField - The field to filter by.
     * @param mixed $filterValue - The value to filter by.
     * @param string $arrayField - The field in the document where the element will be added.
     * @param mixed $elementToAdd - The value to add to the element field.
     *
     * @return array - An associative array with 'success', 'code', and 'message' keys.
     */
    public function addElementToDocument($collectionName, $filterField, $filterValue, $arrayField, $elementToAdd) {
        // Sanitize den Query-Wert
        $sanitizedValue = sanitizeMongoQueryValue($filterValue);
        if ($sanitizedValue === null) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'Invalid filter value');
        }
        
        $filter = [$filterField => $sanitizedValue];
        $update = ['$set' => [$arrayField => $elementToAdd]];
        $options = ['upsert' => true];
        $bulk = new MongoDB\Driver\BulkWrite;
        $bulk->update($filter, $update, $options);
    
        $this->client->executeBulkWrite("{$this->mdb}.$collectionName", $bulk);

        $result = $this->findDocumentByField($collectionName, $filterField, $filterValue);
        if($result['success']) {
            return array('success' => true, 'code' => '8AMZLM', 'message' => 'Successfully added or updated element in '.$collectionName);
        } else {
            // @codeCoverageIgnoreStart
            return array('success' => false, 'code' => 'CN4NA1', 'message' => 'Getting document by newly added field was not successful.');
            // @codeCoverageIgnoreEnd
        }
    }
    
    /**
     * Retrieve a player document based on the PlayerData.GameName and PlayerData.Tag attributes.
     *
     * @param string $gameName - The PlayerData.GameName value to search for.
     * @param string $tag - The PlayerData.Tag value to search for.
     *
     * @return array - An array with keys 'success', 'code', 'message', and 'data'.
     */
    public function getPlayerByRiotId($gameName, $tag) {
        // Sanitize die Query-Werte
        $sanitizedGameName = sanitizeMongoQueryValue($gameName);
        $sanitizedTag = sanitizeMongoQueryValue($tag);
        
        if ($sanitizedGameName === null || $sanitizedTag === null) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'Invalid game name or tag');
        }
        
        // Case-insensitive regular expression for the gameName and tag
        $gameNameRegex = new MongoDB\BSON\Regex("^$sanitizedGameName$", 'i');
        $tagRegex = new MongoDB\BSON\Regex("^$sanitizedTag$", 'i');

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
     */
    public function getPlayerByPUUID($puuid) {
        // Sanitize den Query-Wert
        $sanitizedPUUID = sanitizeMongoQueryValue($puuid);
        if ($sanitizedPUUID === null) {
            return array('success' => false, 'code' => 'INVALID_INPUT', 'message' => 'Invalid PUUID');
        }
        
        $filter = ['PlayerData.PUUID' => $sanitizedPUUID];
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
            if (!isset($document->PlayerData) || !isset($document->PlayerData->GameName)) {
                continue;
            }
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