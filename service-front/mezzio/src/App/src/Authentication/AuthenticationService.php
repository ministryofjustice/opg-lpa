<?php

declare(strict_types=1);

namespace App\Authentication;

use Application\Model\Service\Authentication\Adapter\AdapterInterface;
use Laminas\Authentication\Result;

/**
 * Mezzio-native authentication service.
 *
 * Wraps the LPA API adapter without depending on laminas/laminas-session.
 * Session storage is handled directly by the Mezzio LoginHandler using
 * Mezzio\Session instead.
 */
class AuthenticationService
{
    public function __construct(private readonly AdapterInterface $adapter)
    {
    }

    public function setEmail(string $email): static
    {
        $this->adapter->setEmail($email);
        return $this;
    }

    public function setPassword(string $password): static
    {
        $this->adapter->setPassword($password);
        return $this;
    }

    public function authenticate(): Result
    {
        return $this->adapter->authenticate();
    }
}
