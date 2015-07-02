<?php
namespace Opg\Lpa\Pdf\Worker;

use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;
use Opg\Lpa\Pdf\Logger\Logger;
use Opg\Lpa\Pdf\Config\Config;

class ResqueWorker extends AbstractWorker {

    /**
     * Return the RedisResponse for handling the response.
     *
     * @param $docId
     * @return \Opg\Lpa\Pdf\Service\ResponseInterface
     */
    protected function getResponseObject( $docId ){
        return new Response\RedisResponse( $docId );
    }

    public function perform(){

        if( !isset($this->args['docId']) ){
            throw new \Exception('Missing field: docId');
        }

        if( !isset($this->args['lpa']) ){
            throw new \Exception('Missing field: lpa');
        }

        if( !isset($this->args['type']) ){
            throw new \Exception('Missing field: type');
        }

        //---

        $messageSize = strlen( $this->args['lpa'] );

        Logger::getInstance()->info("New message: $messageSize bytes\n");

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

        $json = $blockCipher->decrypt( $this->args['lpa'] );

        //---------------------------------------------
        // Run the job...

        $this->run( $this->args['docId'], $this->args['type'], $json );

    } // function

} // class
