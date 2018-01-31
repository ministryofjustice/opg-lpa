<?php

namespace Application\Model\Service\Lpa;

use Application\Model\Service\AbstractService;
use Application\Model\Service\ApiClient\Client;
use Opg\Lpa\DataModel\Lpa\Lpa;
use InvalidArgumentException;

class Application extends AbstractService
{
    /**
     * Client service from the api-client module
     *
     * @var Client
     */
    private $client;

    /**
     * Application constructor
     *
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Update the payment on the LPA
     *
     * @param Lpa $lpa
     * @return mixed
     */
    public function updatePayment(Lpa $lpa)
    {
        //  Call the update application function on the client via the __call function
        return $this->updateApplication($lpa->id, ['payment' => $lpa->payment->toArray()]);
    }

    /**
     * By default we just pass requests onto the API Client.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->client, $name ])) {
            return call_user_func_array([$this->client, $name], $arguments);
        }

        throw new InvalidArgumentException("Unknown method $name called");
    }
}
