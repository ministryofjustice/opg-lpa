<?php

namespace Opg\Lpa\Pdf\Worker;

use SplFileInfo;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\ResponseInterface;

class Response implements ResponseInterface  {

    public function __construct( Config $config, $docId ) {

    }

    /**
     * Store the file on the passed path for retrieval by the API service.
     *
     * @param $pathToFile
     */
    public function send( SplFileInfo $pathToFile ){



    } // function

} // interface
