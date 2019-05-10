<?php
namespace Application\Model\Service\Session\SaveHandler;

use Zend\Filter\Compress;
use Zend\Filter\Decompress;

/**
 * Our DynamoDB SaveHandler, with compression.
 *
 * Class CompressedDynamoDB
 * @package Application\Model\Service\Session\SaveHandler
 */
class CompressedDynamoDB extends DynamoDB {

    /**
     * The compression adapter to use (with ZF2 Filters)
     */
    const COMPRESSION_ADAPTER = 'Gz';

    //---

    /**
     * @param string $id
     * @return string
     */
    public function read($id){

        // Return the data from the DynamoDB
        $data = parent::read( $id );

        // If there's no data, just return
        if( empty($data) ){
            return $data;
        }

        // Decompress and return the data
        return (new Decompress( self::COMPRESSION_ADAPTER ))->filter(base64_decode($data));
    }

    /**
     * @param string $id
     * @param string $data
     * @return bool
     */
    public function write($id, $data){

        // Compress the data.
        $compressedData = (new Compress( self::COMPRESSION_ADAPTER ))->filter( $data );

        // Save it to DynamoDB
        return parent::write( $id, base64_encode($compressedData) );
    }

} // class
