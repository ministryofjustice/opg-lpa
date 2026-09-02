<?php

namespace Application\Controller\Version2\Lpa;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Model\Service\InstructionPreference\Service;

class InstructionPreferenceController extends AbstractLpaController
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

        $result = $this->getService()->update($this->params()->fromRoute('lpaId'), $data);

        if ($result instanceof ApiProblem) {
            return $result;
        }

        [$instruction, $preference] = $result;
        return new JsonResponse([$instruction->toArray(), $preference->toArray()]);
    }
}
