<?php

namespace Application\Controller\Version2;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Applications\Collection;
use Application\Model\Service\EntityInterface;
use Zend\Paginator\Paginator;
use ZF\ApiProblem\ApiProblem;

class ApplicationController extends AbstractController
{
    /**
     * @param mixed $id
     * @return JsonResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $result = $this->service->fetch($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            $resultData = $result->toArray();

            if (empty($resultData)) {
                return new NoContentResponse();
            }

            return new JsonResponse($resultData);
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
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
        $result = $this->service->fetchAll($filteredQuery);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result === null) {
            return new NoContentResponse();
        }

        //  The applications collection was a success - it will be a paginator
        /** @var Collection $result */

        //  Set the page number and per page count (if valid)
        $result->setCurrentPageNumber($page);

        if (is_numeric($perPage) && $perPage > 0) {
            $result->setItemCountPerPage($perPage);
        }

        return new JsonResponse($result->toArray());
    }

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
     * @param $id
     * @param $data
     * @return JsonResponse|ApiProblem
     */
    public function patch($id, $data)
    {
        $result = $this->service->patch($data, $id);

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
