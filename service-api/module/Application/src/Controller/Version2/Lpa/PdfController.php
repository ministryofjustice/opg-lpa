<?php

namespace Application\Controller\Version2\Lpa;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\File as FileResponse;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Pdfs\Service;
use Laminas\Http\Request;

class PdfController extends AbstractLpaController
{
    /**
     * @var string
     */
    protected $identifierName = 'pdfType';

    /**
     * Get the service to use
     *
     * @return Service
     */
    protected function getService()
    {
        return $this->service;
    }

    /**
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem|FileResponse
     */
    public function get($id)
    {
        $this->checkAccess();
        $request = $this->getRequest();
        $traceId = $request instanceof Request ? $request->getHeader('X-Trace-Id')->getFieldValue() : '';

        $result = $this->getService()->fetch($this->lpaId, $id, $traceId);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif (is_array($result)) {
            if (empty($result)) {
                return new NoContentResponse();
            }

            return new JsonResponse($result);
        } elseif ($result instanceof FileResponse) {
            //  Just return the file if it present
            return $result;
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
