<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\File as FileResponse;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\EntityInterface;
use ZF\ApiProblem\ApiProblem;

class PdfController extends AbstractController
{
    /**
     * @var string
     */
    protected $identifierName = 'pdfType';

    /**
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $result = $this->resource->fetch($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            $resultData = $result->toArray();

            if (empty($resultData)) {
                return new NoContentResponse();
            }

            return new JsonResponse($resultData);
        } elseif ($result instanceof FileResponse) {
            //  Just return the file if it present
            return $result;
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
