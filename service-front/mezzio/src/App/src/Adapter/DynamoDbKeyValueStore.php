<?php

declare(strict_types=1);

namespace App\Adapter;

use Aws\DynamoDb\DynamoDbClient;
use Exception;

/**
 * An adapter to use DynamoDB as a simple key/value store.
 * Ported from Application\Adapter\DynamoDbKeyValueStore.
 */
class DynamoDbKeyValueStore
{
    private DynamoDbClient $client;
    private string $tableName;
    private string $keyPrefix;

    /**
     * @param array $config Expected keys:
     *   - settings.table_name  (string)
     *   - keyPrefix            (string, optional, defaults to 'default')
     */
    public function __construct(array $config)
    {
        $this->tableName = $config['settings']['table_name'];
        $this->keyPrefix = $config['keyPrefix'] ?? 'default';
    }

    public function setDynamoDbClient(DynamoDbClient $dynamoDbClient): void
    {
        $this->client = $dynamoDbClient;
    }

    private function formatKey(string $key): string
    {
        return "{$this->keyPrefix}/{$key}";
    }

    public function setItem(string $key, mixed $value): void
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
                'id'    => $key,
                'value' => $value,
            ],
        ]);
    }

    public function removeItem(string $key): void
    {
        $this->client->deleteItem([
            'TableName' => $this->tableName,
            'Key' => [
                'id' => ['S' => $this->formatKey($key)],
            ],
        ]);
    }

    public function getItem(string $key): mixed
    {
        try {
            $result = $this->client->getItem([
                'TableName' => $this->tableName,
                'Key' => [
                    'id' => ['S' => $this->formatKey($key)],
                ],
            ]);

            return $result['Item']['value']['B'] ?? null;
        } catch (Exception) {
            // Ignore exception
        }

        return null;
    }
}
