<?php
namespace Application\Model\Service\Session;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;
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
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('Config');

        if( !isset( $config['session'] ) ){
            throw new RuntimeException('Session configuration setting not found');
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
        if( !( $container->get('Request') instanceof \Zend\Console\Request ) ){

            // This is requirement of the GDS service checker

            // Get the hostname of the current request
            $hostname = $container->get('Request')->getUri()->getHost();

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

            $keys = $config['encryption']['keys'];

            /*
             * Keys are in the format:
             *      array( <int ident> => <string key>, ... )
             */

            //---

            // Validate the keys

            if( !is_array($keys) || empty($keys) ){
                throw new RuntimeException('At least one session encryption key must be set');
            }

            foreach( $keys as $ident => $key ){

                // AES is rijndael-128 with a 32 character (256 bit) key.
                if( strlen( $key ) != 32 ){
                    throw new CryptInvalidArgumentException("Key ($ident) must be a string of 32 characters");
                }

            }

            //---

            // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
            $blockCipher = BlockCipher::factory('openssl', [
                'algorithm' => 'aes',
                'mode' => 'cbc',
            ]);

            //---

            $saveHandler = new SaveHandler\EncryptedDynamoDB(
                new SaveHandler\HashedKeyDynamoDbSessionConnection( $dynamoDb, $config['dynamodb']['settings'] )
            );

            $saveHandler->setBlockCipher( $blockCipher, $keys );

        } // if

        //-------------------------------

        $manager = new SessionManager();

        $manager->setSaveHandler($saveHandler);

        return $manager;
    }
} // class
