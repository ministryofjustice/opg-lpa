<?php

namespace Application\Controller\Version2\Lpa;

use Application\Library\Http\Response\File as FileResponse;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Pdfs\Service;
use ZF\ApiProblem\ApiProblem;

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
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $this->checkAccess();

        $result = $this->getService()->fetch($this->lpaId, $id);

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
