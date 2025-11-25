<?php

namespace Application\Model\Service\Pdfs;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ValidationApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Model\DataAccess\Repository\Application\ApplicationRepositoryTrait;
use Application\Model\Service\AbstractService;
use Aws\S3\S3Client;
use Aws\Sqs\SqsClient;
use Laminas\Filter\Compress;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;

class Service extends AbstractService
{
    use ApplicationRepositoryTrait;
    use LoggerTrait;

    /**
     * PDF status constants
     */
    // PDF cannot be generated as we do not have all the data
    public const STATUS_NOT_AVAILABLE = 'not-available';

    // The LPA is not in the PDF queue
    public const STATUS_NOT_QUEUED = 'not-in-queue';

    // The LPA is in the PDF queue
    public const STATUS_IN_QUEUE = 'in-queue';

    // THe PDF is available for immediate download
    public const STATUS_READY = 'ready';

    /**
     * @var array
     */
    private $pdfConfig = [];

    /**
     * @var S3Client
     */
    private $s3Client;

    /**
     * @var SqsClient
     */
    private $sqsClient;

    /**
     * @var array
     */
    private $pdfTypes = [
        'lp1',
        'lp3',
        'lpa120'
    ];

    /**
     * @param $lpaId
     * @param $id
     * @return ApiProblem|ValidationApiProblem|FileResponse|array
     * @throws \Exception
     */
    public function fetch(string $lpaId, $id)
    {
        $lpa = $this->getLpa($lpaId);

        $validation = $lpa->validate();

        if ($validation->hasErrors()) {
            return new ValidationApiProblem($validation);
        }

        if (in_array($id, $this->pdfTypes)) {
            $details = $this->getPdfDetails($lpa, $id);

            if ($details['status'] == self::STATUS_NOT_QUEUED) {
                // Then add the LPA to the PDF queue.
                $this->addLpaToQueue($lpa, $id);

                $details['status'] = self::STATUS_IN_QUEUE;
            }

            // Then assume they're asking for info about a PDF...
            return $details;
        }

        // If they've added '.pdf' onto the end of the type they want to
        // download the file - create array including '.pdf'...
        $typesWithExtention = array_map(function ($v) {
            return "{$v}.pdf";
        }, $this->pdfTypes);

        if (in_array($id, $typesWithExtention)) {
            $type = rtrim($id, '.pdf');

            $file = $this->getPdfFile($lpa, $type);

            if ($file !== false) {
                return new FileResponse($file, FileResponse::TYPE_PDF);
            }
        }

        return new ApiProblem(404, 'Document not found');
    }

    /**
     * @param Lpa $lpa
     * @param $type
     * @return array
     */
    private function getPdfDetails(Lpa $lpa, $type)
    {
        // Check if we can generate this document type.
        switch ($type) {
            case 'lp1':
                $complete = $lpa->canGenerateLP1();
                break;
            case 'lp3':
                $complete = $lpa->canGenerateLP3();
                break;
            case 'lpa120':
                $complete = $lpa->canGenerateLPA120();
                break;
        }

        // If the LPA is complete, we check to see the status...
        if ($complete) {
            $status = $this->getPdfStatus($lpa, $type);
        } else {
            // Otherwise the status is 'not available'...
            $status = self::STATUS_NOT_AVAILABLE;
        }

        return [
            'type' => $type,
            'complete' => $complete,
            'status' => $status,
        ];
    }

