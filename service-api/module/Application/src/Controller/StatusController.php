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

//    /**
//     * Checks whether we have a status from Sirius for a list of applications
//     *
//     * Returns an array of results, one for each application. Will return {"found": false} for an application if it is
//     * not found, does not have a processing status, or does not belong to this user. Otherwise returns
//     * {"found": true, "status": "<processing status>"}
//     *
//     * @param string $ids
//     * @return Json
//     * @throws Exception
//     * @throws \Http\Client\Exception
//     */
//    public function get($ids)
//    {
//        if (empty($this->routeUserId)) {
//            //  userId MUST be present in the URL
//            throw new ApiProblemException('User identifier missing from URL', 400);
//        }
//
//        $exploded_ids = explode(',', $ids);
//        $results = [];
//
//        foreach ($exploded_ids as $id) {
//            $this->getLogger()->debug('Checking Sirius status for ' . $id);
//
//            $lpaResult = $this->getService()->fetch($id, $this->routeUserId);
//
//            // if the id isn't found, return false.
//            if ($lpaResult instanceof ApiProblem) {
//                $this->getLogger()->err('Error accessing LPA data: ' . $lpaResult->getDetail());
//
//                $results[$id] = ['found' => false];
//                continue;
//            }
//
//            /** @var Lpa $lpa */
//            $lpa = $lpaResult->getData();
//
//            $metaData = $lpa->getMetaData();
//
//            $currentProcessingStatus = array_key_exists(LPA::SIRIUS_PROCESSING_STATUS, $metaData) ?
//                $metaData[LPA::SIRIUS_PROCESSING_STATUS] : null;
//
//            // If application has already reached the last stage of processing do not check for updates
//            if ($currentProcessingStatus == Lpa::SIRIUS_PROCESSING_STATUS_RETURNED) {
//                $results[$id] = ['found' => true, 'status' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED];
//                continue;
//            }
//
//            // Get status update from Sirius
//            $siriusStatusResult = $this->processingStatusService->getStatus($id);
//
//            // If there was a status returned
//            if ($siriusStatusResult != null)
//            {
//                // If it doesn't match what we already have update the database
//                if($siriusStatusResult != $currentProcessingStatus) {
//                    // Update metadata in DB
//                    $metaData[LPA::SIRIUS_PROCESSING_STATUS] = $siriusStatusResult;
//
//                    $this->getService()->patch(['metadata' => $metaData], $id, $this->routeUserId);
//                }
//
//                $results[$id] = ['found' => true, 'status' => $siriusStatusResult];
//            } else if ($currentProcessingStatus != null) {
//                // If we get nothing from Sirius but there's an existing status use that
//                $results[$id] = ['found' => true, 'status' => $currentProcessingStatus];
//            } else {
//                // If both sirius and the LPA DB have no status set a not found response
//                $results[$id] = ['found' => false];
//            }
//        }
//
//        return new Json($results);
//    }


    public function getMetadata($id, $routeUserId)
    {
        $lpaResult = $this->getService()->fetch($id, $this->routeUserId);

        //if the id isn't found, return false.
        if ($lpaResult instanceof ApiProblem) {
             $this->getLogger()->err('Error accessing LPA data: ' . $lpaResult->getDetail());

            $results[$id] = ['found' => false];
         }

        /** @var Lpa $lpa */
        $lpa = $lpaResult->getData();
        $metaData = $lpa->getMetaData();

        return $metaData;
    }

    // LPA-3230 NEW
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
            $this->getLogger()->debug('********** Checking existing status in the lpa DB for the lpa application' . $id);

//          $metaData = $this->getMetadata($id, $this->routeUserId);

            //fn start
            $lpaResult = $this->getService()->fetch($id, $this->routeUserId);

            // if the id isn't found, return false.
            if ($lpaResult instanceof ApiProblem) {
                $this->getLogger()->err('Error accessing LPA data: ' . $lpaResult->getDetail());

                $results[$id] = ['found' => false];
                continue;
            }
            /** @var Lpa $lpa */
            $lpa = $lpaResult->getData();
            $metaData = $lpa->getMetaData();
            //fn end

            $currentProcessingStatus = array_key_exists(LPA::SIRIUS_PROCESSING_STATUS, $metaData) ?
                $metaData[LPA::SIRIUS_PROCESSING_STATUS] : null;

            // If application has already reached the last stage of processing do not check for updates
            if ($currentProcessingStatus == Lpa::SIRIUS_PROCESSING_STATUS_RETURNED) {
                $results[$id] = ['found' => true, 'status' => Lpa::SIRIUS_PROCESSING_STATUS_RETURNED];
                continue;
            } else if ($currentProcessingStatus == null) {
                $results[$id] = ['found' => false];
            } else {
                $results[$id] = ['found' => true, 'status' => $currentProcessingStatus];
            }
            // Add the id's to the array to check for updates in Sirius DB
            $idsToCheckInSirius[] = $id;
        }
        // Get status update from Sirius
        if ($idsToCheckInSirius != null) {

            $siriusStatusResult = $this->processingStatusService->getStatuses($idsToCheckInSirius);

            if ($siriusStatusResult != null)
            {
                // updates the results for the status received back from Sirius
                foreach ($siriusStatusResult as $lpaId => $lpaStatus)
                {
                    $currentResult = $results[$lpaId];
                    $currentProcessingStatus = $currentResult['found'] ? $currentResult['status'] : null;

                    // $metaData = $this-> getMetadata($lpaId, $this->routeUserId);

                    $lpaResult = $this->getService()->fetch($lpaId, $this->routeUserId);
                    // if the id isn't found, return false.
                    if ($lpaResult instanceof ApiProblem) {
                        $this->getLogger()->err('Error accessing LPA data: ' . $lpaResult->getDetail());
                        continue;
                    }
                    /** @var Lpa $lpa */
                    $lpa = $lpaResult->getData();
                    $metaData = $lpa->getMetaData();

                    // If there was a status returned
                    if ($lpaStatus != null) {
                        // If it doesn't match what we already have update the database
                        if ($lpaStatus != $currentProcessingStatus) {
                            // Update metadata in DB
                            $metaData[LPA::SIRIUS_PROCESSING_STATUS] = $lpaStatus;

                            $this->getLogger()->debug('-------------- metadata' . var_export($metaData, true));
                            $this->getService()->patch(['metadata' => $metaData], $lpaId, $this->routeUserId);
                        }
                        $results[$lpaId] = ['found' => true, 'status' => $lpaStatus];
                    }
                } //end for
            }
        }
        return new Json($results);
    }
}