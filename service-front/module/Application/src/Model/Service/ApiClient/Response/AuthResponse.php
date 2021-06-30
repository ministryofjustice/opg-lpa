<?php

namespace Application\Model\Service\ApiClient\Response;

/**
 * Class AuthResponse
 * @package Application\Model\Service\ApiClient\Response
 */
class AuthResponse
{
    /**
     * @param array $result
     * @return static
     */
    public static function buildFromResponse(array $result)
    {
        $authResponse = new static();
        $authResponse->exchangeArray($result);

        return $authResponse;
    }

    /**
     * The ID of the currently authenticated user.
     *
     * @var string
     */
    private $userId;

    /**
     * The user identity
     *
     * @var string
     */
    private $username;

    /**
     * The authentication token
     *
     * @var string
     */
    private $token;

    /**
     * Minutes until expiry
     *
     * @var number
     */
    private $expiresIn;

    /**
     * Date and time of expiry
     *
     * @var string
     */
    private $expiresAt;

    /**
     * The last time this user logged in
     *
     * @var number Timestamp
     */
    private $lastLogin;

    /**
     * Boolean value representing whether inactivity flags were cleared during the authentication
     *
     * @var boolean
     */
    private $inactivityFlagsCleared;

    /**
     * The error description, if any
     *
     * @var string
     */
    private $errorDescription;

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     *
     * @return void
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string variable $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return void
     */
    public function setToken($token): void
    {
        $this->token = $token;
    }

    /**
     * @return number variable $expiresIn
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @param number $expiresIn
     *
     * @return void
     */
    public function setExpiresIn($expiresIn): void
    {
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return string variable $expiresAt
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param string $expiresAt
     *
     * @return void
     */
    public function setExpiresAt($expiresAt): void
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return number variable $lastLogin
     */
    public function getLastLogin(): number
    {
        return $this->lastLogin;
    }

    /**
     * @param string $lastLogin
     *
     * @return void
     */
    public function setLastLogin($lastLogin): void
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return string $username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return void
     */
    public function setUsername($username): void
    {
        $this->username = $username;
    }

    /**
     * @return boolean $inactivityFlagsCleared
     */
    public function getInactivityFlagsCleared()
    {
        return $this->inactivityFlagsCleared;
    }

    /**
     * @param boolean $inactivityFlagsCleared
     *
     * @return void
     */
    public function setInactivityFlagsCleared($inactivityFlagsCleared): void
    {
        $this->inactivityFlagsCleared = $inactivityFlagsCleared;
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

    /**
     * Populate the member variables from an array
     *
     * @param array $array
     *
     * @return void
     */
    public function exchangeArray(array $array): void
    {
        $this->userId = isset($array['userId']) ? $array['userId'] : null;
        $this->token = isset($array['token']) ? $array['token'] : null;
        $this->lastLogin = isset($array['last_login']) ? $array['last_login'] : null;
        $this->username = isset($array['username']) ? $array['username'] : null;
        $this->expiresIn = isset($array['expiresIn']) ? $array['expiresIn'] : null;
        $this->expiresAt = isset($array['expiresAt']) ? $array['expiresAt'] : null;
        $this->inactivityFlagsCleared = isset($array['inactivityFlagsCleared']) ? $array['inactivityFlagsCleared'] : null;
    }
}
