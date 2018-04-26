<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\EntityInterface;
use ZF\ApiProblem\ApiProblem;

class NotifiedPeopleController extends AbstractController
{
    /**
     * @var string
     */
    protected $identifierName = 'notifiedPersonId';

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

    /**
     * @param mixed $id
     * @param mixed $data
     * @return JsonResponse|ApiProblem
     */
    public function update($id, $data)
    {
        $result = $this->service->update($data, $id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            return new JsonResponse($result->toArray());
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * @param mixed $id
     * @return NoContentResponse|ApiProblem
     */
    public function delete($id)
    {
        $result = $this->service->delete($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result === true) {
            return new NoContentResponse();
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
