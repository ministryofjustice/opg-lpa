<?php

namespace Opg\Lpa\Pdf\Worker;

use Exception;

use Opg\Lpa\DataModel\Lpa\Lpa;
use Opg\Lpa\Pdf\Service\Generator;

class Worker {

    /**
     * @param string $docId Unique ID representing this job/document.
     * @param string $type The type of PDF to generate.
     * @param string $lpa JSON document representing the LPA document.
     */
    public function run( $docId, $type, $lpa ){

        echo "${docId}: Generating PDF\n";

        try {

            // Instantiate an LPA document from the JSON
            $lpaObj = new Lpa( $lpa );

            // Create and config the $response object.
            $response = new RedisResponse( $docId );

            // Create an instance of the PDF generator service.
            $generator = new Generator( $type, $lpaObj, $response );

            // Run the process.
            $result = $generator->generate();

            // Check the result...
            if ($result === true) {

                // All is good.
                echo "${docId}: PDF successfully generated\n";

            } else {

                // Else there was an error.
                echo "${docId}: PDF generation failed: $result\n";

            }

        } catch (Exception $e){

            echo "${docId}: PDF generation failed with exception: ", $e->getMessage(),"\n";

        }

    } // function

} // class
