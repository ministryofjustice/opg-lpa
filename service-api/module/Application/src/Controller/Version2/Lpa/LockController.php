<?php

namespace Application\Controller\Version2\Lpa;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Model\Service\EntityInterface;
use Application\Model\Service\Lock\Service;
use ZF\ApiProblem\ApiProblem;

class LockController extends AbstractLpaController
{
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
     * @param mixed $data
     * @return JsonResponse|ApiProblem
     */
    public function create($data)
    {
        $this->checkAccess();

        $result = $this->getService()->create($this->lpaId);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            return new JsonResponse($result->toArray(), 201);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
