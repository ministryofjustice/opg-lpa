<?php

namespace App\Service\Cache;

use Laminas\Cache\Exception\UnsupportedMethodCallException;
use Laminas\Cache\Storage\StorageInterface;
use Aws\DynamoDb\DynamoDbClient;
use Exception;

/**
 * Class Cache
 * @package App\Service\Cache
 */
class Cache implements StorageInterface
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
     * Cache constructor.
     * @param array $dynamoConfig
     * @param $keyPrefix
     */
    public function __construct(array $dynamoConfig, $keyPrefix)
    {
        $this->client = new DynamoDbClient($dynamoConfig['dynamodb']['client']);

        $this->tableName = $dynamoConfig['dynamodb']['settings']['table_name'];

        $this->keyPrefix = $keyPrefix;
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
        $key = [
            'S' => $this->formatKey($key)
        ];

        if (empty($value)) {
            $value = [
                'NULL' => true
            ];
        } else {
            $value = [
                'B' => $value
            ];
        }

        $this->client->putItem([
            'TableName' => $this->tableName,
            'Item' => [
                'id'      => $key,
                'value'   => $value,
            ]
        ]);

        return true;
    }

    /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::removeItem()
     */
    public function removeItem($key)
    {
        $key = [
            'S' => $this->formatKey($key)
        ];

        $this->client->deleteItem([
            'TableName' => $this->tableName,
            'Key' => [
                'id'      => $key,
            ],
        ]);

        return true;
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
                    'id' => [
                        'S' => $this->formatKey($key),
                    ],
                ],
            ]);

            $success = true;

            return $result['Item']['value']['B'];
        } catch (Exception $ignore) {
        }

        $success = false;

        return null;
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::addItem()
    */
    public function addItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::addItems()
    */
    public function addItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::checkAndSetItem()
    */
    public function checkAndSetItem($token, $key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::decrementItem()
    */
    public function decrementItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::decrementItems()
    */
    public function decrementItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::getCapabilities()
    */
    public function getCapabilities()
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::getItems()
    */
    public function getItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::getMetadata()
    */
    public function getMetadata($key)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::getMetadatas()
    */
    public function getMetadatas(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::getOptions()
    */
    public function getOptions()
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::hasItem()
    */
    public function hasItem($key)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::hasItems()
    */
    public function hasItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::incrementItem()
    */
    public function incrementItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::incrementItems()
    */
    public function incrementItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::removeItems()
    */
    public function removeItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::replaceItem()
    */
    public function replaceItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::replaceItems()
    */
    public function replaceItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::setItems()
    */
    public function setItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::setOptions()
    */
    public function setOptions($options)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::touchItem()
    */
    public function touchItem($key)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::touchItems()
    */
    public function touchItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }
}
