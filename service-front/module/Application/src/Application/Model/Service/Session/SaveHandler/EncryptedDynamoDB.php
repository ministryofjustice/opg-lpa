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

    /**
     * Returns a hash of the passed session id.
     *
     * @param $id
     * @return string
     */
    private function hashId( $id ){

        return hash( 'sha512', $id );

    }

    //-----------------------

    public function read($id){

        $id = $this->hashId( $id );

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

        $id = $this->hashId( $id );

        // Compress the data.
        $data = (new Compress( self::COMPRESSION_ADAPTER ))->filter( $data );

        // Encrypt the data
        $data = $this->getBlockCipher()->encrypt( $data );

        // Save it to the cache
        return parent::write( $id, $data );

    }

    public function destroy($id){
        return parent::destroy( $this->hashId( $id ) );
    }

    /**
     * Close a session from writing.
     *
     * THIS IS A HACK
     *
     * As formatId() is private, we cannot close the session correctly as it directly accesses session_id().
     * We therefore have to temporarily change it globally.
     *
     * A patch to the AWS SDK to been submitted to correct the properly.
     *
     * @return bool Success
     */
    public function close(){

        $id = session_id();

        session_id( $this->hashId( $id ) );

        $result = parent::close();

        session_id( $id );

        return $result;
    }

} // class
