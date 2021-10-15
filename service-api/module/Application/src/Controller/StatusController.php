<?php

namespace Application\Controller;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\Http\Response\Json;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\Service\Applications\Service as ApplicationsService;
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
     * @var $applicationsService ApplicationsService
     */
    private $applicationsService;

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
        return $this->applicationsService;
    }

    /**
     * @param AuthorizationService $authorizationService
     * @param Service $applicationsService
     * @param ProcessingStatusService $processingStatusService
     */
    public function __construct(
        AuthorizationService $authorizationService,
        ApplicationsService $applicationsService,
        ProcessingStatusService $processingStatusService
    ) {
        $this->authorizationService = $authorizationService;
        $this->applicationsService = $applicationsService;
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

    // $lpaId: ID of LPA to update
    // $metaData: existing metadata for the LPA; [] if no metadata exists yet
    // $data: data to use to update the existing metadata
    private function updateMetadata($lpaId, $metaData, $data)
    {
        // Update metadata in DB
        $newMeta[LPA::SIRIUS_PROCESSING_STATUS] = $data['status'];
        $newMeta[LPA::APPLICATION_REGISTRATION_DATE] = $data['registrationDate'];
        $newMeta[LPA::APPLICATION_RECEIPT_DATE] = $data['receiptDate'];
        $newMeta[LPA::APPLICATION_REJECTED_DATE] = $data['rejectedDate'];
        $newMeta[LPA::APPLICATION_INVALID_DATE] = $data['invalidDate'];
        $newMeta[LPA::APPLICATION_WITHDRAWN_DATE] = $data['withdrawnDate'];

        // TODO edit third party library
        $newMeta['application-dispatch-date'] = $data['dispatchDate'];

        if ($this->hasDifference($newMeta, $metaData)) {
            $metaData = array_merge($metaData, $newMeta);
            $this->getService()->patch(['metadata' => $metaData], $lpaId, $this->routeUserId);
            $this->getLogger()->debug('Updated metadata for: ' . $lpaId . var_export($metaData, true));
        }
    }

    private function getValue($array, $key, $default = null)
    {
        return (isset($array[$key]) ? $array[$key] : $default);
    }

    // returns true if the value of at least one key in $array1 is different
    // from that key in $array2, and the value in $array1 is not null (we
    // don't bother to save null values to the metadata unless the value
    // for the same key in $array2 is *not* null)
    private function hasDifference($array1, $array2) : bool
    {
        foreach ($array1 as $key => $array1Value) {
            $array2Value = $this->getValue($array2, $key);

            if ($array1Value != $array2Value
            && (!is_null($array1Value) || (is_null($array1Value) && !is_null($array2Value)))) {
                return true;
            }
        }
        return false;
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

        $explodedIds = explode(',', $ids);

        // Fetch requested LPAs from db, provided they are owned by the user;
        // this is [] if the db is not available for any reason
        $lpasFromDb = $this->applicationsService->filterByIdsAndUser($explodedIds, $this->routeUserId);

        // Convert db results into a map from ID to metadata
        $lpaMetas = array_reduce($lpasFromDb, function ($sofar, $lpa) {
            $sofar['' . $lpa->getId()] = $lpa->getMetaData();
            return $sofar;
        }, []);

        // Adding an array to check ids for which status requests would be sent
        // without any condition set
        $allIdsToCheckStatusInSirius = [];

        // Compare each requested ID against the ones retrieved from the db
        $dbResults = [];
        foreach ($explodedIds as $id) {
            $allIdsToCheckStatusInSirius[] = $id;

            if (array_key_exists($id, $lpaMetas)) {
                // We got a record from db: status=status in db
                $dbResults[$id] = [
                    'status' => $this->getValue($lpaMetas[$id], LPA::SIRIUS_PROCESSING_STATUS),
                    'inDb' => true,
                ];
            }
            else {
                // We found no record for it
                $dbResults[$id] = [
                    'status' => null,
                    'inDb' => false,
                ];
            }
        }

        // This is our eventual return value
        $results = [];

        if (!empty($allIdsToCheckStatusInSirius)) {
            // LPA-3534 Log the request
            $this->getLogger()->info(
                'All application ids to check in Sirius :' .
                implode("','",$allIdsToCheckStatusInSirius) .
                "'"
            );
            $this->getLogger()->info(
                'Count of all application ids to check in Sirius :' .
                count($allIdsToCheckStatusInSirius)
            );

            // Get status update from Sirius
            $siriusResponseArray = $this->processingStatusService->getStatuses($allIdsToCheckStatusInSirius);
            print_r($siriusResponseArray);
            // Update the results for the status received back from Sirius
            foreach ($siriusResponseArray as $lpaId => $lpaDetail) {

                // If the processStatusService didn't get a response for
                // this LPA (it hasn't been received yet), the detail is null
                // and the LPA will display as "Waiting"
                if (is_null($lpaDetail['response'])) {
                    $results[$lpaId] = ['found' => false];
                }
                // There was a status returned by processStatusService
                else {
                    $dbResult = $dbResults[$lpaId];
                    $dbProcessingStatus = $this->getValue($dbResult, 'status');

                    // Common data, whether the status is set or not
                    $data = [
                        'deleted'      => $lpaDetail['deleted'],
                        'status'       => $this->getValue($lpaDetail['response'],'status'),
                        'rejectedDate' => $this->getValue($lpaDetail['response'], 'rejectedDate')
                    ];

                    if (isset($data['status'])) {
                        // Data we only need if status is set already
                        $data['receiptDate'] = $this->getValue($lpaDetail['response'], 'receiptDate');
                        $data['registrationDate'] = $this->getValue($lpaDetail['response'], 'registrationDate');
                        $data['invalidDate'] = $this->getValue($lpaDetail['response'], 'invalidDate');
                        $data['withdrawnDate'] = $this->getValue($lpaDetail['response'], 'withdrawnDate');
                        $data['dispatchDate'] = $this->getValue($lpaDetail['response'], 'dispatchDate');

                        // If we found a record in the db, try to update it
                        // (the decision of whether to run the update is made
                        // in updateMetadata)
                        $metaData = $this->getValue($lpaMetas, $lpaId, []);
                        if ($this->getValue($dbResult, 'inDb')) {
                            $this->updateMetadata($lpaId, $metaData, $data);
                        }

                        // set found to true here as we got a processing status
                        // from Sirius
                        $results[$lpaId] = [
                            'found'  => true,
                            'status' => $data['status'],
                        ];

                        continue;
                    }

                    // Forcefully set found to false, as lpa has recently been deleted from Sirius
                    // this will display "waiting" for the user
                    if ($lpaDetail['deleted']) {
                        $results[$lpaId] = [
                            'found'  => false,
                        ];

                        continue;
                    }

                    // Use the db status if we got nothing from Sirius, providing there's
                    // no rejection date
                    if (!is_null($dbProcessingStatus) && is_null($data['rejectedDate'])) {
                        $results[$lpaId] = [
                            'found'  => true,
                            'status' => $dbProcessingStatus,
                        ];

                        continue;
                    }

                    // We didn't get a status from db or Sirius
                    $results[$lpaId] = [
                        'found' => false,
                    ];
                }
            }
        }
        return new Json($results);
    }
}
