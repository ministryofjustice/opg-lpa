<?php

namespace Application\Model\Service\ApiClient\Response;

/**
 * Class AuthResponse
 * @package Application\Model\Service\ApiClient\Response
 */
class AuthResponse
{
    /**
     * The ID of the currently authenticated user.
     */
    /** @var string */
    private $userId;

    /**
     * The user identity
     */
    /** @var string */
    private $username;

    /**
     * The authentication token
     */
    /** @var string */
    private $token;

    /**
     * Minutes until expiry
     */
    /** @var int */
    private $expiresIn;

    /**
     * Date and time of expiry
     */
    /** @var string */
    private $expiresAt;

    /**
     * Unix timestamp representing the last time this user logged in
     */
    /** @var string */
    private $lastLogin;

    /**
     * Boolean value representing whether inactivity flags were cleared during the authentication
     */
    /** @var boolean */
    private $inactivityFlagsCleared;

    /**
     * The error description, if any
     */
    /** @var string */
    private $errorDescription;

    /**
     * Constructor.
     * Populates the member variables of the instance from an array.
     *
     * @param array $array
     */
    public function __construct(array $array = [])
    {
        $this->userId = isset($array['userId']) ? $array['userId'] : null;
        $this->token = isset($array['token']) ? $array['token'] : null;

        // If last_login is not set (user has never logged in before), set to null
        $this->lastLogin = $array['last_login'] ?? null;

        $this->username = isset($array['username']) ? $array['username'] : null;
        $this->expiresIn = isset($array['expiresIn']) ? $array['expiresIn'] : null;
        $this->expiresAt = isset($array['expiresAt']) ? $array['expiresAt'] : null;
        $this->inactivityFlagsCleared = isset($array['inactivityFlagsCleared']) ?
            $array['inactivityFlagsCleared'] : null;
    }

    /**
     * Factory method
     *
     * @param array $result
     * @return AuthResponse
     */
    public static function buildFromResponse(array $result)
    {
        return new AuthResponse($result);
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return string variable $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @return int
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @return string $expiresAt
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @return string $lastLogin
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @return string $username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @return boolean $inactivityFlagsCleared
     */
    public function getInactivityFlagsCleared()
    {
        return $this->inactivityFlagsCleared;
    }

    /**
     * @return string $errorDescription
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * @param string $errorDescription
     * @return $this
     */
    public function setErrorDescription($errorDescription)
    {
        $this->errorDescription = $errorDescription;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return !empty($this->userId) && !empty($this->token) && empty($this->errorDescription);
    }
}
