<?php

namespace Application\Adapter;

use Exception;
use RuntimeException;
use Aws\DynamoDb\DynamoDbClient;

/**
 * An adapter to use DynamoDB as a simple key/value store
 */
class DynamoDbKeyValueStore
{
    /**
     * The AWS client
     *
     * @var DynamoDbClient
     */
    private $client;

    /**
     * The name of the table holding the key/value store
     *
     * @var string
     */
    private $tableName;

    /**
     * The namespace to prefix keys with.
     *
     * @var string
     */
    private $keyPrefix;

    /**
     * Constructor
     *
     * @param array $config
     *
     * [
     *      'settings' => [
     *          'table_name' => 'my-table',
     *      ],
     *      'client' => [
     *          [
     *              'version' => '2012-08-10',
     *              'region' => 'eu-west-1',
     *          ],
     *      ],
     *      'keyPrefix' => 'stack-name',
     *  ]
     */
    public function __construct(array $config)
    {
        $this->tableName = $config['settings']['table_name'];

        $this->keyPrefix = ( isset($config['keyPrefix']) ) ? $config['keyPrefix'] : 'default';
    }

    /**
     * Returns the passed key, prefixed with a namespace.
     *
     * @param $key
     * @return string
     */
    private function formatKey($key)
    {
        return "{$this->keyPrefix}/{$key}";
    }

    /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::setItem()
     */
    public function setItem($key, $value)
    {
        $key = ['S' => $this->formatKey($key)];

        if (empty($value)) {
            $value = ['NULL' => true];
        } else {
            $value = ['B' => $value];
        }

        $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'id'      => $key,
                'value'   => $value,
            ]
        ]);
    }

    /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::removeItem()
     */
    public function removeItem($key)
    {
        $key = ['S' => $this->formatKey($key)];

        $this->client->deleteItem([
            'TableName' => $this->tableName,
            'Key' => [
                'id'      => $key,
            ]
        ]);
    }

    /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getItem()
     */
    public function getItem($key, &$success = null, &$casToken = null)
    {
        try {
            $result = $this->client->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'id'      => ['S' => $this->formatKey($key)],
                ]
            ]);

            $success = true;

            return $result['Item']['value']['B'] ?? null;
        } catch (Exception $e) {
            // Ignore exception
        }

        $success = false;

        return null;
    }

    /**
     * @param DynamoDbClient $dynamoDbClient set Dynamo DB client
     */
    public function setDynamoDbClient(DynamoDbClient $dynamoDbClient)
    {
        $this->client = $dynamoDbClient;
    }
}
