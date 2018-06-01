<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Stats\Service;
use ZF\ApiProblem\ApiProblem;

class StatsController extends AbstractController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'type';

    /**
     * @param Service $service
     */
    public function __construct(Service $service)
    {
        $this->service = $service;
    }

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
        $result = $this->getService()->fetch($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif (is_array($result)) {
            if (empty($result)) {
                return new NoContentResponse();
            }

            return new JsonResponse($result);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
