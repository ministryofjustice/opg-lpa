<?php

namespace Application\Model\Service\System;

use Application\Model\Service\AbstractService;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\Mail\Transport\MailTransportInterface;
use Application\Model\Service\Redis\RedisClient;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use Exception;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Constants;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

/**
 * Goes through all required services and checks they're operating.
 *
 * Class Status
 * @package Application\Model\Service\System
 */
class Status extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    // if any of these have a status of 'fail', the service
    // is considered down; if any is 'warn', the service
    // is degraded
    const SERVICES_REQUIRED = ['api', 'sessionSaveHandler', 'mail'];

    // if any of these have a status of 'fail' or 'warn', the service
    // is considered up, but running at a degraded level
    const SERVICES_OPTIONAL = ['dynamo', 'ordnanceSurvey'];

    /** @var DynamoDbClient */
    private $dynamoDbClient;

    /** @var OrdnanceSurvey */
    private $ordnanceSurveyClient;

    /** @var RedisClient */
    private $redisClient;

    /** @var SaveHandlerInterface */
    private $sessionSaveHandler;

    /** @var MailTransportInterface */
    private $mailTransport;

    /**
     * Services:
     * - DynamoDb (system message table)
     * - Session save handler
     * - API
     * - Ordnance Survey
     */
    public function check()
    {
        $result = [];

        // Check API (required)
        $result['api'] = $this->api();

        // Check session save handling (required)
        $result['sessionSaveHandler'] = $this->session();

        $result['mail'] = $this->mailTransport->healthcheck();

        // Service reports as OK if all required services are OK;
        // note that OK is different from status - status can
        // be "warn" or "pass" and OK will be true; if status is
        // "fail", then OK will be false
        $result['ok'] = true;
        foreach (self::SERVICES_REQUIRED as $serviceRequired) {
            if (!$result[$serviceRequired]['ok']) {
                $result['ok'] = false;
                break;
            }
        }

        // Check DynamoDB
        $result['dynamo'] = $this->dynamo();

        // Check Ordnance Survey - rate limited: response might be cached
        $result['ordnanceSurvey'] = $this->ordnanceSurvey();

        // Determine overall status of service by looking at the statuses
        // of each dependency
        $status = Constants::STATUS_PASS;

        // If any required service is "fail", overall status is "fail";
        // if one or more required services are "warn", and none "fail",
        // overall status is "warn";
        // if all required services are "pass", overall status is "pass"
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

        // If no required services are "fail", but one or more optional services are "pass" or "warn",
        // overall status is "warn"; note that an optional service can be "fail", but
        // we still report an overall "warn" status (rather than "fail")
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

    private function dynamo()
    {
        $result = ['ok' => false, 'status' => Constants::STATUS_FAIL];

        // DynamoDb (system message table)
        try {
            $details = $this->dynamoDbClient->describeTable([
                'TableName' => $this->getConfig()['admin']['dynamodb']['settings']['table_name']
            ])->toArray();

            if (
                $details['@metadata']['statusCode'] === 200 &&
                in_array($details['Table']['TableStatus'], ['ACTIVE', 'UPDATING'])
            ) {
                // Table is okay
                $result = [
                    'ok' => true,
                    'status' => Constants::STATUS_PASS,
                ];
            }
        } catch (Exception $e) {
            $result = [
                'ok' => false,
                'status' => Constants::STATUS_FAIL,
            ];
        }

        return $result;
    }

    private function api()
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
            unset($api['ok']);
            unset($api['status']);

            $result['details']['response_code'] = 200;
            $result['details'] += $api;
        } catch (Exception $e) {
            $result['ok'] = false;
            $result['status'] = Constants::STATUS_FAIL;
            $result['details']['response_code'] = 500;
        }

        return $result;
    }

    private function session()
    {
        $ok = $this->sessionSaveHandler->open('', '');

        return [
            'ok' => $ok,
            'status' => ($ok ? Constants::STATUS_PASS : Constants::STATUS_FAIL),
        ];
    }

    private function ordnanceSurvey()
    {
        $this->redisClient->open();
        $osLastCall = $this->redisClient->read('os_last_call');

        $currentUnixTime = (new DateTime('now'))->getTimestamp();

        // Check if redis cached a timestamp for last call to OS, and call OS if no timestamp or not rate limited
        if (is_numeric($osLastCall) && intval($osLastCall) > 0) {
            $timeDiff = $currentUnixTime - intval($osLastCall);
            $rateLimit = 60 / ($this->getConfig()['redis']['ordnance_survey']['max_call_per_min']);

            // Use Redis cache
            if ($timeDiff <= $rateLimit) {
                $osLastStatus = boolval($this->redisClient->read('os_last_status'));
                $osLastDetails = $this->redisClient->read('os_last_details');

                $this->redisClient->close();

                return [
                    'ok' => $osLastStatus,
                    'status' => ($osLastStatus ? Constants::STATUS_PASS : Constants::STATUS_FAIL),
                    'cached' => true,
                    'details' => json_decode($osLastDetails, true)
                ];
            }
        }

        return $this->callOrdnanceSurvey($currentUnixTime);
    }

    private function callOrdnanceSurvey(int $currentUnixTime)
    {
        $os = $this->ordnanceSurveyClient->lookupPostcode('SW1A 1AA');

        // Update redis with timestamp of the call to os
        $this->redisClient->write('os_last_call', strval($currentUnixTime));

        // Cache response in redis
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

    /**
     * @param DynamoDbClient $dynamoDbClient
     */
    public function setDynamoDbClient(DynamoDbClient $dynamoDbClient)
    {
        $this->dynamoDbClient = $dynamoDbClient;
    }

    /**
     * @param OrdnanceSurvey $ordnanceSurveyClient
     */
    public function setOrdnanceSurveyClient(OrdnanceSurvey $ordnanceSurveyClient)
    {
        $this->ordnanceSurveyClient = $ordnanceSurveyClient;
    }

    /**
     * @param SaveHandlerInterface $saveHandler
     */
    public function setSessionSaveHandler(SaveHandlerInterface $saveHandler)
    {
        $this->sessionSaveHandler = $saveHandler;
    }

    /**
     * @param RedisClient $redisClient
     */
    public function setRedisClient(RedisClient $redisClient)
    {
        $this->redisClient = $redisClient;
    }

    /**
     * @param MailTransportInterface $mailTransport
     */
    public function setMailTransport(MailTransportInterface $mailTransport)
    {
        $this->mailTransport = $mailTransport;
    }
}
