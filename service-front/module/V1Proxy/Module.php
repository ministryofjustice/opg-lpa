<?php
namespace V1Proxy;

use GuzzleHttp\Client as GuzzleClient;

use Zend\Mvc\MvcEvent;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Session\Container;

use Zend\Cache\StorageFactory as CacheStorageFactory;

class Module {

    public function getServiceConfig() {
        return [
            'invokables' => [
                'ProxyAboutYou' => 'V1Proxy\Model\AboutYou',
                'ChangeEmailAddress' => 'V1Proxy\Model\ChangeEmailAddress',
                'ProxyDashboard' => 'V1Proxy\Model\Dashboard',
            ],
            'factories' => [
                'ProxyCache' => function( ServiceLocatorInterface $sm ){

                    $config = $sm->get('Config')['v1proxy'];

                    return CacheStorageFactory::factory([
                        'adapter' => [
                            'name' => 'redis',
                            'options' => $config['redis'],
                        ]
                    ]);

                },
                'ProxyOldApiClient' => function( ServiceLocatorInterface $sm ){

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
                    $client->setDefaultOption( 'headers/Token', $token );

                    return $client;

                },
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
