<?php
namespace Application\Model\Service\Session\SaveHandler;

use Zend\Crypt\Symmetric\Exception\InvalidArgumentException;

use Zend\Cache\Storage\StorageInterface as CacheStorageInterface;
use Zend\Session\SaveHandler\Cache as CacheSaveHandler;

use Zend\Crypt\BlockCipher;

/**
 * Adds encryption support to the Zend Cache Save Handler.
 *
 * Class EncryptedCache
 * @package Application\Model\Service\Session\SaveHandler
 */
class EncryptedCache extends CacheSaveHandler {

    /**
     * Instance of the Block Cipher to use for encryption.
     *
     * @var BlockCipher
     */
    private $blockCipher;

    //---

    /**
     * Constructor
     *
     * @param CacheStorageInterface $cacheStorage
     * @param string $key 32 character encryption key
     */
    public function __construct( CacheStorageInterface $cacheStorage, $key ){
        $this->setCacheStorage($cacheStorage);

        // AES is rijndael-128 with a 32 character (256 bit) key.
        if( strlen( $key ) != 32 ){
            throw new InvalidArgumentException('Key must be a string of 32 characters');
        }

        //---

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $this->blockCipher = BlockCipher::factory('mcrypt', ['algorithm' => 'aes', 'mode' => 'cbc']);

        $this->blockCipher->setKey( $key );

        // Output raw binary
        $this->blockCipher->setBinaryOutput( true );

    }

    /**
     * Read and decrypt session data
     *
     * @param string $id
     * @return string
     */
    public function read($id){

        // Return the data from the cache
        $data = $this->getCacheStorage()->getItem($id);

        // If there's no data, just return it (null)
        if( empty($data) ){ return $data; }

        // Decrypt and return the data
        return $this->blockCipher->decrypt( $data );

    }

    /**
     * Encrypt and write session data
     *
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data){

        // Encrypt the data
        $data = $this->blockCipher->encrypt( $data );

        // Save it to the cache
        return $this->getCacheStorage()->setItem($id, $data);

    }

} // class
