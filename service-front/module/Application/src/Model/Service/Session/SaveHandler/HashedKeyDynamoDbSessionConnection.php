<?php
namespace Application\Model\Service\Session\SaveHandler;

use Aws\DynamoDb\StandardSessionConnection;

/**
 * Extends Amazon's StandardSessionConnection so that we can hash the key.
 * This safeguards against exposed DynamoDB keys being used as valid session cookie values.
 *
 * Class HashedKeyDynamoDbSessionConnection
 * @package Application\Model\Service\Session\SaveHandler
 */
class HashedKeyDynamoDbSessionConnection extends StandardSessionConnection {

    /**
     * Hash the key so that it does not match the cookie value.
     *
     * @param string $key
     *
     * @return array
     */
    protected function formatKey($key)
    {
        return [$this->getHashKey() => ['S' => hash( 'sha512', $key )]];
    }

}
