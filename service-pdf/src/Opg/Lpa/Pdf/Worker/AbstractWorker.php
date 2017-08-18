<?php

namespace Opg\Lpa\Pdf\Worker;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\Generator;
use Opg\Lpa\Pdf\Logger\Logger;
use Opg\Lpa\Pdf\Worker\Response\AbstractResponse;
use Exception;

abstract class AbstractWorker
{
    /**
     * Logger utility
     *
     * @var Logger
     */
    protected $logger;

    public function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    /**
     * @param string $docId Unique ID representing this job/document.
     * @param string $type The type of PDF to generate.
     * @param string $lpa JSON document representing the LPA document.
     * @throws Exception
     */
    public function run($docId, $type, $lpa)
    {
        $lpaId = null;

        //  Initialise the log message params
        $message = "${docId}: PDF successfully generated";
        $isError = false;

        try {
            if (is_array($lpa) && isset($lpa['id'])) {
                $lpaId = $lpa['id'];
            } else {
                $lpaDecode = json_decode($lpa);

                if (property_exists($lpaDecode, 'id')) {
                    $lpaId = $lpaDecode->id;
                } else {
                    throw new Exception('Missing field: id in JSON for docId: ' . $docId . ' This can be caused by incorrectly configured encryption keys.');
                }
            }

            // Instantiate an LPA document from the JSON
            $lpaObj = new Lpa($lpa);

            // Create and config the $response object
            $response = $this->getResponseObject($docId);

            $this->logger->info("Creating generator", [
                'lpaId' => $lpaId
            ]);

            // Create an instance of the PDF generator service.
            $generator = new Generator($type, $lpaObj, $response);


            $this->logger->info("${docId}: Generating PDF\n", [
                'lpaId' => $lpaId
            ]);

            // Run the process.
            $result = $generator->generate();

            // Check the result...
            if ($result !== true) {
                // Else there was an error.
                throw new Exception($result);
            }
        } catch (Exception $e) {
            $isError = true;
            $message = "${docId}: PDF generation failed with exception: " . $e->getMessage();
        }

        if ($isError) {
            $this->logger->err($message, [
                'lpaId' => $lpaId
            ]);
        } else {
            $this->logger->info($message, [
                'lpaId' => $lpaId
            ]);
        }

        echo $message . "\n";
    }

    /**
     * Return the object for handling the response
     *
     * @param $docId
     * @return AbstractResponse
     */
    abstract protected function getResponseObject($docId);
}