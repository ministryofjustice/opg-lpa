<?php
namespace Application\Model\Service\Session\SaveHandler;

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
     * @param BlockCipher $blockCipher The BlockCipher to use for encryption.
     */
    public function __construct( CacheStorageInterface $cacheStorage, BlockCipher $blockCipher ){
        $this->setCacheStorage($cacheStorage);
        $this->blockCipher = $blockCipher;
    }

    /**
     * Read and decrypt session data
     *
     * @param string $id
     * @return string
     */
    public function read($id){

        $id = $this->hashId( $id );

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

        $id = $this->hashId( $id );

        // Encrypt the data
        $data = $this->blockCipher->encrypt( $data );

        // Save it to the cache
        return $this->getCacheStorage()->setItem($id, $data);

    }

    /**
     * Returns a hash of the passed session id.
     *
     * @param $id
     * @return string
     */
    private function hashId( $id ){

        return hash( 'sha512', $id );

    }

} // class
