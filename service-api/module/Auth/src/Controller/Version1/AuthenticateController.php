<?php
namespace Application\Controller\Version1;

use Opg\Lpa\Logger\LoggerTrait;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\AbstractActionController;

class AuthenticateController extends AbstractAuthenticatedController
{
    use LoggerTrait;

    /**
     * Authenticate a user.
     */
    public function indexAction(){

        $params = $this->getRequest()->getPost();

        $updateToken = ( isset($params['Update']) && $params['Update'] === 'false' ) ? false : true;

        //---

        if( isset($params['Token']) ){

            return $this->withToken( trim($params['Token']), $updateToken );

        } elseif( isset($params['Username']) && isset($params['Password']) ){

            return $this->withPassword( trim($params['Username']), $params['Password'], $updateToken );

        } else {

            return new ApiProblemResponse(
                new ApiProblem(400, 'Either Token or Username & Password must be passed')
            );

        }

    } // function

    /**
     * Deletes a token.
     */
    public function deleteAction(){

        $token = $this->params('token');

        if( !empty($token) ){
            $this->authenticationService->deleteToken( $token );
        }

        // Return 204 - No Content
        $this->response->setStatusCode(204);

    }

    /**
     * Authenticate a user with a passed token.
     *
     * @param $token string The token to validate.
     * @param $updateToken bool Should the token (if found) be modified.
     * @return JsonModel
     */
    private function withToken( $token, $updateToken ){

        $result = $this->authenticationService->withToken( $token, $updateToken );

        if( is_string($result) ){

            $this->getLogger()->notice("Failed authentication attempt with a token",[
                'token' => $token
            ]);

            return new ApiProblemResponse(
                new ApiProblem(401, $result)
            );
        }

        // Map DateTimes to strings
        $result = array_map( function($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        $this->getLogger()->info("User successfully authenticated with a token",
            [
                'tokenExtended' => (bool)$updateToken,
                'userId'=>$result['userId'],
                'expiresAt'=>$result['expiresAt'],
            ]
        );

        return new JsonModel( $result );

    }

    /**
     * Authenticate a user with a passed usernamer (email address) and password.
     *
     * @param $username
     * @param $password
     * @param $updateToken bool Should the token (if found) be modified.
     * @return JsonModel
     */
    private function withPassword( $username, $password, $updateToken ){

        $result = $this->authenticationService->withPassword( $username, $password, $updateToken );

        if( is_string($result) ){

            $this->getLogger()->notice("Failed authentication attempt with a password",[
                'username' => $username
            ]);

            return new ApiProblemResponse(
                new ApiProblem(401, $result)
            );
        }

        // Map DateTimes to strings
        $result = array_map( function($v) {
            return ( $v instanceof \DateTime ) ? $v->format('Y-m-d\TH:i:sO') : $v;
        }, $result);

        $this->getLogger()->info("User successfully authenticated with a password",
            [
                'userId'=>$result['userId'],
                'last_login'=>$result['last_login'],
                'expiresAt'=>$result['expiresAt'],
            ]
        );

        return new JsonModel( $result );

    }

} // class
