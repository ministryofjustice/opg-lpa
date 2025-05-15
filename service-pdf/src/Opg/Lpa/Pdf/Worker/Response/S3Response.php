<?php

namespace Opg\Lpa\Pdf\Worker\Response;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use MakeShared\Logging\LoggerTrait;
use Opg\Lpa\Pdf\Config\Config;

/**
 * Stores the generated PDF into Amazon S3
 *
 * Files will be automatically deleted after a period by the bucket's lifecycle policy
 */
class S3Response
{
    use LoggerTrait;

    /** @var string */
    private $docId;

    /** @var Config */
    private $config;

    /**
     * Constructor
     *
     * @param $docId
     */
    public function __construct($docId)
    {
        $this->docId = $docId;
        $this->config = Config::getInstance();
    }

    /**
     * Store the file on the passed path for retrieval by the API service.
     *
     * @param string $filecontents
     * @throws S3Exception
     */
    public function save(string $fileContents)
    {
        // Create the S3 client
        $workerConfig = $this->config['worker']['s3Response'];
        $workerSettingsConfig = $workerConfig['settings'];
        $s3 = new S3Client($workerConfig['client']);

        try {
            // Put the file to S3
            $file = $workerSettingsConfig + [
                'Key' => (string)$this->docId,
                'Body' => $fileContents,
            ];

            $s3->putObject($file);
        } catch (S3Exception $e) {
            $this->getLogger()->emergency('ERROR: Failed to save to S3 in ' . $workerSettingsConfig['Bucket']);
            throw $e;
        }

        $this->getLogger()->info('Saved to S3 in ' . $workerSettingsConfig['Bucket']);
    }
}
