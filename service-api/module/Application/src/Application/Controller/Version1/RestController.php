<?php

namespace Application\Controller\Version1;

use RuntimeException;

use Application\Library\Hal;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Rest\AbstractResource;
use Application\Model\Rest\EntityInterface;
use Application\Model\Rest\Lock\LockedException;
use Application\Model\Rest\RouteProviderInterface;
use Zend\Http\Response as HttpResponse;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZfcRbac\Exception\UnauthorizedException;

class RestController extends AbstractRestfulController
{
    /**
     * @var AbstractResource The resource model to use.
     */
    private $resource;

    /**
     * Sets the Resource identified in the URL.
     *
     * @param AbstractResource $resource
     */
    public function setResource(AbstractResource $resource)
    {
        $this->resource = $resource;
        $this->identifierName = $resource->getIdentifier();
    }

    /**
     * @return AbstractResource The Resource current being used.
     */
    public function getResource()
    {
        if (!isset($this->resource) || !($this->resource instanceof AbstractResource)) {
            throw new RuntimeException('A resource has not been set');
        }

        return $this->resource;
    }

    public function onDispatch(MvcEvent $event)
    {
        try {
            $return = parent::onDispatch($event);
        } catch (UnauthorizedException $e) {
            $return = new ApiProblem(401, 'Access Denied');
        } catch (LockedException $e) {
            $return = new ApiProblem(403, 'LPA has been locked');
        }

        if ($return instanceof Hal\Hal) {
            return new Hal\HalResponse($return, 'json');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    /**
     * Retrieve the identifier, if any
     *
     * Attempts to see if an identifier was passed in either the URI or the
     * query string, returning it if found. Otherwise, returns a boolean false.
     *
     * This override ensures a value of TRUE id always
     * returned if the resource is a singular.
     *
     * @param  \Zend\Router\RouteMatch $routeMatch
     * @param  \Zend\Stdlib\RequestInterface $request
     * @return false|mixed
     */
    protected function getIdentifier($routeMatch, $request)
    {
        $resource = $this->getResource();

        // If the resource is a singular,
        if ($resource->getType() == $resource::TYPE_SINGULAR) {
            return true;
        }

        return parent::getIdentifier($routeMatch, $request);
    }

    /**
     * Create a new resource
     *
     * @param  mixed $data
     * @return mixed
     */
    public function create($data)
    {
        if (!is_callable([$this->getResource(), 'create'])) {
            return new ApiProblem(405, 'The POST method has not been defined on this entity');
        }

        $result = $this->getResource()->create($data);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            $hal = new Hal\Entity($result);

            $hal->setLinks([$this, 'generateRoute']);

            $response = new Hal\HalResponse($hal, 'json');
            $response->setStatusCode(201);
            $response->getHeaders()->addHeaderLine('Location', $hal->getUri());

            return $response;
        }

        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Delete an existing resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function delete($id)
    {
        if (!is_callable([$this->getResource(), 'delete'])) {
            return new ApiProblem(405, 'The DELETE method has not been defined');
        }

        $result = @$this->getResource()->delete($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result === true) {
            return new NoContentResponse();
        }

        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Return single resource
     *
     * @param  mixed $id
     * @return mixed
     */
    public function get($id)
    {
        if (!is_callable([$this->getResource(), 'fetch'])) {
            return new ApiProblem(405, 'The GET method has not been defined');
        }

        $result = $this->getResource()->fetch($id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            if (count($result->toArray()) == 0) {
                return new NoContentResponse();
            }

            $hal = new Hal\Entity($result);

            $hal->setLinks([$this, 'generateRoute']);

            $response = new Hal\HalResponse($hal, 'json');

            return $response;
        } elseif ($result instanceof HttpResponse) {
            return $result;
        }

        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Return list of resources
     *
     * @return mixed
     */
    public function getList()
    {
        if (!is_callable([$this->getResource(), 'fetchAll'])) {
            return new ApiProblem(405, 'The GET method has not been defined on this collection');
        }

        $query = $this->params()->fromQuery();

        if (isset($query['page']) && is_numeric($query['page'])) {
            $page = (int)$query['page'];
        } else {
            $page = 1;
        }

        unset($query['page']);

        $collections = $this->getResource()->fetchAll($query);

        if ($collections instanceof ApiProblem) {
            return $collections;
        } elseif ($collections === null) {
            return new NoContentResponse();
        }

        $collections->setCurrentPageNumber($page);

        $hal = new Hal\Collection($collections, $this->getResource()->getName());

        $hal->setLinks([$this, 'generateRoute']);

        return new Hal\HalResponse($hal, 'json');
    }

    /**
     * Respond to the PATCH method
     *
     * Not marked as abstract, as that would introduce a BC break
     * (introduced in 2.1.0); instead, raises an exception if not implemented.
     *
     * @param  $id
     * @param  $data
     * @return mixed
     */
    public function patch($id, $data)
    {
        if (!is_callable([$this->getResource(), 'patch'])) {
            return new ApiProblem(405, 'The PATCH method has not been defined');
        }

        $result = @$this->getResource()->patch($data, $id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            if (count($result->toArray()) == 0) {
                return new NoContentResponse();
            }

            $hal = new Hal\Entity($result);

            $hal->setLinks([$this, 'generateRoute']);

            $response = new Hal\HalResponse($hal, 'json');

            return $response;
        }

        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * Update an existing resource
     *
     * @param  mixed $id
     * @param  mixed $data
     * @return mixed
     */
    public function update($id, $data)
    {
        if (!is_callable([$this->getResource(), 'update'])) {
            return new ApiProblem(405, 'The PUT method has not been defined');
        }

        $result = @$this->getResource()->update($data, $id);

        if ($result instanceof ApiProblem) {
            return $result;
        } elseif ($result instanceof EntityInterface) {
            if (count($result->toArray()) == 0) {
                return new NoContentResponse();
            }

            $hal = new Hal\Entity($result);

            $hal->setLinks([$this, 'generateRoute']);

            $response = new Hal\HalResponse($hal, 'json');

            return $response;
        }

        return new ApiProblem(500, 'Unable to process request');
    }

    /**
     * This function is passed as a callback into anything that needs to be able to generate a route.
     *
     * @param $routeName
     * @param RouteProviderInterface $provider
     * @param array $params
     * @return string
     */
    public function generateRoute($routeName, RouteProviderInterface $provider, $params = array())
    {
        $original = $this->params()->fromQuery();

        unset($original['page']);

        $params = array_merge($original, $params);

        $resource = $this->getResource();

        return $this->url()->fromRoute($routeName, [
            'userId'=>$resource->getRouteUser()->userId(),
            'lpaId'=>$provider->lpaId(),
            'resource' => $resource->getName(),
            'resourceId' => $provider->resourceId()
        ], ['query' => $params]);
    }
}
