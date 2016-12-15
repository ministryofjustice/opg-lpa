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
    protected $identifierName = 'lpaId';

    private function getResource()
    {
        return $this->getServiceLocator()->get('resource-applications');
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

        if ($return instanceof Hal) {
            return new HalResponse($return, 'json');
        }

        if ($return instanceof ApiProblem) {
            return new ApiProblemResponse($return);
        }

        return $return;
    }

    public function getList()
    {
        $query = $this->params()->fromQuery();

        $page = 1;

        if (isset($query['page']) && is_numeric($query['page'])) {
            $page = (int) $query['page'];
        }

        unset($query['page']);

        $collections = $this->getResource()->fetchAll($query);

        if ($collections instanceof ApiProblem) {
            return $collections;
        } elseif ($collections === null) {
            return new NoContentResponse();
        }

        $collections->setCurrentPageNumber($page);

        $hal = $this->generateHalCollection($collections);

        return new HalResponse($hal, 'json');
    }

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

        $hal = new Hal(
            // Set 'self'
            $this->url()->fromRoute('api-v2/user/applications', [
                'userId'=>$this->getResource()->getRouteUser()->userId(),
                'lpaId'=>$entity->lpaId(),
            ]),
            // Set the data
            $entity->toArray()
        );

        // Added 'user' link
        $hal->addLink('user', $this->url()->fromRoute('api-v2/user', [
            'userId'=>$this->getResource()->getRouteUser()->userId(),
        ]));

        return $hal;
    }

    /**
     * Generates a Hal object for a collection of LPAs.
     *
     * @param CollectionInterface $collection
     * @return Hal
     */
    private function generateHalCollection(CollectionInterface $collection)
    {
        $currentPage = $collection->getCurrentPageNumber();

        $query = $this->params()->fromQuery();
        unset($query['page']);

        $data = $collection->toArray();

        $items = $data['items'];

        unset($data['items']);

        $hal = new Hal(
            // Set 'self'
            $this->url()->fromRoute(
                'api-v2/user/applications',
                [
                    'userId'=>$this->getResource()->getRouteUser()->userId(),
                ],
                [
                    'query' => $query
                ]
            ),
            $data
        );

        // Add the resources...
        foreach ($items as $item) {
            $hal->addResource($this->getResource()->getName(), $this->generateHal($item));
        }

        // Add the link to 'user'
        $hal->addLink('user', $this->url()->fromRoute('api-v2/user', [
            'userId'=>$this->getResource()->getRouteUser()->userId(),
        ]));

        // Add pagination links
        // First
        $hal->addLink(
            'first',
            $this->url()->fromRoute(
                'api-v2/user/applications',
                [
                    'userId'=>$this->getResource()->getRouteUser()->userId(),
                ],
                [
                    'query' => $query
                ]
            )
        );

        // Self

        if ($currentPage != 1) {
            // Override 'self' to include the page number if we're not on the first page
            $hal->addLink('self', $this->url()->fromRoute(
                'api-v2/user/applications',
                [
                    'userId'=>$this->getResource()->getRouteUser()->userId(),
                ],
                [
                    'query' => array_merge($query, [ 'page'=> $currentPage ])
                ]
            ));
        }

        // Previous

        if ($currentPage - 1 > 0) {
            $page = ($currentPage - 1 != 1) ? [ 'page' => $currentPage - 1 ] : array();

            $hal->addLink('prev', $this->url()->fromRoute(
                'api-v2/user/applications',
                [
                    'userId'=>$this->getResource()->getRouteUser()->userId(),
                ],
                [
                    'query' => array_merge($query, $page)
                ]
            ));
        }

        // Next
        if ($currentPage + 1 <= $collection->count()) {
            $hal->addLink('next', $this->url()->fromRoute(
                'api-v2/user/applications',
                [
                    'userId'=>$this->getResource()->getRouteUser()->userId(),
                ],
                [
                    'query' => array_merge($query, [ 'page'=> $currentPage + 1 ])
                ]
            ));
        }

        // Last
        $page = ($collection->count() > 1) ? [ 'page'=> $collection->count() ] : array();

        $hal->addLink(
            'last',
            $this->url()->fromRoute(
                'api-v2/user/applications',
                [
                    'userId'=>$this->getResource()->getRouteUser()->userId(),
                ],
                [
                    'query' => array_merge($query, $page)
                ]
            )
        );

        return $hal;
    }
}
