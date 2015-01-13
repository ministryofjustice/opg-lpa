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
     * How many times in a row that this email has failed to authenticate
     * 
     * @var number
     */
    private $failureCount;
    
    /**
     * The error returned, it any
     * 
     * @var string
     */
    private $errorDescription;

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
     * The last time this user logged in
     * 
     * @var number Timestamp
     */
    private $lastLogin;
    
    /**
     * The token type, e.g. "bearer"
     * 
     * @var string
     */
    private $tokenType;

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
     * @return member variable $errorDescription
     */
    public function getErrorDescription()
    {
        return $this->errorDescription;
    }

    /**
     * @param string $errorDescription
     * @return AuthResponse Returns itself.
     */
    public function setErrorDescription($errorDescription)
    {
        $this->errorDescription = $errorDescription;
        return $this;
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
     * @return member variable $failureCount
     */
    public function getFailureCount()
    {
        return $this->failureCount;
    }

    /**
     * @param number $failureCount
     */
    public function setFailureCount($failureCount)
    {
        $this->failureCount = $failureCount;
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
     * @return member variable $lastLogin
     */
    public function getLastLogin()
    {
        return $this->lastLogin;
    }

    /**
     * @param number $lastLogin
     */
    public function setLastLogin($lastLogin)
    {
        $this->lastLogin = $lastLogin;
    }

    /**
     * @return member variable $tokenType
     */
    public function getTokenType()
    {
        return $this->tokenType;
    }

    /**
     * @param string $tokenType
     */
    public function setTokenType($tokenType)
    {
        $this->tokenType = $tokenType;
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
            'user_id' => $this->userId,
            'error_description' => $this->errorDescription,
            'access_token' => $this->token,
            'last_login' => $this->lastLogin,
            'failure_count' => $this->failureCount,
            'expires_in' => $this->expiresIn,
            'token_type' => $this->tokenType,
        ];
    }
    
    /**
     * Populate the member variables from an array
     * 
     * @param array $array
     */
    public function exchangeArray(array $array)
    {
        $this->errorDescription = isset($array['error_description']) ? $array['error_description'] : null;
        $this->token = isset($array['access_token']) ? $array['access_token'] : null;
        $this->lastLogin = isset($array['last_login']) ? $array['last_login'] : null;
        $this->failureCount = isset($array['failure_count']) ? $array['failure_count'] : null;
        $this->expiresIn = isset($array['expires_in']) ? $array['expires_in'] : null;
        $this->tokenType = isset($array['token_type']) ? $array['token_type'] : null;
    }

}
