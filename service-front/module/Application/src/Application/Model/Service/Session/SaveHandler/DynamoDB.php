<?php
namespace Application\Model\Service\Session\SaveHandler;

use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;

// The 3 entries below can be removed if the patch is accepted.
use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\LockingSessionConnection;
use Aws\DynamoDb\StandardSessionConnection;

use Zend\Session\SaveHandler\SaveHandlerInterface;

/**
 * Extends Amazon's DynamoDb Session Handler to we can apply Zend's Save Handler Interface.
 *
 * We don't need to do anything else here.
 *
 * NOTE - Amazon does not use late static binding in fromClient() which is very naughty.
 *          I've submitted a pull request, but until then, we have to override fromClient().
 *
 * Class DynamoDB
 * @package Application\Model\Service\Session\SaveHandler
 */
class DynamoDB extends DynamoDbSessionHandler implements SaveHandlerInterface {

    public static function fromClient(DynamoDbClient $client, array $config = [])
    {
        $config += ['locking' => false];
        if ($config['locking']) {
            $connection = new LockingSessionConnection($client, $config);
        } else {
            $connection = new StandardSessionConnection($client, $config);
        }

        return new static($connection);
    }

    /**
     * Re-enable GC. This will only ever be called out of hours.
     * See the global.php config file for details.
     *
     * It would be nice to do this with a cron as recommend by Amazon, but we don't
     * have anywhere nice to run the cronjob yet.
     *
     * @param int $maxLifetime
     * @return bool
     */
    public function gc($maxLifetime){

        $this->garbageCollect();

        // Garbage collection for a DynamoDB table must be triggered manually.
        return true;
    }

} // class
