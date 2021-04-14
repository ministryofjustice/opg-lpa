<?php
namespace Application\Adapter;

use Exception;
use Application\Logging\LoggerTrait;
use RuntimeException;
use Aws\DynamoDb\DynamoDbClient;

/**
 * An adapter to use DynamoDB as a simple key/value store
 */
class DynamoDbKeyValueStore
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

    /**
     * @param DynamoDbClient $dynamoDbClient set Dynamo DB client
     */
    public function setDynamoDbClient(DynamoDbClient $dynamoDbClient)
    {
        $this->client = $dynamoDbClient;
    }
}
