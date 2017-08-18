<?php

namespace Opg\Lpa\Pdf\Worker;

use DynamoQueue\Worker\ProcessorInterface;
use Opg\Lpa\Pdf\Config\Config;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;
use Zend\Filter\Decompress;

class DynamoQueueWorker extends AbstractWorker implements ProcessorInterface {

    /**
     * The compression adapter to use (with ZF2 Filters)
     * We compress JSON put into the queue with this.
     */
    const COMPRESSION_ADAPTER = 'Gz';

    //----------------------------------------------------

    /**
     * Return the RedisResponse for handling the response.
     *
     * @param $docId
     * @return \Opg\Lpa\Pdf\Worker\Response\AbstractResponse
     */
    protected function getResponseObject( $docId ){
        return new Response\S3Response( $docId );
    }


    public function perform( $jobId, $message ){

        $messageSize = strlen( $message );

        $this->logger->info("New message: $messageSize bytes\n");

        //---------------------------------------------
        // Decrypt the JSON...

        $config = Config::getInstance()['pdf']['encryption'];

        if( !is_string($config['keys']['queue']) || strlen($config['keys']['queue']) != 32 ){
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        //---

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $config['options']);

        // Set the secret key
        $blockCipher->setKey( $config['keys']['queue'] );

        $compressedJson = $blockCipher->decrypt( $message );

        //---------------------------------------------
        // Decompress the JSON...

        $json = (new Decompress( self::COMPRESSION_ADAPTER ))->filter( $compressedJson );

        //---

        $data = json_decode( $json, true );

        //---------------------------------------------
        // Run the job...

        $this->run( $jobId, $data['type'], $data['lpa'] );

    } // function

}
