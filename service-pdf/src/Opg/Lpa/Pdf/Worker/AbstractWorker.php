<?php

namespace Opg\Lpa\Pdf\Worker;

use Exception;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\Generator;
use Opg\Lpa\Pdf\Logger\Logger;

abstract class AbstractWorker {

    /**
     * Return the object for handling the response.
     *
     * @param $docId
     * @return \Opg\Lpa\Pdf\Service\ResponseInterface
     */
    abstract protected function getResponseObject( $docId );

    /**
     * @param string $docId Unique ID representing this job/document.
     * @param string $type The type of PDF to generate.
     * @param string $lpa JSON document representing the LPA document.
     */
    public function run( $docId, $type, $lpa ){

        Logger::getInstance()->info("${docId}: Generating PDF\n", ['lpaId' => $lpa->id]);
        
        try {

            Logger::getInstance()->info("Creating LPA document from JSON", ['lpaId' => $lpa->id]);
            
            // Instantiate an LPA document from the JSON
            $lpaObj = new Lpa( $lpa );
            
            // Create and config the $response object.
            $response = $this->getResponseObject( $docId );

            Logger::getInstance()->info("Creating generator", ['lpaId' => $lpa->id]);
            
            // Create an instance of the PDF generator service.
            $generator = new Generator( $type, $lpaObj, $response );

            Logger::getInstance()->info("Starting PDF generation", ['lpaId' => $lpa->id]);
            
            // Run the process.
            $result = $generator->generate();

            // Check the result...
            if ($result === true) {

                // All is good.
                $this->logAndShow($lpa, "${docId}: PDF successfully generated");

            } else {

                // Else there was an error.
                $this->logAndShow($lpa, "${docId}: PDF generation failed: $result");

            }

        } catch (Exception $e){

            $this->logAndShow($lpa, "${docId}: PDF generation failed with exception: " . $e->getMessage());
            
        }

    } // function
    
    private function logAndShow($lpa, $message) {
        
        Logger::getInstance()->info($message, ['lpaId' => $lpa->id]);
        
        echo $message . "\n";
        
    }

} // class
