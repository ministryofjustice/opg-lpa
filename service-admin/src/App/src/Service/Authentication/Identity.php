<?php

namespace App\Service\Authentication;

class Identity
{
    /**
     * @var ?string
     */
    private $username;

    /**
     * @var ?string
     */
    private $userId;

    /**
     * @var ?string
     */
    private $token;

    /**
     * Identity constructor.
     * @param array<string, string> $data
     */
    public function __construct(array $data)
    {
        $this->username = $data['username'] ?: null;
        $this->userId = $data['userId'] ?: null;
        $this->token = $data['token'] ?: null;
    }

    /**
     * @return ?string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return void
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return ?string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return void
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return ?string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     * @return void
     */
    public function setToken(#[\SensitiveParameter] string $token)
    {
        $this->token = $token;
    }
}
