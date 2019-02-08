<?php

namespace Application\Controller;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\DateTime;
use Application\Library\Http\Response\Json;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\Service\Applications\Service;
use Exception;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblemResponse;
use ZfcRbac\Service\AuthorizationService;

class StatusController extends AbstractRestfulController
{
    /**
     * @var string
     */
    protected $identifierName = 'lpaIds';

    /**
     * @var AuthorizationService AuthorizationService
     */
    private $service;

    /**
     * @var $authorizationService Service
     */
    private $authorizationService;

    /**
     * @var $processingStatusService ProcessingStatusService
     */
    private $processingStatusService;

    /**
     * @var $config array
     */
    private $config;

    /**
     * @var $routeUserId string
     */
    private $routeUserId;

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
     * @param AuthorizationService $authorizationService
     * @param Service $service
     * @param ProcessingStatusService $processingStatusService
     * @param array $config
     */
    public function __construct(
        AuthorizationService $authorizationService,
        Service $service,
        ProcessingStatusService $processingStatusService,
        array $config
    ) {
        $this->authorizationService = $authorizationService;
        $this->service = $service;
        $this->processingStatusService = $processingStatusService;
        $this->config = $config;
    }

    /**
     * @param MvcEvent $event
     * @return mixed|ApiProblemResponse
     * @throws ApiProblemException
     */
    public function onDispatch(MvcEvent $event)
    {
        //  If possible get the user and LPA from the ID values in the route
        $this->routeUserId = $event->getRouteMatch()->getParam('userId');

        if (empty($this->routeUserId)) {
            //  userId MUST be present in the URL
            throw new ApiProblemException('User identifier missing from URL', 400);
        }

        if (!$this->authorizationService->isGranted('authenticated')) {
            throw new UnauthorizedException('You need to be authenticated to access this service');
        }

        try {
            return parent::onDispatch($event);
        } catch (UnauthorizedException $e) {
            return new ApiProblemResponse(new ApiProblem(401, 'Access Denied'));
        } catch (LockedException $e) {
            return new ApiProblemResponse(new ApiProblem(403, 'LPA has been locked'));
        }
    }

    /**
     * Checks whether we have a status from Sirius for a list of applications
     *
     * Returns an array of results, one for each application. Will return {"found": false} for an application if it is
     * not found, does not have a processing status, or does not belong to this user. Otherwise returns
     * {"found": true, "status": "<processing status>"}
     *
     * @param string $ids
     * @return Json
     * @throws Exception
     */
    public function get($ids)
    {
        if (empty($this->routeUserId)) {
            //  userId MUST be present in the URL
            throw new ApiProblemException('User identifier missing from URL', 400);
        }

        $lpasTrackableFrom = new DateTime($this->config['track-from-date']);
        $exploded_ids = explode(',', $ids);
        $results =  [];

        foreach ($exploded_ids as $id) {
            $result = $this->getService()->fetch($id, $this->routeUserId);

            // if the id isn't found, return false.
            if ($result instanceof ApiProblem) {
                $results[$id] = ['found'=>false];
                continue;
            }

            /** @var Lpa $lpa */
            $lpa = $result->getData();

            // if the LPA was made before lpasTrackableFrom return default status.
            if ($lpa->getCompletedAt() < $lpasTrackableFrom) {
                $results[$id] = ['found'=>false];
                continue;
            }

            $metaData = $lpa->getMetaData();

            // If application has already reached the last stage of processing ('Concluded') do not check for updates
            if ($metaData[LPA::PROCESSING_STATUS] == 'Concluded') {
                $results[$id] = ['found'=>true, 'status'=>'Concluded'];
                continue;
            }

            $result = $this->processingStatusService->getStatus($id);

            if($result != null && $result != $metaData[LPA::PROCESSING_STATUS]) {

                // Update metadata in DB
                $metaData[LPA::PROCESSING_STATUS] = $result;

                $this->getService()->patch(['metadata' => $metaData], $id, $this->routeUserId);
            }

            $results[$id] = ['found'=>true, 'status'=>$metaData[LPA::PROCESSING_STATUS]];
        }
        
        return new Json($results);
    }
}