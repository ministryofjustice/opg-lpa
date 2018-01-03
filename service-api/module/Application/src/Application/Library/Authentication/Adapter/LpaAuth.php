<?php
namespace Application\Library\Authentication\Adapter;

use Exception;

use \GuzzleHttp\Client as GuzzleClient;

use Zend\Authentication\Result;
use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Adapter\Exception\ExceptionInterface as AdapterExceptionInterface;

use Application\Library\Authentication\Identity;

/**
 * Authentication adapter for Version 2 of the LPA auth service.
 *
 * Class LpaAuthOne
 * @package Application\Library\Authentication\Adapter
 */
class LpaAuth implements AdapterInterface {

    /**
     * The token to authenticate.
     *
     * @var string
     */
    private $token;

    /**
     * The endpoint (domain and path) to authenticate against.
     *
     * @var string
     */
    private $authEndpoint;

    /**
     * The administrator config for the API
     *
     * @var array
     */
    private $adminConfig;

    //-------------------------------

    /**
     * Sets username and password for authentication
     */
    public function __construct( $token, $authEndpoint, $adminConfig ){
        $this->token = $token;
        $this->authEndpoint = $authEndpoint;
        $this->adminConfig = $adminConfig;
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

            $response = $client->post( $this->authEndpoint , [
                'body' => [
                    'Token' => $this->token,
                ]
            ]);

            if( $response->getStatusCode() == 200 ){

                $data = $response->json();

                if( isset( $data['userId'] ) && isset( $data['username'] ) ){
                    $user = new Identity\User($data['userId'], $data['username']);

                    $adminAccounts = $this->adminConfig['accounts'];
                    $isAdmin = in_array($data['username'], $adminAccounts);
                    if ($isAdmin === true) {
                        $user->setAsAdmin();
                    }

                    $this->adminConfig['accounts'];

                    $result = new Result( Result::SUCCESS, $user);
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
