<?php
namespace Application\Model\Service\Session;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;

use Zend\Session\Exception\RuntimeException;

use Aws\DynamoDb\DynamoDbClient;

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
        // Set the cookie domain

        // Only if it's not a Console request.
        if( !( $serviceLocator->get('Request') instanceof \Zend\Console\Request ) ){

            // This is requirement of the GDS service checker

            // Get the hostname of the current request
            $hostname = $serviceLocator->get('Request')->getUri()->getHost();

            // and set it as the domain cookie.
            ini_set( 'session.cookie_domain', $hostname );

        }


        //----------------------------------------
        // Setup the DynamoDb Client

        $dynamoDb = new DynamoDbClient( $config['dynamodb']['client'] );


        //----------------------------------------
        // Setup the DynamoDb save handler

        if( $config['encryption']['enabled'] !== true ){

            // Use the standard SaveHandler...
            $saveHandler = SaveHandler\DynamoDB::fromClient( $dynamoDb, $config['dynamodb']['settings'] );

        } else {

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
            // I want to enable this, but Amazon's  driver only supports strings at present.
            //$blockCipher->setBinaryOutput( true );

            //---

            $saveHandler = SaveHandler\EncryptedDynamoDB::fromClient( $dynamoDb, $config['dynamodb']['settings'] );

            $saveHandler->setBlockCipher( $blockCipher );

        } // if

        //-------------------------------

        $manager = new SessionManager();

        $manager->setSaveHandler($saveHandler);

        return $manager;
        
    } // function

} // class
