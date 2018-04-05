<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\Applications\Resource;
use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\Lock\LockedException;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use Zend\Paginator\Paginator;
use ZfcRbac\Exception\UnauthorizedException;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class ApplicationController extends AbstractRestfulController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'lpaId';

    /**
     * @var Resource
     */
    private $applicationsResource;

    /**
     * ApplicationController constructor
     * @param Resource $applicationsResource
     */
    public function __construct(Resource $applicationsResource)
    {
        $this->applicationsResource = $applicationsResource;
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
        } catch (LockedException $e) {
            $return = new ApiProblem(403, 'LPA has been locked');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    /**
     * Get a specific application by ID
     *
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $application = $this->applicationsResource->fetch($id);

        if ($application instanceof ApiProblem) {
            return $application;
        } elseif ($application instanceof EntityInterface) {
            $applicationData = $application->toArray();

            if (empty($applicationData)) {
                return new NoContentResponse();
            }

            return new JsonResponse($applicationData);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Get a list of applications
     *
     * @return JsonResponse|NoContentResponse|Paginator
     */
    public function getList()
    {
        $query = $this->params()->fromQuery();

        //  If appropriate numeric values have been provided then get the correct page
        $page = (isset($query['page']) ? $query['page'] : null);
        $perPage = (isset($query['perPage']) ? $query['perPage'] : null);

        //  If the page param is invalid then just get the first page
        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }

        //  Create the filtered query by excluding the page query data - this should just leave the search parameter
        $filteredQuery = $query;
        unset($filteredQuery['page']);
        unset($filteredQuery['perPage']);

        //  Get the collection of applications with the query data
        $applications = $this->applicationsResource->fetchAll($filteredQuery);

        if ($applications instanceof ApiProblem) {
            return $applications;
        } elseif ($applications === null) {
            return new NoContentResponse();
        }

        //  The applications collection was a success - it will be a paginator
        /** @var Paginator $applications */

        //  Set the page number and per page count (if valid)
        $applications->setCurrentPageNumber($page);

        if (is_numeric($perPage) && $perPage > 0) {
            $applications->setItemCountPerPage($perPage);
        }

        return new JsonResponse($applications->toArray());
    }

    /**
     * Create an application using the provided data
     *
     * @param mixed $data
     * @return JsonResponse|ApiProblem
     */
    public function create($data)
    {
        $application = $this->applicationsResource->create($data);

        if ($application instanceof ApiProblem) {
            return $application;
        } elseif ($application instanceof EntityInterface) {
            return new JsonResponse($application->toArray(), 201);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Delete a specific application by ID
     *
     * @param mixed $id
     * @return NoContentResponse|ApiProblem
     */
    public function delete($id)
    {
        $application = $this->applicationsResource->delete($id);

        if ($application instanceof ApiProblem) {
            return $application;
        } elseif ($application === true) {
            return new NoContentResponse();
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Patch (update) the data for a specific application
     *
     * @param $id
     * @param $data
     * @return JsonResponse|ApiProblem
     */
    public function patch($id, $data)
    {
        $application = $this->applicationsResource->patch($data, $id);

        if ($application instanceof ApiProblem) {
            return $application;
        } elseif ($application instanceof EntityInterface) {
            return new JsonResponse($application->toArray());
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
