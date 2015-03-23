<?php
namespace Opg\Lpa\Pdf\Worker;

class TestWorker extends AbstractWorker {

    /**
     * Return the TestResponse for handling the response.
     *
     * @param $docId
     * @return \Opg\Lpa\Pdf\Service\ResponseInterface
     */
    protected function getResponseObject( $docId ){
        return new Response\TestResponse( $docId );
    }

    public function run( $docId, $type, $lpa ){
        parent::run( $docId, $type, $lpa );
    }

} // class
