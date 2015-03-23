<?php

namespace Opg\Lpa\Pdf\Worker\Response;

use SplFileInfo;

use Opg\Lpa\Pdf\Config\Config;
use Opg\Lpa\Pdf\Service\ResponseInterface;

class TestResponse implements ResponseInterface  {

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
        
        if( !\file_exists($this->config['worker']['testResponse']['path']) ) {
            mkdir( $this->config['worker']['testResponse']['path'], 0777, true );
        }

        $path = realpath($this->config['worker']['testResponse']['path'])."/{$this->docId}.pdf";
        
        if(\file_exists($pathToFile->getPathname())) {

            copy( $pathToFile->getPathname(), $path );
    
            echo "{$this->docId}: File saved to {$path}\n";
        }

    } // function

} // interface
