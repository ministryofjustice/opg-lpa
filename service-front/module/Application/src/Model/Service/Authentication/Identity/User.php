<?php

namespace Application\Model\Service\Authentication\Identity;

use DateTime;

/**
 * Class representing the identity of a currently authenticated user.
 *
 * Class User
 * @package Application\Model\Service\Authentication\Identity
 */
class User
{
    /** @var string The user's internal ID */
    private $id;

    /** @var string The user's authentication token */
    private $token;

    /** @var DateTime The time the $token is valid till (last time we checked) */
    private $tokenExpiresAt;

    /** @var DateTime The date & time the user last logged in */
    private $lastLogin;

    /**
     * The user's roles.
     * 'admin' could be added to this.
     */
    /** @var array */
    private $roles = [ 'user' ];

    /**
     * @param string $userId The user's internal ID.
     * @param string $token The user's authentication token.
     * @param int $expiresIn The number of seconds in which the token expires.
     * @param ?DateTime $lastLogin The DateTime the user logged in, or null if they've never logged in
     * @param bool $isAdmin Whether of not the user is an admin.
     */
    public function __construct(#[\SensitiveParameter] string $userId, #[\SensitiveParameter] string $token, int $expiresIn, ?DateTime $lastLogin, bool $isAdmin = false)
    {
        $this->id = $userId;
        $this->token = $token;

        // If $lastLogin is null, they have not logged in before, so last login is now.
        if (is_null($lastLogin)) {
            $lastLogin = new DateTime();
        }

        $this->lastLogin = $lastLogin;

        $this->tokenExpiresIn($expiresIn);

        if ($isAdmin === true) {
            $this->setAsAdmin();
        }
    }

    //------

    public function id()
    {
        return $this->id;
    }

    public function token()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function lastLogin()
    {
        return $this->lastLogin;
    }

    public function tokenExpiresAt()
    {
        return $this->tokenExpiresAt;
    }

    public function roles()
    {
        return $this->roles;
    }

    public function isAdmin()
    {
        return in_array('admin', $this->roles);
    }

    /**
     * @param int $expiresIn The number of seconds in which the token expires.
     */
    public function tokenExpiresIn($expiresIn)
    {
        $this->tokenExpiresAt = (new DateTime())->setTimestamp((int)$expiresIn + time());
    }

    /**
     * Flags this user as an admin.
     */
    private function setAsAdmin()
    {
        if (!in_array('admin', $this->roles)) {
            $this->roles[] = 'admin';
        }
    }

    /**
     * Return this identity as a JSON string
     */
    public function toArray()
    {
        return get_object_vars($this);
    }
}
