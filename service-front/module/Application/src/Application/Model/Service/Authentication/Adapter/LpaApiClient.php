<?php
namespace Application\Model\Service\Authentication\Adapter;

use Opg\Lpa\Api\Client\Client as ApiClient;

use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Adapter\Exception\RuntimeException;

/**
 * Performs email address & password authentication with the LPA API Client.
 *
 * Class LpaApiClient
 * @package Application\Model\Service\Authentication\Adapter
 */
class LpaApiClient implements AdapterInterface {

    private $client;

    private $email;
    private $password;

    public function __construct( ApiClient $client ){
        $this->client = $client;
    }

    public function setCredentials( $email, $password ){
        $this->email = strtolower($email);
        $this->password = $password;
    }

    public function authenticate(){

        if( !isset($this->email) ){ throw new RuntimeException( 'Email address not set' ); }
        if( !isset($this->password) ){ throw new RuntimeException( 'Password not set' ); }

        //---

        //$response = $this->client->authenticate( $this->email, $this->password );

        //---

        // Don't leave this lying around
        unset( $this->password );

        # TODO - getArrayCopy should get merged into the array below. Ideally
        // in the stored identity we want their name, user_id, lastLogin and token.
        //$response->getArrayCopy();

        return new Result( Result::SUCCESS, [
            'id' => '699aaf1a14ff64239de1e0d03c2d66d4',
            'token' => 'ac5e086ee81b0aa6ef7469cedf88ea38'
        ]);

    }

} // class
