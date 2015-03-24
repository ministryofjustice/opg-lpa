<?php
namespace Opg\Lpa\Pdf\Worker\Response;

use SplFileInfo;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\ResponseInterface;

use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;

/**
 * Store the generated PDF into Redis.
 *
 * The Redis store is made up of two things:
 * - A list (REDIS_LIST) of PDF ids that are currently in the store.
 * - Zero or more blobs of binary data.
 *
 * When a new file is generated, it's id is added to the start of the list
 * and its data is written to REDIS_FILE_PREFIX + id.
 *
 * Once a file has been added we check whether any files need removing.
 * This done by checking if the file list is longer than allow, and, if so:
 * - Popping a file id off the end of the list.
 * - Deleting the blob for that id.
 * - Repeat until the list is down to the allowed length.
 *
 * Class RedisResponse
 * @package Opg\Lpa\Pdf\Worker
 */
class RedisResponse implements ResponseInterface  {

    private $docId;
    private $config;

    //---

    /**
     * Redis key for the file list.
     */
    const REDIS_LIST = 'pdf2:files:list';

    /**
     * Prefix for Redis keys of blobs (files).
     */
    const REDIS_FILE_PREFIX = 'pdf2:files:blob:';

    public function __construct( $docId ) {

        $this->docId = $docId;

        // load config/local.php by default
        $this->config = Config::getInstance()['worker']['redisResponse'];

    }

    /**
     * Store the file on the passed path for retrieval by the API service.
     *
     * @param $file
     */
    public function save( SplFileInfo $file ){

        echo "{$this->docId}: Response received: ".$file->getRealPath()."\n";

        //---

        $data = file_get_contents( $file->getRealPath() );

        //-------------------------------------------
        // Secure data

        $config = Config::getInstance()['pdf']['encryption'];

        if( !is_string($config['keys']['document']) || strlen($config['keys']['document']) != 32 ){
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $config['options']);

        // Set the secret key
        $blockCipher->setKey( $config['keys']['document'] );
        $blockCipher->setBinaryOutput( true );

        // Encrypt the PDF...
        $encryptedData = $blockCipher->encrypt( $data );

        //-------------------------------------------
        // Save to Redis

        $redis = new \Credis_Client( $this->config['host'], $this->config['port'], $timeout = null, $persistent = '', $db = 1);

        // Pushed the id onto the list...
        $redis->lpush( self::REDIS_LIST , $this->docId);

        // Save the file into redis...
        $redis->set( self::REDIS_FILE_PREFIX . $this->docId, $encryptedData );

        //---

        echo "{$this->docId}: Saved to Redis"."\n";

        //-------------------------------------------
        // Cleanup Redis...

        $allowedSize = $this->config['size'];

        // Failsafe
        if( !isset($allowedSize) || !is_int($allowedSize) ){ $allowedSize = 10; }

        // While there are more files than allowed...
        while( $redis->lLen( self::REDIS_LIST ) > $allowedSize ){

            // Pop an old file ident off the end of the list...
            $fileIdent = $redis->rPop( self::REDIS_LIST );

            // And delete it...
            $redis->del( self::REDIS_FILE_PREFIX . $fileIdent );

            echo "{$this->docId}: Redis cleanup: $fileIdent removed"."\n";

        } // while


    } // function

} // interface
