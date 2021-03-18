<?php
namespace Application\Adapter;

use Exception;
use Application\Logging\LoggerTrait;
use RuntimeException;
use Laminas\Cache\Storage\StorageInterface;
use Aws\DynamoDb\DynamoDbClient;

/**
 * An adapter to use DynamoDB as a simple key/value store
 */
class DynamoDbKeyValueStore implements StorageInterface
{
    use LoggerTrait;
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
        $key = array('S' => $this->formatKey($key));

        if (empty($value)) {
            $value = array('NULL' => true);
        } else {
            $value = array('B' => $value);
        }

        $this->getLogger()->err(sprintf(
            "{DynamoDbKeyValueStore:setItem} [table: %s] [key: %s] [value: %s]",
            $this->tableName,
            json_encode($key),
            json_encode($value)
        ));

        $this->client->putItem(array(
            'TableName' => $this->tableName,
            'Item' => array(
                'id'      => $key,
                'value'   => $value,
            )
        ));
    }

    /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::removeItem()
     */
    public function removeItem($key)
    {
        $key = array('S' => $this->formatKey($key));


        $this->getLogger()->err(sprintf(
            "{DynamoDbKeyValueStore:removeItem} [table: %s] [key: %s] ",
            $this->tableName,
            json_encode($key)
        ));

        $this->client->deleteItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                'id'      => $key,
            )
        ));
    }

    /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getItem()
     */
    public function getItem($key, & $success = null, & $casToken = null)
    {
        $this->getLogger()->err(sprintf(
            "{DynamoDbKeyValueStore:getItem} [table: %s] [key: %s] ",
            $this->tableName,
            $key
        ));


        try {
            $result = $this->client->getItem(array(
                'TableName' => $this->tableName,
                'Key' => array(
                    'id'      => array('S' => $this->formatKey($key)),
                )
            ));

            $success = true;

            return $result['Item']['value']['B'];
        } catch (Exception $e) {
            // Ignore exception
        }

        $success = false;

        return null;
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::addItem()
     */
    public function addItem($key, $value)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::addItems()
     */
    public function addItems(array $keyValuePairs)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::checkAndSetItem()
     */
    public function checkAndSetItem($token, $key, $value)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::decrementItem()
     */
    public function decrementItem($key, $value)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::decrementItems()
     */
    public function decrementItems(array $keyValuePairs)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getCapabilities()
     */
    public function getCapabilities()
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getItems()
     */
    public function getItems(array $keys)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getMetadata()
     */
    public function getMetadata($key)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getMetadatas()
     */
    public function getMetadatas(array $keys)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::getOptions()
     */
    public function getOptions()
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::hasItem()
     */
    public function hasItem($key)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::hasItems()
     */
    public function hasItems(array $keys)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::incrementItem()
     */
    public function incrementItem($key, $value)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::incrementItems()
     */
    public function incrementItems(array $keyValuePairs)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::removeItems()
     */
    public function removeItems(array $keys)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::replaceItem()
     */
    public function replaceItem($key, $value)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::replaceItems()
     */
    public function replaceItems(array $keyValuePairs)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::setItems()
     */
    public function setItems(array $keyValuePairs)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::setOptions()
     */
    public function setOptions($options)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::touchItem()
     */
    public function touchItem($key)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Laminas\Cache\Storage\StorageInterface::touchItems()
     */
    public function touchItems(array $keys)
    {
        throw new RuntimeException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

    /**
     * @param DynamoDbClient $dynamoDbClient set Dynamo DB client
     */
    public function setDynamoDbClient(DynamoDbClient $dynamoDbClient)
    {
        $this->client = $dynamoDbClient;
    }
}
