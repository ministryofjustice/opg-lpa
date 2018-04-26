<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Model\Service\EntityInterface;
use ZF\ApiProblem\ApiProblem;

class WhoAreYouController extends AbstractController
{
    /**
     * @param mixed $data
     * @return JsonResponse|ApiProblem
     */
    public function create($data)
    {
        $result = $this->service->create($data);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            return new JsonResponse($result->toArray(), 201);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
