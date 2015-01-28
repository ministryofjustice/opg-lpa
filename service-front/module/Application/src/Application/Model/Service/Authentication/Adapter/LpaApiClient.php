<?php
namespace Application\Model\Service\Authentication\Adapter;

use DateTime;

use Opg\Lpa\Api\Client\Client as ApiClient;

use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Adapter\Exception\RuntimeException;

use Application\Model\Library\Authentication\Identity\User;

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

    /**
     * @param ApiClient $client
     */
    public function __construct( ApiClient $client ){
        $this->client = $client;
    }

    /**
     * Set the credentials to attempt authentication with.
     *
     * @param $email
     * @param $password
     */
    public function setCredentials( $email, $password ){
        $this->email = strtolower($email);
        $this->password = $password;
    }

    /**
     * Attempt to authenticate the user with the set credentials, via the LPA API Client.
     *
     * @return Result
     */
    public function authenticate(){

        if( !isset($this->email) ){ throw new RuntimeException( 'Email address not set' ); }
        if( !isset($this->password) ){ throw new RuntimeException( 'Password not set' ); }

        //---

        $response = $this->client->authenticate( $this->email, $this->password );

        //---

        // Don't leave this lying around
        unset( $this->password );

        //---

        if( !$response->isAuthenticated() ){
            return new Result( Result::FAILURE, null );
        }

        $identity = new User(
            $response->getUserId(),
            $response->getToken(),
            $response->getExpiresIn(),
            (new DateTime())->setTimestamp( $response->getLastLogin() )
        );

        return new Result( Result::SUCCESS, $identity );

    }

} // class
