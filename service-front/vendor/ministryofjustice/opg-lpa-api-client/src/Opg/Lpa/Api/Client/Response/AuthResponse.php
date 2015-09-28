<?php
namespace Opg\Lpa\Api\Client\Response;

use Opg\Lpa\Api\Client\Traits\JsonSerializer;

/**
 * 
 * @author Chris Moreton
 * 
 * Wraps the information received from the auth server
 *
 */
class AuthResponse
{
    use JsonSerializer;

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
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
    
    /**
     * @return member variable $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return member variable $expiresIn
     */
    public function getExpiresIn()
    {
        return $this->expiresIn;
    }

    /**
     * @param number $expiresIn
     */
    public function setExpiresIn($expiresIn)
    {
        $this->expiresIn = $expiresIn;
    }

    /**
     * @return member variable $expiresAt
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }
    
    /**
     * @param string $expiresAt
     */
    public function setExpiresAt($expiresIn)
    {
        $this->expiresAt = $expiresIn;
    }
    
    /**
     * @return member variable $lastLogin
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param string $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }
    
    /**
     * @return the $username
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }
    
    /**
     * @return the $errorDescription
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }
    
    /**
     * @param string $errorDescription
     */
    public function setErrorDescription($errorDescription)
    {
        $this->errorDescription = $errorDescription;
        return $this;
    }
    
    public function isAuthenticated()
    {
        return !empty($this->userId) && !empty($this->token);
    }

    /**
     * Return an array representation of the object
     * 
     * @return array
     */
    public function getArrayCopy()
    {
        return [
            'userId' => $this->userId,
            'token' => $this->token,
            'last_login' => $this->lastLogin,
            'expires_in' => $this->expiresIn,
            'expires_at' => $this->expiresAt,
            'username' => $this->username,
        ];
    }
    
    /**
     * Populate the member variables from an array
     * 
     * @param array $array
     */
    public function exchangeArray(array $array)
    {
        $this->userId = isset($array['userId']) ? $array['userId'] : null;
        $this->token = isset($array['token']) ? $array['token'] : null;
        $this->lastLogin = isset($array['last_login']) ? $array['last_login'] : null;
        $this->username = isset($array['username']) ? $array['username'] : null;
        $this->expiresIn = isset($array['expiresIn']) ? $array['expiresIn'] : null;
        $this->expiresAt = isset($array['expiresAt']) ? $array['expiresAt'] : null;
    }

}
