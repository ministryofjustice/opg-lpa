<?php
namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use Zend\Session\Container;

use Zend\ServiceManager\ServiceLocatorInterface;

use Application\Model\Service\Authentication\Adapter\LpaApiClient as LpaApiClientAuthAdapter;

use Opg\Lpa\Api\Client\Client as ApiClient;

class Module{

    public function onBootstrap(MvcEvent $e){

        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
        
        if(!strstr($e->getApplication()->getServiceManager()->get('Request')->getServer('SCRIPT_NAME'), 'phpunit')) {
            $this->bootstrapSession($e);
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

    public function getServiceConfig(){

        return [
            'invokables' => [
                'AuthenticationService' => 'Zend\Authentication\AuthenticationService',
            ],
            'factories' => [
                'SessionManager' => 'Application\Model\Service\Session\SessionFactory',
                'LpaApiClientAuthAdapter' => function( ServiceLocatorInterface $sm ){
                    return new LpaApiClientAuthAdapter( $sm->get('ApiClient') );
                },
                'ApiClient' => function( ServiceLocatorInterface $sm ){

                    $client = new ApiClient();

                    //---

                    $auth = $sm->get('AuthenticationService');

                    if ($auth->hasIdentity()) {

                        $identity = $auth->getIdentity();

                        if( isset($identity->token) ){
                            $client->setToken( $identity->token );
                        }

                    }

                    //---

                    return $client;
                }
            ],
        ];

    } // function

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
