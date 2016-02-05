<?php
namespace Application\Model\Service\Session\SaveHandler;

use Zend\Crypt\BlockCipher;
use Zend\Session\Exception\RuntimeException;

use Zend\Filter\Compress;
use Zend\Filter\Decompress;

/**
 * Our DynamoDB SaveHandler, with encryption AND compression.
 *
 * Class EncryptedDynamoDB
 * @package Application\Model\Service\Session\SaveHandler
 */
class EncryptedDynamoDB extends DynamoDB {

    /**
     * The compression adapter to use (with ZF2 Filters)
     */
    const COMPRESSION_ADAPTER = 'Gz';

    //---

    /**
     * Instance of the Block Cipher to use for encryption.
     *
     * @var BlockCipher
     */
    private $blockCipher;

    //--------------------

    /**
     * Sets the pre-configured BlockCipher to use for encryption.
     *
     * @param BlockCipher $blockCipher
     */
    public function setBlockCipher( BlockCipher $blockCipher ){

        $this->blockCipher = $blockCipher;

    }

    /**
     * Returns the current BlockCipher.
     *
     * @return BlockCipher
     */
    private function getBlockCipher(){

        if( !( $this->blockCipher instanceof BlockCipher ) ){
            throw new RuntimeException('No session BlockCipher set');
        }

        return $this->blockCipher;
    }

    //-----------------------

    public function read($id){

        // Return the data from the cache
        $data = parent::read( $id );

        // If there's no data, just return it (null)
        if( empty($data) ){ return $data; }

        // Decrypt and return the data
        $data =  $this->getBlockCipher()->decrypt( $data );

        // Decompress the data.
        return (new Decompress( self::COMPRESSION_ADAPTER ))->filter( $data );

    }

    public function write($id, $data){

        // Compress the data.
        $data = (new Compress( self::COMPRESSION_ADAPTER ))->filter( $data );

        // Encrypt the data
        $data = $this->getBlockCipher()->encrypt( $data );

        // Save it to the cache
        return parent::write( $id, $data );

    }

} // class
