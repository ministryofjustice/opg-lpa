<?php

namespace Opg\Lpa\Pdf\Worker\Response;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException;
use SplFileInfo;

/**
 * Stores the generated PDF into Amazon S3
 *
 * Files will be automatically deleted after a period by the bucket's Lifecycle policy
 */
class S3Response extends AbstractResponse
{
    /**
     * Store the file on the passed path for retrieval by the API service.
     *
     * @param SplFileInfo $file
     * @throws InvalidArgumentException|S3Exception
     */
    public function save(SplFileInfo $file)
    {
        $this->logToConsole('Response received: ' . $file->getRealPath());

        //  Get the file contents and encrypt them
        $fileContents = file_get_contents($file->getRealPath());

        $encryptionConfig = $this->config['pdf']['encryption'];
        $encryptionKeysDocument = $encryptionConfig['keys']['document'];

        if (!is_string($encryptionKeysDocument) || strlen($encryptionKeysDocument) != 32) {
            throw new InvalidArgumentException('Invalid encryption key');
        }

        //  We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $encryptionConfig['options']);
        $blockCipher->setKey($encryptionKeysDocument);
        $blockCipher->setBinaryOutput(true);

        //  Encrypt the PDF...
        $fileContentsEncrypted = $blockCipher->encrypt($fileContents);

        //  Create the S3 client
        $workerConfig = $this->config['worker']['s3Response'];
        $workerSettingsConfig = $workerConfig['settings'];
        $s3 = new S3Client($workerConfig['client']);

        try {
            //  Put the encrypted file to S3
            $file = $workerSettingsConfig + [
                'Key'  => (string)$this->docId,
                'Body' => $fileContentsEncrypted,
            ];

            $s3->putObject($file);
        } catch (S3Exception $e) {
            $this->logToConsole('ERROR: Failed to save to S3 in ' . $workerSettingsConfig['Bucket']);

            throw $e;
        }

        $this->logToConsole('Saved to S3 in ' . $workerSettingsConfig['Bucket']);
    }
}
