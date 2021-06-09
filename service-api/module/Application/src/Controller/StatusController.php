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
use Application\Logging\LoggerTrait;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use LmcRbacMvc\Service\AuthorizationService;

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
        $metaData = $lpaResult->getData()->getMetaData();

        return array_key_exists(LPA::SIRIUS_PROCESSING_STATUS, $metaData) ?
            $metaData[LPA::SIRIUS_PROCESSING_STATUS] : null;
    }

    private function updateMetadata($lpaId, $data)
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
        $metaData[LPA::SIRIUS_PROCESSING_STATUS] = $data['status'];
        $metaData[LPA::APPLICATION_REGISTRATION_DATE] = $data['registrationDate'];
        $metaData[LPA::APPLICATION_RECEIPT_DATE] = $data['receiptDate'];
        $metaData[LPA::APPLICATION_REJECTED_DATE] = $data['rejectedDate'];
        $metaData[LPA::APPLICATION_INVALID_DATE] = $data['invalidDate'];
        $metaData[LPA::APPLICATION_WITHDRAWN_DATE] = $data['withdrawnDate'];

        // TODO edit third party library
        $metaData['application-dispatch-date'] = $data['dispatchDate'];

        $this->getService()->patch(['metadata' => $metaData], $lpaId, $this->routeUserId);

        $this->getLogger()->debug('Updated metadata for: ' . $lpaId . var_export($metaData, true));
    }

    private function getValue($array, $key, $default = null)
    {
        return (isset($array[$key]) ? $array[$key] : $default);
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

        // Adding an array to check id's for which status requests would be sent without any condition set
        $allIdsToCheckStatusInSirius = [];

        foreach ($exploded_ids as $id) {
            $currentProcessingStatus = $this->getCurrentProcessingStatus($id);

            //Add all id's to array to check status in SIRIUS for all applications created by the user
            $allIdsToCheckStatusInSirius[] = $id;

            if ($currentProcessingStatus instanceof ApiProblem) {
                $results[$id] = ['found' => false];
                continue;
            }

            if ($currentProcessingStatus == null) {
                $results[$id] = ['found' => false];

            } else {
                $results[$id] = ['found' => true, 'status' => $currentProcessingStatus];
            }
        }

        //LPA-3534 Log the request
        if (!empty($allIdsToCheckStatusInSirius)) {
            $this->getLogger()->info('All application ids to check in Sirius :' .implode("','",$allIdsToCheckStatusInSirius)."'");
            $this->getLogger()->info('Count of all application ids to check in Sirius :' . count($allIdsToCheckStatusInSirius));

            // Get status update from Sirius
            $siriusResponseArray = $this->processingStatusService->getStatuses($allIdsToCheckStatusInSirius);
            if (!empty($siriusResponseArray)) {
                // updates the results for the status received back from Sirius
                foreach ($siriusResponseArray as $lpaId => $lpaDetail) {
                    // If the processStatusService didn't get a response for
                    // this LPA (it hasn't been received yet), the detail is null
                    // and the LPA will display as "Waiting"
                    if (is_null($lpaDetail)) {
                        $results[$lpaId] = ['found' => false];
                    }
                    // There was a status returned by processStatusService
                    else {
                        $currentResult = $results[$lpaId];
                        $currentProcessingStatus = $currentResult['found'] ? $currentResult['status'] : null;

                        // Common data, whether the status is set or not
                        $data = [
                            'found' => true,
                            'status' => $lpaDetail['status'],
                            'rejectedDate' => $this->getValue($lpaDetail, 'rejectedDate')
                        ];

                        if (isset($data['status'])) {
                            // Data we only need if status is set already
                            $data['receiptDate'] = $this->getValue($lpaDetail, 'receiptDate');
                            $data['registrationDate'] = $this->getValue($lpaDetail, 'registrationDate');
                            $data['invalidDate'] = $this->getValue($lpaDetail, 'invalidDate');
                            $data['withdrawnDate'] = $this->getValue($lpaDetail, 'withdrawnDate');
                            $data['dispatchDate'] = $this->getValue($lpaDetail, 'dispatchDate');

                            // If status doesn't match what we already have, update the database
                            if ($data['status'] !== $currentProcessingStatus) {
                                $this->updateMetadata($lpaId, $data);
                            }

                            $results[$lpaId] = [
                                'found' => true,
                                'status' => $data['status'],
                            ];
                        }
                        else if (!is_null($currentProcessingStatus) && is_null($data['rejectedDate'])) {
                            $results[$lpaId] = [
                                'found' => true,
                                'status' => $currentProcessingStatus,
                            ];
                        }
                    }
                }
            }
        }
        return new Json($results);
    }
}
