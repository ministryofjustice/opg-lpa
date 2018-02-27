<?php

namespace Application\Model\Service\AuthClient\Response;

use Application\Model\Service\AuthClient\Exception;
use Psr\Http\Message\ResponseInterface;

/**
 *
 * @author Chris Moreton
 *
 * Wraps the information received from the auth server
 *
 */
class AuthResponse
{
    private $response;

    //---------------------

    public static function buildFromResponse(ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        // The expected response should always be JSON, thus now an array.
        if (!is_array($body)) {
            throw new Exception\ResponseException('Malformed JSON response from server', $response->getStatusCode(), $response);
        }

        $authResponse = new static();

        $authResponse->exchangeArray($body);

        $authResponse->setResponse($response);

        return $authResponse;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
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
     * @return string variable $token
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
     * @return number variable $expiresIn
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
     * @return string variable $expiresAt
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * @param string $expiresAt
     */
    public function setExpiresAt($expiresAt)
    {
        $this->expiresAt = $expiresAt;
    }

    /**
     * @return string variable $lastLogin
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
     * @return string $username
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

    public function isAuthenticated()
    {
        return !empty($this->userId) && !empty($this->token) && empty($this->errorDescription);
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

    /**
     * Populate the member variables from a JSON structure
     * Convert underscore_field_names to be camelCase
     *
     * @param string $json
     */
    public function exchangeJson($json)
    {
        $this->exchangeArray(json_decode($json, true));
    }

    /**
     * Return the object as JSON
     *
     * @return string
     */
    public function getJsonCopy()
    {
        return json_encode($this->getArrayCopy());
    }
}
