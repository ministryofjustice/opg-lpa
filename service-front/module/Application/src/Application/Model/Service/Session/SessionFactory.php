<?php
namespace Application\Model\Service\Session;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;

use Zend\Session\SessionManager;
use Zend\Session\Exception\RuntimeException;
use Zend\Session\SaveHandler\Cache as CacheSaveHandler;

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

        //----------------------------------------
        // Apply any native PHP level settings

        if( isset($config['native_settings']) && is_array($config['native_settings']) ){

            foreach( $config['native_settings'] as $k => $v ){
                ini_set( 'session.'.$k, $v );
            }

        }

        //----------------------------------------

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

        if( $config['encryption']['enabled'] === true ){

            $key = $config['encryption']['key'];

            // AES is rijndael-128 with a 32 character (256 bit) key.
            if( strlen( $key ) != 32 ){
                throw new CryptInvalidArgumentException('Key must be a string of 32 characters');
            }

            //---

            // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
            $blockCipher = BlockCipher::factory('mcrypt', [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ]);

            // Set the secret key
            $blockCipher->setKey( $key );

            // Output raw binary (as opposed to base64).
            // It's smaller and Redis is fine with it.
            $blockCipher->setBinaryOutput( true );

            //---

            $saveHandler = new SaveHandler\EncryptedCache( $redis, $blockCipher );

        } else {

            // Else if encryption is not enabled, just use the normal Cache Save Handler
            $saveHandler = new CacheSaveHandler( $redis );

        }

        $manager->setSaveHandler($saveHandler);

        //----------------------------------------

        return $manager;

    } // function

} // class
