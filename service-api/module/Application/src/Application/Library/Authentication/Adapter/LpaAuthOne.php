<?php
namespace Application\Library\Authentication\Adapter;

use Exception;

use \GuzzleHttp\Client as GuzzleClient;

use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Adapter\Exception\ExceptionInterface as AdapterExceptionInterface;

use Application\Library\Authentication\Identity;

/**
 * Authentication adapter for Version 1 of the LPA auth service.
 *
 * Class LpaAuthOne
 * @package Application\Library\Authentication\Adapter
 */
class LpaAuthOne implements AdapterInterface {

    private $token;

    /**
     * Sets username and password for authentication
     */
    public function __construct( $token ){
        $this->token = $token;
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface
     *               If authentication cannot be performed
     */
    public function authenticate(){

        // The default result...
        $result = new Result( Result::FAILURE, null );

        try {

            $client = new GuzzleClient();

            $response = $client->get('http://auth.local/tokeninfo', [ 'query' => [
                'access_token' => $this->token
            ] ] );

            if( $response->getStatusCode() == 200 ){

                $data = $response->json();

                if( isset( $data['user_id'] ) ){
                    $result = new Result( Result::SUCCESS, new Identity\User( $data['user_id'] ) );
                }

            } // if

        } catch (AdapterExceptionInterface $e){
            // The exception is specific to authentication, so throw it.
            throw $e;
        } catch (Exception $e){
            // Do nothing, allow Result::FAILURE to be returned.
        }

        // Don't leave the token lying around...
        unset($this->token);

        return $result;

    } // function

} // class
