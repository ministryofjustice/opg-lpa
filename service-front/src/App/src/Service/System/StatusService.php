<?php

declare(strict_types=1);

namespace App\Service\System;

use App\Service\AddressLookup\OrdnanceSurvey;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Service\Redis\RedisClient;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use Exception;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Client\ClientInterface;

class StatusService
{
    // If any of these have a status of 'fail', the service is considered down
    private const array SERVICES_REQUIRED = ['api', 'sessionSaveHandler', 'mail'];

    // If any of these have a status of 'fail' or 'warn', the service is degraded
    private const array SERVICES_OPTIONAL = ['dynamo', 'ordnanceSurvey'];

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly ClientInterface $httpClient,
        private readonly ?DynamoDbClient $dynamoDbClient = null,
        private readonly ?\SessionHandlerInterface $sessionSaveHandler = null,
        private readonly ?MailTransportInterface $mailTransport = null,
        private readonly ?OrdnanceSurvey $ordnanceSurveyClient = null,
        private readonly ?RedisClient $redisClient = null,
        private readonly array $config = [],
    ) {
    }

    public function checkApi(): array
    {
        $result = [
            'ok' => false,
            'details' => [],
        ];

        try {
            $api = $this->apiClient->httpGet('/ping');

            $result['ok'] = $api['ok'];
            unset($api['ok']);

            $result['details']['response_code'] = 200;
            $result['details'] += $api;
        } catch (Exception) {
            $result['ok'] = false;
            $result['details']['response_code'] = 500;
        }

        return $result;
    }

    public function checkSession(): array
    {
        if ($this->sessionSaveHandler === null) {
            return ['ok' => true];
        }

        $ok = $this->sessionSaveHandler->open('', '');

        return [
            'ok' => $ok,
        ];
    }

    public function checkMail(): array
    {
        if ($this->mailTransport === null) {
            return ['ok' => true];
        }

        return $this->mailTransport->healthcheck();
    }

    public function checkPay(): array
    {
        $payUrl = $this->config['alphagov']['pay']['url'] . '/healthcheck';

        $response = $this->httpClient->sendRequest(new Request('GET', $payUrl));
        $ok = $response->getStatusCode() === 200;

        return [
            'ok' => $ok,
            'details' => [
                'url' => $payUrl,
                'response_code' => $response->getStatusCode(),
            ],
        ];
    }

    public function checkApiDependencies(): array
    {
        $result = [
            'ok' => false,
            'details' => [],
        ];

        try {
            $api = $this->apiClient->httpGet('/health-check/dependencies');

            $result['ok'] = $api['ok'];
            unset($api['ok']);

            $result['details'] += $api;
        } catch (Exception) {
            $result['ok'] = false;
        }

        return $result;
    }

    public function checkDynamo(): array
    {
        if ($this->dynamoDbClient === null) {
            return ['ok' => true];
        }

        try {
            $tableName = $this->config['admin']['dynamodb']['settings']['table_name'] ?? '';
            $details = $this->dynamoDbClient->describeTable([
                'TableName' => $tableName,
            ])->toArray();

            if (
                $details['@metadata']['statusCode'] === 200 &&
                in_array($details['Table']['TableStatus'], ['ACTIVE', 'UPDATING'])
            ) {
                return ['ok' => true];
            }
        } catch (Exception) {
            // fall through
        }

        return ['ok' => false];
    }

    public function checkOrdnanceSurvey(): array
    {
        if ($this->ordnanceSurveyClient === null || $this->redisClient === null) {
            return ['ok' => true];
        }

        $this->redisClient->open();
        $osLastCall = $this->redisClient->read('os_last_call');

        $currentUnixTime = (new DateTime('now'))->getTimestamp();

        // Check if Redis cached a timestamp for last call to OS, use cache if within rate limit
        if (is_numeric($osLastCall) && intval($osLastCall) > 0) {
            $timeDiff = $currentUnixTime - intval($osLastCall);
            $rateLimit = 60 / ($this->config['redis']['ordnance_survey']['max_call_per_min'] ?? 6);

            if ($timeDiff <= $rateLimit) {
                $osLastStatus = boolval($this->redisClient->read('os_last_status'));
                $osLastDetails = $this->redisClient->read('os_last_details');

                $this->redisClient->close();

                return [
                    'ok' => $osLastStatus,
                    'cached' => true,
                    'details' => json_decode($osLastDetails, true),
                ];
            }
        }

        return $this->callOrdnanceSurvey($currentUnixTime);
    }

    private function callOrdnanceSurvey(int $currentUnixTime): array
    {
        try {
            $os = $this->ordnanceSurveyClient->lookupPostcode('SW1A 1AA');
        } catch (Exception) {
            $this->redisClient->write('os_last_call', strval($currentUnixTime));
            $this->redisClient->write('os_last_status', '');
            $this->redisClient->write('os_last_details', json_encode(''));
            $this->redisClient->close();

            return [
                'ok' => false,
                'cached' => false,
                'details' => '',
            ];
        }

        $this->redisClient->write('os_last_call', strval($currentUnixTime));

        $alive = false;
        $details = '';

        if ($this->ordnanceSurveyClient->verify($os)) {
            $alive = true;
            $details = $os[0];
        }

        $this->redisClient->write('os_last_status', strval($alive));
        $this->redisClient->write('os_last_details', json_encode($details));

        $this->redisClient->close();

        return [
            'ok' => $alive,
            'cached' => false,
            'details' => $details,
        ];
    }
}
