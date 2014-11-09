<?php

namespace Opg\Lpa\Pdf\Worker;

use Exception;
use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\Generator;

class Worker {

    public function run( $docId, $type, $lpa ){

        echo "${docId}: Generating PDF\n";

        try {

            // Get the service config.
            $config = new Config(include('config/local.php'));

            // Create and config the $response object.
            $response = new Response( $config, $docId );

            // Create an instance of the PDF generator service.
            $generator = new Generator($config, $type, $lpa, $response);

            // Run the process.
            $result = $generator->generate();

            $result = true;

            // Check and deal with the result.
            if ($result === true) {
                // All is good.
                echo "${docId}: PDF successfully generated\n";
                return;
            }

            //------------------------
            // There was ane error

            echo "${docId}: PDF generation failed: $result\n";

        } catch (Exception $e){

            echo "${docId}: PDF generation failed with exception: ", $e->getMessage(),"\n";

        }

    } // function

} // class
