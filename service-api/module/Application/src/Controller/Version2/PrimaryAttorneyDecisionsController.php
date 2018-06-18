<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Model\Service\AttorneyDecisionsPrimary\Service;
use Application\Model\Service\EntityInterface;
use ZF\ApiProblem\ApiProblem;

class PrimaryAttorneyDecisionsController extends AbstractController
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
     * @param mixed $id
     * @param mixed $data
     * @return JsonResponse|ApiProblem
     */
    public function update($id, $data)
    {
        $this->checkAccess();

        $result = $this->getService()->update($this->lpaId, $data);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            return new JsonResponse($result->toArray());
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
