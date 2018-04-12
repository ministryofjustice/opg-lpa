<?php

namespace Application\Model\Rest\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Library\Lpa\StateChecker;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\LpaConsumerInterface;
use Aws\S3\S3Client;
use DynamoQueue\Queue\Client as DynamoQueue;
use DynamoQueue\Queue\Job\Job as DynamoQueueJob;
use Zend\Crypt\BlockCipher;
use Zend\Crypt\Symmetric\Exception\InvalidArgumentException as CryptInvalidArgumentException;
use Zend\Filter\Compress;

class Resource extends AbstractResource implements LpaConsumerInterface
{
    /*
     * @var array
     */
    private $pdfConfig = [];

    /**
     * @var DynamoQueue
     */
    private $dynamoQueueClient;

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var array
     */
    private $pdfTypes = [
        'lp1',
        'lp3',
        'lpa120'
    ];

    /**
     * @param $id
     * @return ApiProblem|ValidationApiProblem|FileResponse|Entity
     */
    public function fetch($id)
    {
        $this->checkAccess();

        $lpa = $this->getLpa();

        $validation = $lpa->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if (in_array($id, $this->pdfTypes)) {
            $details = $this->getPdfDetails($id);

            if ($details['status'] == Entity::STATUS_NOT_QUEUED) {
                // Then add the LPA to the PDF queue.
                $this->addLpaToQueue($id);

                $details['status'] = Entity::STATUS_IN_QUEUE;
            }

            // Then assume they're asking for info about a PDF...
            return new Entity($details, $lpa);
        }

        //  If they've added '.pdf' onto the end of the type they want to download the file - create array including '.pdf'...
        $typesWithExtention = array_map(function ($v) {
            return "{$v}.pdf";
        }, $this->pdfTypes);

        if (in_array($id, $typesWithExtention)) {
            $type = rtrim($id, '.pdf');

            $file = $this->getPdfFile($type);

            if ($file !== false) {
                return new FileResponse($file, FileResponse::TYPE_PDF);
            }
        }

        return new ApiProblem(404, 'Document not found');
    }

    /**
     * @param $type
     * @return array
     */
    private function getPdfDetails($type)
    {
        $lpa = $this->getLpa();

        // Check if we can generate this document type.
        $state = new StateChecker($lpa);

        switch ($type) {
            case 'lp1':
                $complete = $state->canGenerateLP1();
                break;
            case 'lp3':
                $complete = $state->canGenerateLP3();
                break;
            case 'lpa120':
                $complete = $state->canGenerateLPA120();
                break;
        }

        // If the LPA is complete, we check to see the status...
        if ($complete) {
            $status = $this->getPdfStatus($type);
        } else {
            // Otherwise the status is 'not available'...
            $status = Entity::STATUS_NOT_AVAILABLE;
        }

        return array(
            'type' => $type,
            'complete' => $complete,
            'status' => $status,
        );
    }

    /**
     * This checks the status of the PDF document that represents the LPA (and form type).
     *
     * The result can be:
     * - It's not in the queue (because it has not been added)
     * - It's in the queue ready for processing.
     * - It's ready to be downloaded.
     *
     * @param $type
     * @return string
     */
    private function getPdfStatus($type)
    {
        $ident = $this->getPdfIdent($type);

        // Check if the file already exists in the cache.
        $bucketConfig = $this->pdfConfig['cache']['s3']['settings'];

        try {
            $this->s3Client->headObject($bucketConfig + [
                'Key' => $ident,
            ]);

            // If it's in the cache, clean it out of the queue.
            $this->dynamoQueueClient->deleteJob($ident);

            // If we get here it exists in the bucket...
            return Entity::STATUS_READY;
        } catch (\Aws\S3\Exception\S3Exception $ignore) {}

        // Check for the job status in the queue
        $status = $this->dynamoQueueClient->checkStatus($ident);

        if (in_array($status, [DynamoQueueJob::STATE_WAITING, DynamoQueueJob::STATE_PROCESSING])) {
            return Entity::STATUS_IN_QUEUE;
        } elseif (in_array($status, [DynamoQueueJob::STATE_DONE, DynamoQueueJob::STATE_ERROR])) {
            // If we get here something strange has happened because:
            //  - The PDF should have been in the cache; or
            //  - An error occurred.
            $this->dynamoQueueClient->deleteJob($ident);
        }

        return Entity::STATUS_NOT_QUEUED;
    }

    /**
     * @param $type
     */
    private function addLpaToQueue($type)
    {
        $lpa = $this->getLpa();

        // Setup the message
        $message = json_encode([
            'lpa' => $lpa->toArray(),
            'type' => strtoupper($type), // The type of document we want generating
        ]);

        // Compress the message - we compress JSON put into the queue with this
        $message = (new Compress('Gz'))->filter($message);

        // Encrypt the message
        $encryptionKey = $this->pdfConfig['encryption']['keys']['queue'];

        if (!is_string($encryptionKey) || strlen($encryptionKey) != 32) {
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $this->pdfConfig['encryption']['options']);

        // Set the secret key
        $blockCipher->setKey($encryptionKey);

        // Encrypt the JSON...
        $encryptedMessage = $blockCipher->encrypt($message);

        // Add the message to the queue
        $this->dynamoQueueClient->enqueue('\Opg\Lpa\Pdf\Worker\DynamoQueueWorker', $encryptedMessage, $this->getPdfIdent($type));
    }

    /**
     * @param $type
     * @return \Aws\Result|bool|string
     */
    public function getPdfFile($type)
    {

        $bucketConfig = $this->pdfConfig['cache']['s3']['settings'];

        try {
            $file = $this->s3Client->getObject($bucketConfig + [
                'Key' => $this->getPdfIdent($type),
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            return false;
        }

        $file = $file['Body']->getContents();

        // Decrypt the PDF
        $encryptionKeysConfig = $this->pdfConfig['encryption']['keys']['document'];

        if (!is_string($encryptionKeysConfig) || strlen($encryptionKeysConfig) != 32) {
            throw new CryptInvalidArgumentException('Invalid encryption key');
        }

        // We use AES encryption with Cipher-block chaining (CBC); via PHPs mcrypt extension
        $blockCipher = BlockCipher::factory('mcrypt', $this->pdfConfig['encryption']['options']);

        // Set the secret key
        $blockCipher->setKey($encryptionKeysConfig);
        $blockCipher->setBinaryOutput(true);

        // Encrypt the JSON and return
        return $blockCipher->decrypt($file);
    }

    /**
     * Generates a unique identifier the represents the LPA data and type of form ( lp1, lp3, etc. ).
     *
     * @param $type
     * @return string
     */
    private function getPdfIdent($type)
    {
        // $keys are included so a new ident is generated when encryption keys change.
        $keys = $this->pdfConfig['encryption']['keys'];

        $hash = hash('sha512', md5($this->getLpa()->toJson()) . $keys['document'] . $keys['queue']);

        return strtolower("{$type}-{$hash}");
    }

    /**
     * Set the PDF config
     *
     * @param array $config
     */
    public function setPdfConfig(array $config)
    {
        if (isset($config['pdf'])) {
            $this->pdfConfig = $config['pdf'];
        }
    }

    /**
     * @param DynamoQueue $dynamoQueueClient
     */
    public function setDynamoQueueClient(DynamoQueue $dynamoQueueClient)
    {
        $this->dynamoQueueClient = $dynamoQueueClient;
    }

    /**
     * @param S3Client $s3Client
     */
    public function setS3Client(S3Client $s3Client)
    {
        $this->s3Client = $s3Client;
    }
}
