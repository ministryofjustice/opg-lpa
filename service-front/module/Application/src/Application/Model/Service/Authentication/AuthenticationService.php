<?php

namespace Application\Model\Service\Authentication;

use Zend\Authentication\AuthenticationService as ZFAuthenticationService;
use Zend\Authentication\Exception\RuntimeException;

/**
 * Used to enforce the setAdapter method to take our own AdapterInterface.
 *
 * Class AuthenticationService
 * @package Application\Model\Service\Authentication
 */
class AuthenticationService extends ZFAuthenticationService
{
    /**
     * Verify against the supplied adapter. On success this updates the persisted identity.
     * On failure it does not effect the existing identity.
     *
     * This differs from authenticate() in that clearIdentity() is never called here.
     *
     * @param  Adapter\AdapterInterface $adapter
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Exception\RuntimeException
     */
    public function verify(Adapter\AdapterInterface $adapter = null)
    {
        if (!$adapter) {
            if (!$adapter = $this->getAdapter()) {
                throw new RuntimeException('An adapter must be set or passed prior to calling verify()');
            }
        }

        $result = $adapter->authenticate();

        if ($result->isValid()) {
            $this->getStorage()->write($result->getIdentity());
        }

        return $result;
    }
}
