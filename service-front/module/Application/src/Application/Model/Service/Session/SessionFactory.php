<?php
namespace Application\Model\Service\Session;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Session\SessionManager;

use Zend\Cache\StorageFactory as CacheStorageFactory;

/**
 * Create the SessionManager for use throughout the LPA frontend.
 *
 * Class SessionFactory
 * @package Application\Model\Service\Session
 */
class SessionFactory implements FactoryInterface {

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return SessionManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator){

        $manager = new SessionManager();

        //----------------------------------------
        // Setup Redis as the save handler

        # TODO - pull settings from config...

        $redis = CacheStorageFactory::factory([
            'adapter' => [
                'name' => 'redis',
                'options' => [
                    'namespace' => 'session',
                    'ttl' => (60 * 60 * 24 * 14), // Set a default (longish) ttl to clean up sessions that did not end properly.
                    'server' => [
                        'host' => 'redisfront.local',
                        'port' => '6379',
                    ]
                ],
            ]
        ]);

        //----------------------------------------
        // Setup the encryption save handler

        # TODO - pull this from Config...
        $key = '0g5vi1m1602uyD5585lKNaUJE0p22p2k';

        $saveHandler = new SaveHandler\EncryptedCache( $redis, $key );

        $manager->setSaveHandler($saveHandler);

        //----------------------------------------

        return $manager;

    } // function

} // class
