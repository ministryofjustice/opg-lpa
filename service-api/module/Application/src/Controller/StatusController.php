<?php

namespace Application\Controller;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\Http\Response\Json;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\Service\Applications\Service;
use Exception;
use Opg\Lpa\DataModel\Lpa\Lpa;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Opg\Lpa\Logger\LoggerTrait;
use Zend\Mvc\Controller\AbstractRestfulController;
use Zend\Mvc\MvcEvent;
use ZF\ApiProblem\ApiProblemResponse;
use ZfcRbac\Service\AuthorizationService;

class StatusController extends AbstractRestfulController
{
    use LoggerTrait;

    /**
     * Name of the identifier used in the routes to this RESTful controller
     *
     * @var string
     */
    protected $identifierName = 'lpaIds';

    /**
     * @var $service Service
     */
    private $service;

    /**
     * @var $authorizationService AuthorizationService
     */
    private $authorizationService;

    /**
     * @var $processingStatusService ProcessingStatusService
     */
    private $processingStatusService;

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
     */
    public function __construct(
        AuthorizationService $authorizationService,
        Service $service,
        ProcessingStatusService $processingStatusService
    ) {
        $this->authorizationService = $authorizationService;
        $this->service = $service;
        $this->processingStatusService = $processingStatusService;
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



    public function getCurrentProcessingStatus($id)
    {
        $lpaResult = $this->getService()->fetch($id, $this->routeUserId);

        if ($lpaResult instanceof ApiProblem) {
            $this->getLogger()->err('Error accessing LPA data: ' . $lpaResult->getDetail());
            return $lpaResult;
        }
        /** @var Lpa $lpa */
        $lpa = $lpaResult->getData();
        $metaData = $lpa->getMetaData();

        $currentProcessingStatus = array_key_exists(LPA::SIRIUS_PROCESSING_STATUS, $metaData) ?
            $metaData[LPA::SIRIUS_PROCESSING_STATUS] : null;

        return $currentProcessingStatus;
    }

    private function updateMetadata($lpaId, $lpaStatus)
    {
        $lpaResult = $this->getService()->fetch($lpaId, $this->routeUserId);

        if ($lpaResult instanceof ApiProblem) {
            $this->getLogger()->err('Error accessing LPA data: ' . $lpaResult->getDetail());
            return $lpaResult;
        }

        /** @var Lpa $lpa */
        $lpa = $lpaResult->getData();
        $metaData = $lpa->getMetaData();

        // Update metadata in DB
        $metaData[LPA::SIRIUS_PROCESSING_STATUS] = $lpaStatus;
        $this->getService()->patch(['metadata' => $metaData], $lpaId, $this->routeUserId);

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
     * @throws \Http\Client\Exception
     */
    public function get($ids)
    {
        if (empty($this->routeUserId)) {
            //  userId MUST be present in the URL
            throw new ApiProblemException('User identifier missing from URL', 400);
        }

        $exploded_ids = explode(',', $ids);
        $results = [];
        $idsToCheckInSirius = [];

        foreach ($exploded_ids as $id) {
            $currentProcessingStatus = $this->getCurrentProcessingStatus($id);

            if ($currentProcessingStatus instanceof ApiProblem) {
                $results[$id] = ['found' => false];
                continue;
            }

            if ($currentProcessingStatus == null) {
                $results[$id] = ['found' => false];
            } else {
                $results[$id] = ['found' => true, 'status' => $currentProcessingStatus];
            }

            //Add id's to array, to check updates in Sirius for applications that has not reached the last stage of processing.
            if ($currentProcessingStatus != Lpa::SIRIUS_PROCESSING_STATUS_RETURNED) {
                $idsToCheckInSirius[] = $id;
            }
        }
        // Get status update from Sirius
        if (!empty($idsToCheckInSirius )) {

            $siriusStatusResult = $this->processingStatusService->getStatuses($idsToCheckInSirius);

            if (!empty($siriusStatusResult))
            {
                // updates the results for the status received back from Sirius
                foreach ($siriusStatusResult as $lpaId => $lpaStatus)
                {
                    // If there was a status returned
                    if ($lpaStatus != null) {
                        $currentResult = $results[$lpaId];
                        $currentProcessingStatus = $currentResult['found'] ? $currentResult['status'] : null;

                        // If it doesn't match what we already have update the database
                        if ($lpaStatus != $currentProcessingStatus) {

                            $this->updateMetadata($lpaId, $lpaStatus);
                        }

                        $results[$lpaId] = ['found' => true, 'status' => $lpaStatus];
                    }
                }
            }
        }
        return new Json($results);
    }
}
