<?php
namespace Application\Model\Service\Session\SaveHandler;

use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;

use Zend\Session\SaveHandler\SaveHandlerInterface;

/**
 * Extends Amazon's DynamoDb Session Handler (mostly) so we can apply Zend's Save Handler Interface.
 *
 * Class DynamoDB
 * @package Application\Model\Service\Session\SaveHandler
 */
class DynamoDB extends DynamoDbSessionHandler implements SaveHandlerInterface {

    /**
     * Re-enable GC. This will only ever be called out of hours.
     * See the global.php config file for details.
     *
     * It would be nice to do this with a cron as recommend by Amazon, but we don't
     * have anywhere nice to run the cronjob yet.
     *
     * #TODO - This will be moved to a cron eventually.
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
