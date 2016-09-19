<?php
namespace Opg\Lpa\Api\Client\Response;

class Error implements ErrorInterface {

    private $details;

    public function __construct( $details ){
        $this->details = $details;
    }

    /**
     * @return null|string
     */
    public function getDetail(){
        return $this->details;
    }

}