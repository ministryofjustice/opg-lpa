<?php

namespace App\Service\Cache;

use Laminas\Cache\Exception\UnsupportedMethodCallException;
use Laminas\Cache\Storage\Adapter\AdapterOptions;
use Laminas\Cache\Storage\StorageInterface;
use Aws\DynamoDb\DynamoDbClient;
use Exception;
use Traversable;

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
     * @param array<string, mixed> $dynamoConfig
     * @param string $keyPrefix
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
     * @param string $key
     * @return string
     */
    private function formatKey($key)
    {
        return "{$this->keyPrefix}/{$key}";
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::setItem()
     *
     * @param string $key
     * @param mixed $value
     * @return bool
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
    public function getItem($key, &$success = null, #[\SensitiveParameter] &$casToken = null)
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

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::addItems()
     *
     * @param array $keyValuePairs
     * @return array
     */
    public function addItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /* (non-PHPdoc)
    * @see \Laminas\Cache\Storage\StorageInterface::checkAndSetItem()
    */
    public function checkAndSetItem(#[\SensitiveParameter] $token, $key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::decrementItem()
     *
     * @param string $key
     * @param int $value
     * @return int|bool The new value on success, false on failure
     */
    public function decrementItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::decrementItems()
     *
     * @param array $keyValuePairs
     * @return array Associative array of new values
     */
    public function decrementItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::getCapabilities()
     */
    public function getCapabilities()
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::getItems()
     *
     * @param array $keys
     * @return array Associative array of keys and values
     */
    public function getItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::getMetadata()
     *
     * @param string $key
     * @return array<string, mixed>|bool Metadata on success, false on failure
     */
    public function getMetadata($key)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::getMetadatas()
     *
     * @param array $keys
     * @return array Associative array of keys and metadataa
     */
    public function getMetadatas(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::getOptions()
     *
     * @return \Laminas\Cache\Storage\Adapter\AdapterOptions
     */
    public function getOptions()
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::hasItem()
     *
     * @param string $key
     * @return bool
     */
    public function hasItem($key)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::hasItems()
     *
     * @param array $keys
     * @return array Array of found keys
     */
    public function hasItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::incrementItem()
     *
     * @param string $key
     * @param int $value
     * @return int|bool The new value on success, false on failure
     */
    public function incrementItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::incrementItems()
     *
     * @param array $keyValuePairs
     * @return array Associative array of new values
     */
    public function incrementItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::removeItems()
     *
     * @param array $keys
     * @return array Array of not removed keys
     */
    public function removeItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::replaceItem()
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function replaceItem($key, $value)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::replaceItems()
     *
     * @param array $keyValuePairs
     * @return array Array of not replaced keys
     */
    public function replaceItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::setItems()
     *
     * @param array $keyValuePairs
     * @return array Array of not set keys
     */
    public function setItems(array $keyValuePairs)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::setOptions()
     *
     * @param AdapterOptions|Traversable|array $options
     * @return $this
     */
    public function setOptions($options)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::touchItem()
     *
     * @param string $key
     * @return bool
     */
    public function touchItem($key)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @see \Laminas\Cache\Storage\StorageInterface::touchItems()
     *
     * @param array $keys
     * @return array Array of not updated keys
     */
    public function touchItems(array $keys)
    {
        throw new UnsupportedMethodCallException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }
}
