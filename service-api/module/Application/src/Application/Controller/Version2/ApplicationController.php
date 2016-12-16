<?php

namespace Application\Controller\Version2;

use Zend\Mvc\MvcEvent;

use Zend\Mvc\Controller\AbstractRestfulController;

use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\CollectionInterface;

use Application\Model\Rest\Lock\LockedException;

use Zend\Http\Response as HttpResponse;

use Application\Library\Http\Response\NoContent as NoContentResponse;

use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

use Application\Library\Hal\Hal;
use Application\Library\Hal\HalResponse;


use ZfcRbac\Exception\UnauthorizedException;

class ApplicationController extends AbstractRestfulController
{
    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'lpaId';

    /**
     * Get the applications resource
     *
     * @return array|object
     */
    private function getResource()
    {
        return $this->getServiceLocator()->get('resource-applications');
    }

    /**
     * Execute the request
     *
     * @param MvcEvent $event
     * @return HalResponse|mixed|ApiProblem|ApiProblemResponse
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

        if ($return instanceof Hal) {
            return new HalResponse($return, 'json');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    /**
     * Get a list of applications
     *
     * @return HalResponse|NoContentResponse
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

        //  Create the filtered query by excluding the page query data
        $filteredQuery = $query;
        unset($filteredQuery['page']);
        unset($filteredQuery['perPage']);

        //  Get the collection (paginator) of applications with the query data
        $collections = $this->getResource()->fetchAll($filteredQuery);

        if ($collections instanceof ApiProblem) {
            return $collections;
        } elseif ($collections === null) {
            return new NoContentResponse();
        }

        //  Set the page number and per page count (if valid)
        $collections->setCurrentPageNumber($page);

        if (is_numeric($perPage) && $perPage > 0) {
            $collections->setItemCountPerPage($perPage);
        }

        $hal = $this->generateHalCollection($collections, $query);

        return new HalResponse($hal, 'json');
    }

    /**
     * Get a specific application by ID
     *
     * @param int $id
     * @return HalResponse|NoContentResponse|ApiProblem
     */
    public function get($id)
    {
        $result = $this->getResource()->fetch($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            if (count($result->toArray()) == 0) {
                return new NoContentResponse();
            }

            $hal = $this->generateHal($result);

            return new HalResponse($hal, 'json');
        } elseif ($result instanceof HttpResponse) {
            return $result;
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Create an application using the provided data
     *
     * @param mixed $data
     * @return HalResponse|ApiProblem
     */
    public function create($data)
    {
        $result = $this->getResource()->create($data);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            $hal = $this->generateHal($result);

            $response = new HalResponse($hal, 'json');
            $response->setStatusCode(201);
            $response->getHeaders()->addHeaderLine('Location', $hal->getUri());

            return $response;
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
        $result = $this->getResource()->delete($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result === true) {
            return new NoContentResponse();
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Patch (update) the data for a specific application
     *
     * @param mixed $id
     * @return NoContentResponse|ApiProblem
     */
    public function patch($id, $data)
    {
        $result = $this->getResource()->patch($data, $id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            $hal = $this->generateHal($result);

            $response = new HalResponse($hal, 'json');
            $response->setStatusCode(200);

            return $response;
        }

        // If we get here...
        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Generates a Hal object for a single LPA Application
     *
     * @param EntityInterface $entity
     * @return Hal
     */
    private function generateHal(EntityInterface $entity)
    {
        //  Create the Hal object with a link to the application and the entity data
        $halSelfUri = $this->getApplicationsLink([], $entity->lpaId());

        $hal = new Hal($halSelfUri, $entity->toArray());

        //  Add a user link to the Hal
        $this->addUserLinkToHal($hal);

        return $hal;
    }

    /**
     * Create a link URI to the applications page, using any query params provided
     * If a specific lpaId is provided then use that to create a link to the specific application
     *
     * @param array $queryParams
     * @param int $lpaId
     * @return string
     */
    private function getApplicationsLink($queryParams = [], $lpaId = null)
    {
        $routeParams = [
            'userId' => $this->getResource()->getRouteUser()->userId(),
        ];

        //  If an LPA Id has been provided then add it to the params so that the link points to the specific LPA
        if (!is_null($lpaId)) {
            $routeParams['lpaId'] = $lpaId;
        }

        //  Set the query params in the options
        $options = [
            'query' => $queryParams,
        ];

        return $this->url()->fromRoute('api-v2/user/applications', $routeParams, $options);
    }

    /**
     * Add a user link to a Hal object
     *
     * @param Hal $hal
     */
    private function addUserLinkToHal(Hal $hal)
    {
        $routeParams = [
            'userId' => $this->getResource()->getRouteUser()->userId(),
        ];

        $userLink = $this->url()->fromRoute('api-v2/user', $routeParams);

        $hal->addLink('user', $userLink);
    }

    /**
     * Generates a Hal object for a collection of LPAs
     *
     * @param CollectionInterface $collection
     * @param array $query
     * @return Hal
     */
    private function generateHalCollection(CollectionInterface $collection, array $query)
    {
        //  Get the collection data without the application data so we can use it in the constructor
        //  The applications will be added later
        $collectionData = $collection->toArray();
        $collectionItems = $collectionData['items'];
        unset($collectionData['items']);

        //  Create the Hal object for the collection data using an appropriate self link
        $hal = new Hal($this->getApplicationsLink($query), $collectionData);

        //  Add the resources - generate a Hal object for each one
        foreach ($collectionItems as $collectionItem) {
            $hal->addResource($this->getResource()->getName(), $this->generateHal($collectionItem));
        }

        //  Add a user link to the Hal
        $this->addUserLinkToHal($hal);

        return $hal;
    }
}
