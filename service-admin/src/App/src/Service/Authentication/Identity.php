<?php

namespace App\Service\Authentication;

class Identity
{
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
        $this->userId = $data['userId'] ?: null;
        $this->token = $data['token'] ?: null;
    }

    /**
     * @return ?string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @return ?string
     */
    public function getToken()
    {
        return $this->token;
    }
}
