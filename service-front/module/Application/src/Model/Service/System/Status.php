<?php

namespace Application\Model\Service\System;

use Application\Model\Service\AbstractService;
use Application\Model\Service\AddressLookup\OrdnanceSurvey;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Aws\DynamoDb\DynamoDbClient;
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

    /** @var OsSaveClient */
    private $osSaveClient;

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
        $os = $this->ordnanceSurveyClient->lookupPostcode('SW1A 1AA');

        if ($this->ordnanceSurveyClient->verify($os) == true) {
            return ['ok' => true, 'details' => $os];
        } else {
            return ['ok' => false];
        }
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
     * @param OsSaveHandler $osSaveHandler
     */
    public function setOsSaveHandler(SaveHandlerInterface $osSaveHandler)
    {
        $this->osSaveClient = $osSaveClient;
    }
}
