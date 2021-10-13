<?php

namespace App\Service\Feedback;

use App\Service\ApiClient\ApiException;
use App\Service\ApiClient\Client as ApiClient;
use DateTime;

class FeedbackService
{
    /**
     * @var ApiClient
     */
    private $client;

    /**
     * AuthenticationService constructor
     *
     * @param ApiClient $client
     */
    public function __construct(ApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param DateTime $startDate
     * @param DateTime $endDate
     * @return mixed|bool|null
     */
    public function search(DateTime $startDate, DateTime $endDate)
    {
        try {
            return $this->client->httpGet('/user-feedback', [
                'from' => $startDate->format('Y-m-d'),
                'to' => $endDate->format('Y-m-d'),
            ]);
        } catch (ApiException $ignore) {
        }

        return false;
    }
}
