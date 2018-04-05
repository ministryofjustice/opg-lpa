<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\Users\Resource;
use Application\Model\Rest\EntityInterface;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use ZfcRbac\Exception\UnauthorizedException;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class UserController extends AbstractRestfulController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'userId';

    /**
     * @var Resource
     */
    private $usersResource;

    /**
     * UserController constructor
     * @param Resource $usersResource
     */
    public function __construct(Resource $usersResource)
    {
        $this->usersResource = $usersResource;
    }

    /**
     * Execute the request
     *
     * @param MvcEvent $event
     * @return mixed|ApiProblem|ApiProblemResponse
     */
    public function onDispatch(MvcEvent $event)
    {
        try {
            $return = parent::onDispatch($event);
        } catch (UnauthorizedException $e) {
            $return = new ApiProblem(401, 'Access Denied');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    /**
     * Get a specific user by ID
     *
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $user = $this->usersResource->fetch($id);

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
        $result = $this->usersResource->delete($id);

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
        $user = $this->usersResource->update($data, $id);

        if ($user instanceof ApiProblem) {
            return $user;
        } elseif ($user instanceof EntityInterface) {
            return new JsonResponse($user->toArray());
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
