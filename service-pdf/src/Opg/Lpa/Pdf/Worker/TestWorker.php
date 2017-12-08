<?php

namespace Opg\Lpa\Pdf\Worker;

class TestWorker extends AbstractWorker
{
    /**
     * Return the object for handling the response
     *
     * @param $docId
     * @return Response\TestResponse
     */
    protected function getResponseObject($docId)
    {
        return new Response\TestResponse($docId);
    }
}
