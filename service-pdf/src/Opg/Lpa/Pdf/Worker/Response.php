<?php

namespace Opg\Lpa\Pdf\Worker;

use SplFileInfo;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\ResponseInterface;

class Response implements ResponseInterface  {

    private $docId;
    private $config;

    public function __construct( $docId ) {

        $this->docId = $docId;
        
        // load config/local.php by default 
        $this->config = Config::getInstance( );

    }

    /**
     * Store the file on the passed path for retrieval by the API service.
     *
     * @param $pathToFile
     */
    public function save( SplFileInfo $pathToFile ){

        echo "{$this->docId}: Response received: ".$pathToFile->getRealPath()."\n";

    } // function

} // interface
