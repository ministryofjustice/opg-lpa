<?php
namespace DynamoQueue;

use Aws\DynamoDb\DynamoDbClient;

abstract class AbstractClient {

    /*
     * Valid states:
     *  - not-in-queue
     *  - waiting
     *  - processing
     *  - done
     *  - error
     *
     */

    /** @var DynamoDbClient The DynamoDB client */
    protected $client;


    /** @var array The queue's config options */
    protected $config;


    /**
     * A run partition is an integer used as a hash in DynamoDB. The more run partitions used the
     * better DynamoDB can scale.
     *
     * However also the more run partitions used, the less predictable job execution order will be.
     *
     * The identical value must be set everywhere within the same queue system.
     *
     * @var int The number of run partitions to use. Must be an int >= 1.
     */
    protected $runPartitions;

    //--------------------

    public function __construct( DynamoDbClient $client, array $config = [] ){

        $this->client = $client;

        $this->config = $config + [
                'table_name' => 'queue',
                'run_partitions'  => 1,
        ];

        // Ensure $this->runPartitions is set with a valid value.
        if( is_int($this->config['run_partitions']) && $this->config['run_partitions'] > 1 ){
            $this->runPartitions = $this->config['run_partitions'];
        } else {
            $this->runPartitions = 1;
        }

    } // function


    //---------------------------------------------------------------------------------------
    // Job management functions

    /**
     * Removes the passed jobId from the queue.
     *
     * @param $id
     * @return bool True if the item existed and was deleted. False otherwise.
     */
    public function dequeue( $id ){

        $result = $this->client->deleteItem([
            'TableName'      => $this->config['table_name'],
            'Key'            => ['id' => ['S' => $id]],
            'ReturnValues'   => 'ALL_OLD',
        ]);

        // Attributes will be populated iff the item existed.
        return isset($result['Attributes']);

    } // function

    //---------------------------------------------------------------------------------------
    // Table management functions

    /**
     * Generates a new table to use as a queue in DynamoDB.
     *
     * WARNING: This only provisions 1 read and 1 write unit each for the Table and
     *          the Global Secondary Index. This is fine for testing but should be
     *          changed to a more suitable value for production.
     *
     * @param $name string The table's name.
     */
    public function createTable( $name ){

        $table = [

            'TableName' => $name,

            'ProvisionedThroughput' => [
                'ReadCapacityUnits' => 1,
                'WriteCapacityUnits' => 1,
            ],

            'AttributeDefinitions' => [
                [
                    'AttributeName'=>'id',
                    'AttributeType'=>'S',
                ],
                [
                    'AttributeName'=>'run_after',
                    'AttributeType'=>'N',
                ],
                [
                    'AttributeName'=>'run_partition',
                    'AttributeType'=>'N',
                ],
            ], // AttributeDefinitions

            'KeySchema' => [
                [
                    'AttributeName' => 'id',
                    'KeyType' => 'HASH',
                ]
            ],

            'GlobalSecondaryIndexes' => [
                [
                    'IndexName' => 'partition-order-index',

                    'KeySchema' => [
                        [
                            'AttributeName' => 'run_partition',
                            'KeyType' => 'HASH',
                        ],
                        [
                            'AttributeName' => 'run_after',
                            'KeyType' => 'RANGE',
                        ]
                    ], // KeySchema

                    'ProvisionedThroughput' => [
                        'ReadCapacityUnits' => 1,
                        'WriteCapacityUnits' => 1,
                    ],

                    'Projection' => [
                        'ProjectionType' => 'INCLUDE',
                        'NonKeyAttributes' => [
                            'id',
                            'processor',
                        ],
                    ],

                ],
            ], // GlobalSecondaryIndexes

        ];

        $this->client->createTable( $table );

    } // function

    /**
     * Validates that the named DynamoDB table is setup correctly for the queue.
     *
     * @return true|array True if all is well. An array of errors otherwise.
     */
    public function isTableValid(){

        $details = $this->client->describeTable([
            'TableName' => $this->config['table_name']
        ]);

        //--------------

        $errors = array();

        //--------------

        // Check the table is active.

        if( !in_array( $details->getPath('Table/TableStatus'), [ 'ACTIVE', 'UPDATING' ] ) ){
            $errors[] = "The table's status is not ACTIVE (or UPDATING)";
        }

        //--------------

        // Check the Primary Key

        $keySchema = $details->getPath('Table/KeySchema');

        if( count($keySchema) != 1 ){

            $errors[] = "The Table Primary Key should be Hash (not Hash and Range)";

        } else {

            $keySchema = array_pop($keySchema);

            if( $keySchema['AttributeName'] !== "id" || $keySchema['KeyType'] !== "HASH" ){
                $errors[] = "Primary Key should be on attribute 'id' (String)";
            }


        }

        //--------------

        // Check the Global Secondary Indexes

        $globalSecondaryIndexes = $details->getPath('Table/GlobalSecondaryIndexes');

        if( count($globalSecondaryIndexes) != 1 ){

            // Check we only have 1 Global Secondary Indexe

            $errors[] = "Table should have only a single Global Secondary Index (partition-order-index)";

        } else {

            $globalSecondaryIndexes = array_pop($globalSecondaryIndexes);

            // Check it's named correctly

            if( $globalSecondaryIndexes['IndexName'] !== 'partition-order-index' ){
                $errors[] = "The Global Secondary Index must be called 'partition-order-index'";
            }

            // Check it's accessible.

            if( !in_array( $globalSecondaryIndexes['IndexStatus'], [ 'ACTIVE', 'UPDATING' ] ) ){
                $errors[] = "The Global Secondary Index is not ACTIVE (or UPDATING)";
            }

            //---

            $hashFound = false;
            $rangeFound = false;

            // Check it uses the required keys correctly

            foreach( $globalSecondaryIndexes['KeySchema'] as $key ){

                if( $key['AttributeName'] === 'run_partition' && $key['KeyType'] === 'HASH' ){
                    $hashFound = true;
                }

                if( $key['AttributeName'] === 'run_after' && $key['KeyType'] === 'RANGE' ){
                    $rangeFound = true;
                }

            } // foreach

            if( !$hashFound ){
                $errors[] = "The Global Secondary Index must have a HASH key on 'run_partition' (Number)";
            }

            if( !$rangeFound ){
                $errors[] = "The Global Secondary Index must have a RANGE key on 'run_after' (Number)";
            }

            //---

            // Check the required attributes are projected.

            if( !is_array( $globalSecondaryIndexes['Projection']['NonKeyAttributes'] ) ){
                $errors[] = "The Global Secondary Index must Project the 'id' and 'processor' attributes";
            } else {

                if( !in_array( 'id', $globalSecondaryIndexes['Projection']['NonKeyAttributes'] ) ){
                    $errors[] = "The Global Secondary Index must Project the 'id' attribute";
                }

                if( !in_array( 'processor', $globalSecondaryIndexes['Projection']['NonKeyAttributes'] ) ){
                    $errors[] = "The Global Secondary Index must Project the 'processor' attribute";
                }

            }

        }

        //---

        return ( empty($errors) ) ? true : $errors;

    } // function

} // class
