<?php

namespace Application\Model\Service\System;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\ApiClientAwareInterface;
use Application\Model\Service\ApiClient\ApiClientTrait;
use Aws\DynamoDb\DynamoDbClient;
use Exception;

/**
 * Goes through all required services and checks they're operating.
 *
 * Class Status
 * @package Application\Model\Service\System
 */
class Status extends AbstractService implements ApiClientAwareInterface
{
    use ApiClientTrait;

    /**
     * @var DynamoDbClient
     */
    private $dynamoDbSessionClient;

    /**
     * @var DynamoDbClient
     */
    private $dynamoDbCronClient;

    /**
     * Services:
     *  - API 2
     *  - RedisFront
     *  - Postcode Anywhere #TODO
     *  - SendGird #TODO
     *
     * @return (bool|int|mixed)[]
     *
     * @psalm-return array{dynamo: mixed, api: mixed, ok: bool, iterations: positive-int}
     */
    public function check(): array
    {
        $result = ['ok' => false];

        for ($i = 1; $i <= 6; $i++) {
            $result = array();

            //-----------------------------------
            // DynamoDB

            $result['dynamo'] = $this->dynamo();

            //-----------------------------------
            // Check API 2

            $result['api'] = $this->api();

            //-----------------------------------

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

    //------------------------------------------------------------------------

    /**
     * @return (bool|bool[])[]
     *
     * @psalm-return array{ok: bool, details: array{locks: bool}}
     */
    private function dynamo(): array
    {
        $result = array('ok' => false, 'details' => [
//            'sessions' => false,
            'locks' => false,
        ]);

//        //------------------
//        // Sessions
//
//        try {
//            $details = $this->dynamoDbSessionClient->describeTable([
//                'TableName' => $this->getConfig()['session']['dynamodb']['settings']['table_name']
//            ]);
//
//            if ($details['@metadata']['statusCode'] === 200 && in_array($details['Table']['TableStatus'], ['ACTIVE', 'UPDATING'])) {
//                // Table is okay
//                $result['details']['sessions'] = true;
//            }
//        } catch (Exception $e) {}

        //------------------
        // Locks

        try {
            $details = $this->dynamoDbCronClient->describeTable([
                'TableName' => $this->getConfig()['cron']['lock']['dynamodb']['settings']['table_name']
            ]);

            if ($details['@metadata']['statusCode'] === 200 && in_array($details['Table']['TableStatus'], ['ACTIVE', 'UPDATING'])) {
                // Table is okay
                $result['details']['locks'] = true;
            }
        } catch (Exception $e) {}

        //----

        // ok is true if and only if all values in details are true.
        $result['ok'] = array_reduce(
            $result['details'],
            function ($carry, $item) {
                return $carry && $item;
            },
            true // initial
        );

        return $result;
    }

    //------------------------------------------------------------------------

    /**
     * @return (array|false|mixed|null)[]
     *
     * @psalm-return array{ok: false|mixed|null, details: array}
     */
    private function api(): array
    {
        $result = [
            'ok'      => false,
            'details' => [
                '200' => false,
            ],
        ];

        try {
            $api = $this->apiClient->httpGet('/ping');

            $result['details']['200'] = true;

            $result['ok'] = $api['ok'];
            $result['details'] = $result['details'] + $api;
        } catch (Exception $e) {}   //  Don't throw exceptions; we just return ok==false

        return $result;
    }

    /**
     * @param DynamoDbClient $dynamoDbSessionClient
     *
     * @return void
     */
    public function setDynamoDbSessionClient(DynamoDbClient $dynamoDbSessionClient): void
    {
        $this->dynamoDbSessionClient = $dynamoDbSessionClient;
    }

    /**
     * @param DynamoDbClient $dynamoDbCronClient
     *
     * @return void
     */
    public function setDynamoDbCronClient(DynamoDbClient $dynamoDbCronClient): void
    {
        $this->dynamoDbCronClient = $dynamoDbCronClient;
    }
}
