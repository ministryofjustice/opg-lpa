<?php

namespace Application\Model\Service\System;

use Application\Model\Service\AbstractService;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Aws\DynamoDb\DynamoDbClient;
use DateTime;
use Exception;
use Laminas\Session\SaveHandler\SaveHandlerInterface;

/**
 * Goes through all required services and checks they're operating.
 *
 * Class Status
 * @package Application\Model\Service\System
 */
class Status extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /** @var DynamoDbClient */
    private $dynamoDbClient;

    /** @var OrdnanceSurveyClient */
    private $ordnanceSurveyClient;

    /** @var OsRedisClient */
    private $osRedisClient;

    /** @var SaveHandlerInterface */
    private $sessionSaveHandler;

    /**
     * Services:
     * - DynamoDb (system message table)
     * - Session save handler
     * - API
     * - Ordnance Survey
     * - Ordnance Survey save handler
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

            // Check ordnanceSurvey
            $result['ordnanceSurvey'] = $this->ordnanceSurvey();

            $ok = true;
            foreach ($result as $service) {
                $ok = $ok && $service['ok'];
            }

            $result['ok'] = $ok;
            $result['iterations'] = $i;

            if (!$result['ok']) {
                return $result;
            }
        }

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

        $this->osRedisClient->open('', '');

        $lastOsCall = $this->osRedisClient->read('os_last_call');

        $currentTime = new DateTime('now');
        $currentUnixTime = $currentTime->getTimestamp();

        // Rate limit calls to os
        // If no record of calling os then call os directly
        if ($lastOsCall === '') {
            return $this->callOrdnanceSurvey($currentUnixTime);
        // Decide whether to call os based on max_call_per_min rate limit
        } else {
            $timeDiff = $currentUnixTime - intval($lastOsCall);
            $rateLimit = 60 / $config['max_call_per_min'];

            // Not rate limited
            if ($timeDiff > $rateLimit) {
                return $this->callOrdnanceSurvey($currentUnixTime);
            // Rate limited - os is not called and cached response returned
            } else {
                $osStatus = $this->osRedisClient->read('os_last_status');
                $osDetails = $this->osRedisClient->read('os_last_details');

                return [
                    'ok' => boolval($osStatus),
                    'cached' => true,
                    'details' => json_decode($osDetails, true)
                ];
            }
        }
    }

    private function callOrdnanceSurvey(int $currentUnixTime)
    {
        $os = $this->ordnanceSurveyClient->lookupPostcode('SW1A 1AA');

        // Update redis with timestamp of the call to os
        $this->osRedisClient->write('os_last_call', $currentUnixTime);

        // Cache response in redis
        if ($this->ordnanceSurveyClient->verify($os) == true) {
            $alive = true;
            $details = $os[0];
        } else {
            $alive = false;
            $details = '';
        }

        $this->osRedisClient->write('os_last_status', $alive);
        $this->osRedisClient->write('os_last_details', json_encode($details));

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
     * @param OrdnanceSurveyClient $ordnanceSurveyClient
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
     * @param OsRedisHandler $osRedisHandler
     */
    public function setOsRedisHandler(SaveHandlerInterface $osRedisHandler)
    {
        $this->osRedisClient = $osRedisHandler;
    }
}
