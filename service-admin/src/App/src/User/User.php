<?php

namespace App\User;

/**
 * Class User
 * @package App\User
 */
class User
{
    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $userId;

    /**
     * @var string
     */
    private $token;

    /**
     * User constructor.
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->username = $data['username'] ?: null;
        $this->userId = $data['userId'] ?: null;
        $this->token = $data['token'] ?: null;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }
}