    /**
     * This checks the status of the PDF document that represents the LPA (and form type).
     *
     * The result can be:
     * - It's not in the queue (because it has not been added)
     * - It's in the queue ready for processing.
     * - It's ready to be downloaded.
     *
     * @param Lpa $lpa
     * @param $type
     * @return string
     */
    private function getPdfStatus(Lpa $lpa, $type)
    {
        $ident = $this->getPdfIdent($lpa, $type);

        // Check if the file already exists in the cache.
        $bucketConfig = $this->pdfConfig['cache']['s3']['settings'];

        try {
            $this->s3Client->headObject($bucketConfig + [
                'Key' => $ident,
            ]);

            // If we get here it exists in the bucket...
            return self::STATUS_READY;
        } catch (\Aws\S3\Exception\S3Exception $ignore) {
            $this->getLogger()->error('Exception while attempting to get PDF info from S3', [
                'error_code' => 'PDF_S3_HEAD_FAILED',
                'exception' => $ignore,
            ]);
        }

        /*
         * Technically with SQS we have no way of knowing if a PDF is in the queue, but with
         * fifo duplication detection, we can assume it's not and attempt to re-submit it.
         */
        return self::STATUS_NOT_QUEUED;
    }

    /**
     * @param Lpa $lpa
     * @param $type
     *
     * @throws \Exception
     *
     * @return void
     */
    private function addLpaToQueue(Lpa $lpa, $type)
    {
        // Setup the message
        $message = json_encode([
            'lpa' => $lpa->toArray(),
            'type' => strtoupper($type), // The type of document we want generating
        ]);

        // Compress the message - we compress JSON put into the queue with this
        $message = (new Compress('Gz'))->filter($message);

        $jobId = $this->getPdfIdent($lpa, $type);

        if (!isset($this->pdfConfig['queue']['sqs']['settings']['url'])) {
            throw new \Exception('SQS URL not configured');
        }

        // Add the message to the queue
        $this->sqsClient->sendMessage([
            'QueueUrl' => $this->pdfConfig['queue']['sqs']['settings']['url'],
            'MessageBody' => json_encode(
                [
                    'jobId' => $jobId,
                    'lpaId' => $lpa->getId(),
                    'data'  => base64_encode($message),
                ]
            ),
            'MessageGroupId' => $jobId,
            'MessageDeduplicationId' => $jobId,
        ]);
    }

    /**
     * @param Lpa $lpa
     * @param $type
     * @return bool|string
     */
    private function getPdfFile(Lpa $lpa, string $type)
    {
        $bucketConfig = $this->pdfConfig['cache']['s3']['settings'];

        try {
            $file = $this->s3Client->getObject($bucketConfig + [
                'Key' => $this->getPdfIdent($lpa, $type),
            ]);
        } catch (\Aws\S3\Exception\S3Exception $e) {
            return false;
        }

        return $file['Body']->getContents();
    }

    /**
     * Generates a unique identifier the represents the LPA data and type of form ( lp1, lp3, etc. ).
     * This is used as the key for the LPA form in the S3 bucket.
     * The docIdSuffix is a unique identifier for this version of the application,
     * meaning that whenever we update the application, we implicitly flush
     * the cache; any PDFs in the cache for old versions of the app will expire after
     * 24 hours and be cleaned up by the S3 bucket itself.
     *
     * @param Lpa $lpa
     * @param $type
     * @return string
     */
    private function getPdfIdent(Lpa $lpa, string $type)
    {
        $docIdSuffix = '';
        if (isset($this->pdfConfig['docIdSuffix'])) {
            $docIdSuffix = $this->pdfConfig['docIdSuffix'];
        }

        $hash = hash(
            'md5',
            $lpa->toJson() . $docIdSuffix
        );

        return strtolower("{$type}-{$hash}");
    }

    /**
     * Set the PDF config
     *
     * @param array $config
     * @psalm-api
     */
    public function setPdfConfig(array $config): void
    {
        if (isset($config['pdf'])) {
            $this->pdfConfig = $config['pdf'];
        }
    }

    /**
     * @param S3Client $s3Client
     * @psalm-api
     */
    public function setS3Client(S3Client $s3Client): void
    {
        $this->s3Client = $s3Client;
    }

    /**
     * @param SqsClient $sqsClient
     * @psalm-api
     */
    public function setSqsClient(SqsClient $sqsClient): void
    {
        $this->sqsClient = $sqsClient;
    }
}
