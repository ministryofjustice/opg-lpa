<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\EntityInterface;
use ZF\ApiProblem\ApiProblem;

class UserController extends AbstractController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'userId';

    /**
     * Get a specific user by ID
     *
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $user = $this->resource->fetch($id);

        if ($user instanceof ApiProblem) {
            return $user;
        } elseif ($user instanceof EntityInterface) {
            $userData = $user->toArray();

            if (empty($userData)) {
                return new NoContentResponse();
            }

            return new JsonResponse($userData);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Delete a specific user by ID
     *
     * @param mixed $id
     * @return NoContentResponse|ApiProblem
     */
    public function delete($id)
    {
        $result = $this->resource->delete($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result === true) {
            return new NoContentResponse();
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Put (update) the data for a specific application
     *
     * @param $id
     * @param $data
     * @return JsonResponse|ApiProblem
     */
    public function update($id, $data)
    {
        $user = $this->resource->update($data, $id);

        if ($user instanceof ApiProblem) {
            return $user;
        } elseif ($user instanceof EntityInterface) {
            return new JsonResponse($user->toArray());
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
