<?php

namespace Opg\Lpa\Pdf\Worker;

class TestWorker extends AbstractWorker
{
    /**
     * Return the object for handling the response
     *
     * @param string $docId
     * @return Response\TestResponse
     */
    protected function getResponseObject(string $docId): Response\TestResponse
    {
        return new Response\TestResponse($docId);
    }
}
