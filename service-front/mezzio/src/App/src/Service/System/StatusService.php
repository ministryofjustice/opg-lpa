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
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Constants;

class StatusService
{
    // If any of these have a status of 'fail', the service is considered down
    private const array SERVICES_REQUIRED = ['api', 'sessionSaveHandler', 'mail'];

    // If any of these have a status of 'fail' or 'warn', the service is degraded
    private const array SERVICES_OPTIONAL = ['dynamo', 'ordnanceSurvey'];

    public function __construct(
        private readonly ApiClient $apiClient,
        private readonly ?DynamoDbClient $dynamoDbClient = null,
        private readonly ?SaveHandlerInterface $sessionSaveHandler = null,
        private readonly ?MailTransportInterface $mailTransport = null,
        private readonly ?OrdnanceSurvey $ordnanceSurveyClient = null,
        private readonly ?RedisClient $redisClient = null,
        private readonly array $config = [],
    ) {
    }

    /**
     * @return array{ok: bool, status: string}
     */
    public function check(): array
    {
        $result = [];

        // Check API (required)
        $result['api'] = $this->checkApi();

        // Check session save handler (required)
        $result['sessionSaveHandler'] = $this->checkSession();

        // Check mail transport (required)
        $result['mail'] = $this->checkMail();

        // Service reports as OK if all required services are OK
        $result['ok'] = true;
        foreach (self::SERVICES_REQUIRED as $serviceRequired) {
            if (!$result[$serviceRequired]['ok']) {
                $result['ok'] = false;
                break;
            }
        }

        // Check DynamoDB (optional)
        $result['dynamo'] = $this->checkDynamo();

        // Check Ordnance Survey (optional, rate limited via Redis)
        $result['ordnanceSurvey'] = $this->checkOrdnanceSurvey();

        // Determine overall status
        $status = Constants::STATUS_PASS;

        foreach (self::SERVICES_REQUIRED as $serviceRequired) {
            $requiredStatus = $result[$serviceRequired]['status'];

            if ($requiredStatus === Constants::STATUS_PASS) {
                continue;
            }

            $status = $requiredStatus;

            if ($status === Constants::STATUS_FAIL) {
                break;
            }
        }

        // If no required services fail, check optional services for degradation
        if ($status !== Constants::STATUS_FAIL) {
            foreach (self::SERVICES_OPTIONAL as $serviceOptional) {
                if ($result[$serviceOptional]['status'] !== Constants::STATUS_PASS) {
                    $status = Constants::STATUS_WARN;
                    break;
                }
            }
        }

        $result['status'] = $status;

        return $result;
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

    private function checkSession(): array
    {
        if ($this->sessionSaveHandler === null) {
            return ['ok' => true, 'status' => Constants::STATUS_PASS];
        }

        $ok = $this->sessionSaveHandler->open('', '');

        return [
            'ok' => $ok,
            'status' => ($ok ? Constants::STATUS_PASS : Constants::STATUS_FAIL),
        ];
    }

    private function checkMail(): array
    {
        if ($this->mailTransport === null) {
            return ['ok' => true, 'status' => Constants::STATUS_PASS];
        }

        return $this->mailTransport->healthcheck();
    }

    private function checkDynamo(): array
    {
        if ($this->dynamoDbClient === null) {
            return ['ok' => true, 'status' => Constants::STATUS_PASS];
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
                return ['ok' => true, 'status' => Constants::STATUS_PASS];
            }
        } catch (Exception) {
            // fall through
        }

        return ['ok' => false, 'status' => Constants::STATUS_FAIL];
    }

    private function checkOrdnanceSurvey(): array
    {
        if ($this->ordnanceSurveyClient === null || $this->redisClient === null) {
            return ['ok' => true, 'status' => Constants::STATUS_PASS];
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
                    'status' => ($osLastStatus ? Constants::STATUS_PASS : Constants::STATUS_FAIL),
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
                'status' => Constants::STATUS_FAIL,
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
            'status' => ($alive ? Constants::STATUS_PASS : Constants::STATUS_FAIL),
            'cached' => false,
            'details' => $details,
        ];
    }
}
