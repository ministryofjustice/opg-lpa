<?php
namespace Application\Model\Service\Lpa;

use InvalidArgumentException;

use Opg\Lpa\Api\Client\Client;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\DataModel\Lpa\Payment\Payment;

class Application {

    private $client;

    public function __construct( Client $client ){
        $this->client = $client;
    }

    public function updatePayment( Lpa $lpa ){

        return $this->updateApplication($lpa->id, [ 'payment' => $lpa->payment->toArray() ]);

    }

    /**
     * By default we just pass requests onto the API Client.
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments){

        if( is_callable( [ $this->client, $name ] ) ){
            return call_user_func_array( [ $this->client, $name ], $arguments );
        }

        throw new InvalidArgumentException("Unknown method $name called");

    } // function

} // class
