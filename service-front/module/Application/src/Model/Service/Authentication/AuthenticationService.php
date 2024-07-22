<?php

namespace Application\Model\Service\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface as LpaAdapterInterface;
use Laminas\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\AuthenticationService as LaminasAuthenticationService;
use Laminas\Authentication\Storage\StorageInterface;
use RuntimeException;

/**
 * Used to enforce the setAdapter method to take our own AdapterInterface.
 *
 * Class AuthenticationService
 * @package Application\Model\Service\Authentication
 */
class AuthenticationService extends LaminasAuthenticationService
{
    /**
     * AuthenticationService constructor
     *
     * @param StorageInterface|null $storage
     * @param AdapterInterface|null $adapter
     */
    public function __construct(StorageInterface $storage = null, AdapterInterface $adapter = null)
    {
        //  Assert that we are using an LPA authentication adapter specifically
        if (!$adapter instanceof LpaAdapterInterface) {
            throw new RuntimeException(
                sprintf(
                    'An %s authentication adapter must be injected into %s at instantiation',
                    LpaAdapterInterface::class,
                    get_class($this)
                )
            );
        }

        parent::__construct($storage, $adapter);
    }

    /**
     * Verify against the adapter. On success this updates the persisted identity.
     * On failure it does not effect the existing identity.
     *
     * This differs from authenticate() in that clearIdentity() is never called here.
     *
     * @return bool
     */
    public function verify()
    {
        $result = $this->adapter->authenticate();

        if ($result->isValid()) {
            $this->getStorage()->write($result->getIdentity());
        }

        return $result->isValid();
    }

    /**
     * Proxy to set the email address in the adapter
     *
     * @param $email
     * @return $this
     */
    public function setEmail(#[\SensitiveParameter] $email)
    {
        /** @var LpaAdapterInterface $adapter */
        $adapter = $this->adapter;
        $adapter->setEmail($email);

        return $this;
    }

    /**
     * Proxy to set the password in the adapter
     *
     * @param $password
     * @return $this
     */
    public function setPassword(#[\SensitiveParameter] $password)
    {
        /** @var LpaAdapterInterface $adapter */
        $adapter = $this->adapter;
        $adapter->setPassword($password);

        return $this;
    }

    private function getToken()
    {
        $this->getStorage();

        $identity = $this->getIdentity();
        if (!$identity) {
            return null;
        }

        $token = $identity->token();
        if (!$token) {
            return null;
        }

        return $token;
    }

    /**
     * Get the seconds until the session expires
     *
     * @return int|null null if the session is not active/timed out,
     *     otherwise returns the remaining seconds until expiry
     */
    public function getSessionExpiry(): ?int
    {
        $token = $this->getToken();
        if ($token === null) {
            return null;
        }

        // the constructor makes sure we're using the LPA Authentication/AdapterInterface
        // which does have a getSessionExpiry() method
        /** @noinspection PhpUndefinedMethodInspection */
        /** @psalm-suppress UndefinedInterfaceMethod */
        $result = $this->adapter->getSessionExpiry($token);

        if ($result == null || !isset($result['valid']) || !$result['valid']) {
            return null;
        }

        return $result['remainingSeconds'];
    }

    /**
     * Set the seconds until the session expires
     *
     * @param $expiresInSeconds int - number of seconds from now to set expiry at
     * @return int|null null if the session is not active/timed out,
     *     otherwise returns the remaining seconds until expiry
     */
    public function setSessionExpiry(int $expiresInSeconds): ?int
    {
        $token = $this->getToken();
        if ($token === null) {
            return null;
        }

        // the constructor makes sure we're using the LPA Authentication/AdapterInterface
        // which does have a setSessionExpiry() method
        /** @noinspection PhpUndefinedMethodInspection */
        /** @psalm-suppress UndefinedInterfaceMethod */
        $result = $this->adapter->setSessionExpiry($token, $expiresInSeconds);

        if ($result == null || !isset($result['valid']) || !$result['valid']) {
            return null;
        }

        return $result['remainingSeconds'];
    }
}
