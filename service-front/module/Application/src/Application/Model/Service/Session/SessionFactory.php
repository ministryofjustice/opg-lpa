<?php
namespace Application\Model\Service\Session;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Session\SessionManager;
use Zend\Session\Exception\RuntimeException;

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

        $config = $serviceLocator->get('Config');

        if( !isset( $config['session'] ) ){
            throw new RuntimeException('Session configuration setting not found ');
        }

        $config = $config['session'];

        //---

        $manager = new SessionManager();

        //----------------------------------------
        // Setup Redis as the save handler

        $redis = CacheStorageFactory::factory([
            'adapter' => [
                'name' => 'redis',
                'options' => $config['redis'],
            ]
        ]);

        //----------------------------------------
        // Setup the encryption save handler

        $key = $config['encryption']['key'];

        $saveHandler = new SaveHandler\EncryptedCache( $redis, $key );

        $manager->setSaveHandler($saveHandler);

        //----------------------------------------

        return $manager;

    } // function

} // class
