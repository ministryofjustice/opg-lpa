<?php
namespace V1Proxy;

use GuzzleHttp\Client as GuzzleClient;

use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module {

    public function getServiceConfig() {
        return [
            'factories' => [
                'ProxyClient' => function( ServiceLocatorInterface $sm ){

                    $auth = $sm->get('AuthenticationService');

                    if (!$auth->hasIdentity()) {
                        throw new \RuntimeException('V1Proxy Authentication error: no token');
                    }

                    $token = $auth->getIdentity()->token();

                    //---

                    $client = new GuzzleClient();

                    // Proxy errors (4xx) to the browser.
                    $client->setDefaultOption( 'exceptions', false );

                    // Proxy redirects to the browser.
                    $client->setDefaultOption( 'allow_redirects', false );

                    // Set the authentication token.
                    $client->setDefaultOption( 'headers/X-AuthOne', $token );

                    // Add the hmglsd cookie to prevent 'enable cookies' redirects.
                    $client->setDefaultOption( 'headers/Cookie', 'hmglsd=1' );

                    return $client;

                } // ProxyClient
            ],
        ];
    } // function

    public function getConfig(){
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig(){
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    } // function

} // class
