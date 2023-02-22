<?php

namespace Application\Model\Service\System;

use Application\Model\Service\AbstractService;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Application\Model\Service\Redis\RedisClient;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use Exception;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use MakeShared\Logging\LoggerTrait;

/**
 * Goes through all required services and checks they're operating.
 *
 * Class Status
 * @package Application\Model\Service\System
 */
class Status extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;
    use LoggerTrait;

    /** @var DynamoDbClient */
    private $dynamoDbClient;

    /** @var OrdnanceSurvey */
    private $ordnanceSurveyClient;

    /** @var RedisClient */
    private $redisClient;

    /** @var SaveHandlerInterface */
    private $sessionSaveHandler;

    /**
     * Services:
     * - DynamoDb (system message table)
     * - Session save handler
     * - API
     * - Ordnance Survey
     */
    public function check()
    {
        $result = ['ok' => false];

        for ($i = 1; $i <= 6; $i++) {
            $result = array();

            // Check DynamoDB
            $result['dynamo'] = $this->dynamo();

            // Check API
            $result['api'] = $this->api();

            // Check session save handling
            $result['sessionSaveHandler'] = $this->session();

            $ok = true;
            foreach ($result as $service) {
                $ok = $ok && $service['ok'];
            }

            $result['ok'] = $ok;
            $result['iterations'] = $i;

            if (!$result['ok']) {
                break;
            }
        }

        // Check ordnanceSurvey - we rate limit this so we don't want it in the above retry loop
        $result['ordnanceSurvey'] = $this->ordnanceSurvey();

        return $result;
    }

    private function dynamo()
    {
        $result = array(
            'ok' => false,
        );

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
                $result['ok'] = true;
            }
        } catch (Exception $e) {
            $result['ok'] = false;
        }

        return $result;
    }

    private function api()
    {
        $result = [
            'ok' => false,
            'details' => [],
        ];

        try {
            $api = $this->apiClient->httpGet('/ping');

            $result['ok'] = $api['ok'];
            unset($api['ok']);

            $result['details']['status'] = 200;
            $result['details'] = $result['details'] + $api;
        } catch (Exception $e) {
            $result['ok'] = false;
        }

        return $result;
    }

    private function session()
    {
        return [
            'ok' => $this->sessionSaveHandler->open('', ''),
        ];
    }

    private function ordnanceSurvey()
    {
        $config = $this->getConfig()['redis']['ordnance_survey'];

        $redisOpen = $this->redisClient->open();
        $lastOsCall = $this->redisClient->read('os_last_call');

        $currentTime = new DateTime('now');
        $currentUnixTime = $currentTime->getTimestamp();

        // Check if redis cached a timestamp for last call to OS, and call OS if no timestamp or not rate limited
        if (is_numeric($lastOsCall) && intval($lastOsCall) > 0) {
            $timeDiff = $currentUnixTime - intval($lastOsCall);
            $rateLimit = 60 / $config['max_call_per_min'];

            // Not rate limited - call OS
            if ($timeDiff > $rateLimit) {
                return $this->callOrdnanceSurvey($currentUnixTime);
            // Rate limited - return cached response
            } else {
                $osStatus = $this->redisClient->read('os_last_status');
                $osDetails = $this->redisClient->read('os_last_details');

                return [
                    'ok' => boolval($osStatus),
                    'cached' => true,
                    'details' => json_decode($osDetails, true)
                ];
            }
        } else {
            return $this->callOrdnanceSurvey($currentUnixTime);
        }
    }

    private function callOrdnanceSurvey(int $currentUnixTime)
    {
        $os = $this->ordnanceSurveyClient->lookupPostcode('SW1A 1AA');

        // Update redis with timestamp of the call to os
        $this->redisClient->write('os_last_call', strval($currentUnixTime));

        // Cache response in redis
        if ($this->ordnanceSurveyClient->verify($os) == true) {
            $alive = true;
            $details = $os[0];
        } else {
            $alive = false;
            $details = '';
        }

        $this->redisClient->write('os_last_status', strval($alive));
        $this->redisClient->write('os_last_details', json_encode($details));

        return ['ok' => $alive, 'cached' => false, 'details' => $details];
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
}
