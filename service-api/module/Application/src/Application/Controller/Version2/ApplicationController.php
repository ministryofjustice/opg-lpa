<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\EntityInterface;
use Zend\Paginator\Paginator;
use ZF\ApiProblem\ApiProblem;

class ApplicationController extends AbstractController
{
    /**
     * Get a specific application by ID
     *
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $application = $this->resource->fetch($id);

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
        $applications = $this->resource->fetchAll($filteredQuery);

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
        $application = $this->resource->create($data);

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
        $application = $this->resource->delete($id);

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
        $application = $this->resource->patch($data, $id);

        if ($application instanceof ApiProblem) {
            return $application;
        } elseif ($application instanceof EntityInterface) {
            return new JsonResponse($application->toArray());
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }
}
