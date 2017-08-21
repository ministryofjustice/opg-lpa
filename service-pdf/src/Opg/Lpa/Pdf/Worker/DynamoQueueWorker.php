<?php

namespace Opg\Lpa\Pdf\Worker;

use Opg\Lpa\Pdf\Config\Config;
use DynamoQueue\Worker\ProcessorInterface;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException;
use Zend\Filter\Decompress;

class DynamoQueueWorker extends AbstractWorker implements ProcessorInterface
{
    /**
     * Return the object for handling the response
     *
     * @param $docId
     * @return Response\S3Response
     */
    protected function getResponseObject($docId)
    {
        return new Response\S3Response($docId);
    }

    /**
     * Process the specified job
     *
     * @param $jobId
     * @param $message
     */
    public function perform($jobId, $message)
    {
        $this->logger->info("New message: strlen($message) bytes\n");

        $config = Config::getInstance();
        $encryptionConfig = $config['pdf']['encryption'];
        $encryptionKeysQueue = $encryptionConfig['keys']['queue'];

        if (!is_string($encryptionKeysQueue) || strlen($encryptionKeysQueue) != 32) {
            throw new InvalidArgumentException('Invalid encryption key');
        }

        //  We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $encryptionConfig['options']);
        $blockCipher->setKey($encryptionKeysQueue);

        //  Get the JSON from the message and decompress it
        $decompressFilter = new Decompress('Gz');
        $compressedJson = $blockCipher->decrypt($message);
        $json = $decompressFilter->filter($compressedJson);

        $data = json_decode($json, true);

        //  Run the job...
        $this->run($jobId, $data['type'], $data['lpa']);
    }
}
