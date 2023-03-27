<?php

namespace Application\Command;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Exception\ResourceNotFoundException;
use Laminas\ServiceManager\ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Acquires the lock stored in the dynamodb table.
 *
 * Options:
 *      --name:     Lock name within the table.
 *      --table:    DynamoDB table name.
 *      --ttl:      Time to hold the lock for.
 *      --endpoint: The DynamoDB endpoint to use. Defaults to none.
 *      --version:  The endpoint version to use. Defaults to 2012-08-10.
 *      --region:   AWS region the table is in. Defaults to eu-west-1.
 */
class LockCommand extends Command
{
    /**
     * Factory method
     *
     * @param ServiceManager $sm
     */
    public function __invoke(ServiceManager $sm): static
    {
        return $this;
    }

    protected function configure(): void
    {
        $this->addOption(
            'name',
            null,
            InputOption::VALUE_REQUIRED,
            'Name of this lock within the DynamoDB table'
        );

        $this->addOption(
            'table',
            null,
            InputOption::VALUE_REQUIRED,
            'DynamoDB table name'
        );

        $this->addOption(
            'ttl',
            null,
            InputOption::VALUE_REQUIRED,
            'Length of time in seconds the lock should be held for, if acquired'
        );

        $this->addOption(
            'endpoint',
            null,
            InputOption::VALUE_OPTIONAL,
            'DynamoDB endpoint',
            null
        );

        $this->addOption(
            'endpointVersion',
            null,
            InputOption::VALUE_OPTIONAL,
            'End point version; defaults to 2012-08-10',
            '2012-08-10'
        );

        $this->addOption(
            'region',
            null,
            InputOption::VALUE_OPTIONAL,
            'AWS region; defaults to eu-west-1',
            'eu-west-1'
        );
    }

    /**
     * Acquire lock in dynamodb table
     *
     * @return int
     *
     * @psalm-return 0|1
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $tableName = $input->getOption('table');

        $dynamoDb = new DynamoDbClient([
            'endpoint' => $input->getOption('endpoint'),
            'version' => $input->getOption('endpointVersion'),
            'region' => $input->getOption('region'),
        ]);

        // Current time in milliseconds
        $time = round(microtime(true) * 1000);

        // If the existing lock is older than this time, we can take the lock
        $takeLockIfOlderThan = $time - (intval($input->getOption('ttl')) * 1000);

        $updateJson = [
                'TableName' => $tableName,
                'Key' => [
                    'id' => [
                        'S' => $input->getOption('name'),
                    ]
                ],
                'ExpressionAttributeNames' => [
                    '#updated' => 'updated',
                ],
                'ExpressionAttributeValues' => [
                    ':updated' => [ 'N' => (string)$time ],
                    ':diff' => [ 'N' => (string)$takeLockIfOlderThan ],
                ],
                // If the lock is old, or the row doesn't exist...
                'ConditionExpression' => '#updated < :diff or attribute_not_exists(#updated)',
                'UpdateExpression' => 'SET #updated=:updated',
                'ReturnValues' => 'NONE',
                'ReturnConsumedCapacity' => 'NONE'
            ];

        // Try to take the lock 10 times
        $tries = 0;
        $success = false;

        while ((!$success) && $tries < 10) {
            try {
                $dynamoDb->updateItem($updateJson);

                // No exception means we got the lock.
                // Otherwise a ConditionalCheckFailedException is thrown.
                echo "Acquired lock\n";
                $success = true;
            } catch (DynamoDbException $e) {
                $tries++;

                // We expect a ConditionalCheckFailedException
                // Anything else is a 'real' exception.
                if ($e->getAwsErrorCode() !== 'ConditionalCheckFailedException') {
                    echo "Unable to get lock on attempt $tries due to exception: " . $e->getMessage() . "\n";
                    echo "Will attempt to acquire lock again...\n";
                }

                if ($tries < 10) {
                    sleep(6);
                }
            }
        }

        if (!$success) {
            echo "Unable to acquire lock\n";
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
