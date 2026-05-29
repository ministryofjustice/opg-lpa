<?php

declare(strict_types=1);

namespace App\Service\System;

use Application\Model\Service\ApiClient\Client as ApiClient;
use Exception;
use MakeShared\Constants;

class StatusService
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {
    }

    /**
     * @return array{ok: bool, status: string, api: array}
     */
    public function check(): array
    {
        $api = $this->checkApi();

        $status = $api['ok'] ? Constants::STATUS_PASS : Constants::STATUS_FAIL;

        return [
            'ok' => $api['ok'],
            'status' => $status,
            'api' => $api,
        ];
    }

    private function checkApi(): array
    {
        $result = [
            'ok' => false,
            'status' => Constants::STATUS_FAIL,
            'details' => [],
        ];

        try {
            $api = $this->apiClient->httpGet('/ping');

            $result['ok'] = $api['ok'];
            $result['status'] = $api['status'];
            unset($api['ok'], $api['status']);

            $result['details']['response_code'] = 200;
            $result['details'] += $api;
        } catch (Exception) {
            $result['ok'] = false;
            $result['status'] = Constants::STATUS_FAIL;
            $result['details']['response_code'] = 500;
        }

        return $result;
    }
}
