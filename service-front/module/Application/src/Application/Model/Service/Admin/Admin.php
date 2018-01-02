<?php

namespace Application\Model\Service\Admin;

use Application\Model\Service\ApiClient\Client as ApiClient;

class Admin
{
    private $client;

    /**
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param string $email
     * @return array|bool
     */
    public function searchUsers(string $email)
    {
        $result = $this->client->searchUsers($email);

        return $result;
    }
}