<?php
namespace Application;

use DateTime;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Session\Container;

use Zend\ServiceManager\ServiceLocatorInterface;

use Application\Model\Service\Authentication\Adapter\LpaApiClient as LpaApiClientAuthAdapter;

use Opg\Lpa\Api\Client\Client as ApiClient;

use Application\Model\Service\Lpa\Application as LpaApplicationService;

class Module{

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        //Only bootstrap the session if it's *not* PHPUnit.
        if(!strstr($e->getApplication()->getServiceManager()->get('Request')->getServer('SCRIPT_NAME'), 'phpunit')) {
            $this->bootstrapSession($e);
            $this->bootstrapIdentity($e);
        }
    }

    /**
     * Sets up and starts global sessions.
     *
     * @param MvcEvent $e
     */
    public function bootstrapSession(MvcEvent $e){

        $session = $e->getApplication()->getServiceManager()->get('SessionManager');

        // Always starts the session.
        $session->start();

        // Ensures this SessionManager is used for all Session Containers.
        Container::setDefaultManager($session);

        //---

        $container = new Container('initialised');

        // If it's a new session, regenerate the id.
        if (!isset($container->init)) {
            $session->regenerateId(true);
            $container->init = true;
        }

    } // function

    /**
     * This performs lazy checking of the user's auth token (if there is one).
     *
     * It works by only checking if the token is invalid if once we've gone past our recorded (in session)
     * 'tokenExpiresAt' time. Before then we assume the token is valid (leaving the API to verify this).
     *
     * If we're past 'tokenExpiresAt', then we query the Auth service to check the token's state. If it's still
     * valid we update 'tokenExpiresAt'. Otherwise we clear the user's identity form the session.
     *
     * We don't deal with forcing the user to re-authenticate here as they
     * may be accessing a page that does not require authentication.
     *
     * TODO - Consider using an extended AuthenticationService and put this logic in hasIdentity(), meaning we'd
     * TODO - only check if we try to access the identity, making the logic even lazier.
     *
     * @param MvcEvent $e
     */
    public function bootstrapIdentity(MvcEvent $e){

        $sm = $e->getApplication()->getServiceManager();

        $auth = $sm->get('AuthenticationService');

        // If we have an identity...
        if ( ($identity = $auth->getIdentity()) != null ) {

            // If we're beyond the original time we expected the token to expire...
            if( (new DateTime) > $identity->tokenExpiresAt() ){

                // Get the tokens details...
                $info = $sm->get('ApiClient')->getTokenInfo( $identity->token() ) ;

                // If the token has not expired...
                if( isset($info['expires_in']) ){

                    // update the time the token expires in the session
                    $identity->tokenExpiresIn( $info['expires_in'] );

                } else {

                    // else the user will need to re-login, so remove the current identity.
                    $auth->clearIdentity();

                }

            } // if we're beyond tokenExpiresAt

        } // if we have an identity

    } // function

    //-------------------------------------------

    public function getServiceConfig(){

        return [
            'invokables' => [
                'AuthenticationService' => 'Zend\Authentication\AuthenticationService',
            ],
            'factories' => [
                'SessionManager' => 'Application\Model\Service\Session\SessionFactory',
                'LpaApplicationService' => function( ServiceLocatorInterface $sm ){
                    return new LpaApplicationService( $sm->get('ApiClient') );
                },
                'LpaApiClientAuthAdapter' => function( ServiceLocatorInterface $sm ){
                    return new LpaApiClientAuthAdapter( $sm->get('ApiClient') );
                },
                'ApiClient' => function( ServiceLocatorInterface $sm ){

                    $client = new ApiClient();

                    //---

                    $auth = $sm->get('AuthenticationService');

                    if ( $auth->hasIdentity() ) {

                        $identity = $auth->getIdentity();
                        $client->setUserId( $identity->id() );
                        $client->setToken( $identity->token() );

                    }

                    //---

                    return $client;
                }
            ],
        ];

    } // function

    public function getControllerConfig(){

        return [
            'initializers' => [
                'UserAwareInitializer' => 'Application\ControllerFactory\UserAwareInitializer',
                'LpaAwareInitializer' => 'Application\ControllerFactory\LpaAwareInitializer',
            ]
        ];

    }

    //-------------------------------------------

    public function getAutoloaderConfig(){
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getConfig(){
        return include __DIR__ . '/config/module.config.php';
    }

} // class
