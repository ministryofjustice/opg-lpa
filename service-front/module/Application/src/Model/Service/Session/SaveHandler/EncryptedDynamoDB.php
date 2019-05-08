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


    /**
     * Array of currently active keys.
     *
     * The format should be:
     *  <int ident> => <string key>
     *
     * The biggest ident value should be treated the the 'current' key.
     *
     * @var array
     */
    private $keys;

    //--------------------

    /**
     * Sets the pre-configured BlockCipher to use for encryption.
     *
     * @param BlockCipher $blockCipher
     * @param array $keys
     */
    public function setBlockCipher( BlockCipher $blockCipher, array $keys ){

        $this->keys = $keys;
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

    /**
     * TODO: Decryption left in to facilitate moving to DynamoDB encryption. Once moved, it can be stripped out.
     *
     * @param string $id
     * @return array|string|null
     */
    public function read($id){

        // Return the data from the DynamoDB
        $data = parent::read( $id );

        // If there's no data, just return
        if( empty($data) ){
            return $data;
        }

        // Split the data into encryption key ident, and actual session data.
        $data = explode( '.', $data );

        // If not key ident was found.
        if( count($data) != 2 ){

            // Then we used DynamoDB for encryption, thus we only need to decompress.
            return  (new Decompress( self::COMPRESSION_ADAPTER ))->filter(base64_decode($data[0]));

        } else {

            // If the key ident doesn't match a known key...
            if( !isset( $this->keys[$data[0]] ) ){
                return null;
            }

            $sessionKey = $this->keys[$data[0]];
            $sessionData = $data[1];

        }

        //---

        // Decrypt and return the data
        $decryptedData =  $this->getBlockCipher()->setKey( $sessionKey )->decrypt( $sessionData );

        // Decompress the data.
        return (new Decompress( self::COMPRESSION_ADAPTER ))->filter( $decryptedData );

    }

    public function write($id, $data){

        // Compress the data.
        $compressedData = (new Compress( self::COMPRESSION_ADAPTER ))->filter( $data );

        // Save it to DynamoDB
        return parent::write( $id, base64_encode($compressedData) );

    }

} // class
