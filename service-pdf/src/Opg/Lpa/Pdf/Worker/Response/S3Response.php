<?php
namespace Opg\Lpa\Pdf\Worker\Response;

use SplFileInfo;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\ResponseInterface;

use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;

use Aws\S3\S3Client;

/**
 * Stores the generated PDF into Amazon S3.
 *
 * Files will be automatically deleted after a period by the bucket's Lifecycle policy.
 *
 * Class S3Response
 * @package Opg\Lpa\Pdf\Worker\Response
 */
class S3Response implements ResponseInterface
{

    private $docId;
    private $config;

    //---

    public function __construct($docId){

        $this->docId = $docId;

        // load config/local.php by default
        $this->config = Config::getInstance()['worker']['s3Response'];

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
        // Save to S3

        $s3 = new S3Client( $this->config['client'] );

        $file = $this->config['settings'] + [
            'Key' => (string)$this->docId,
            'Body' => $encryptedData,
            ];

        //---

        try {

            // Upload the file to S3.
            $s3->putObject($file);

        } catch (\Aws\Exception\S3Exception $e) {

            echo "{$this->docId}: Failed to saved to S3"."\n";

            // Re-throw the exception to catch further up the stack.
            throw $e;

        }

        //---

        echo "{$this->docId}: Saved to S3 in {$this->config['settings']['Bucket']}"."\n";

    } // function

} // class
