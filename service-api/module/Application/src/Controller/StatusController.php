<?php

namespace Application\Controller;

use Application\Library\ApiProblem\ApiProblem;
use Application\Library\ApiProblem\ApiProblemException;
use Application\Library\ApiProblem\ApiProblemResponse;
use Application\Library\Authorization\UnauthorizedException;
use Application\Library\Http\Response\Json;
use Application\Model\DataAccess\Repository\Application\LockedException;
use Application\Model\Service\Applications\Service as ApplicationsService;
use Application\Model\Service\ProcessingStatus\Service as ProcessingStatusService;
use Exception;
use Laminas\Mvc\Controller\AbstractRestfulController;
use Laminas\Mvc\MvcEvent;
use Lmc\Rbac\Mvc\Service\AuthorizationService;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\Logging\LoggerTrait;
use Psr\Log\LoggerAwareInterface;

class StatusController extends AbstractRestfulController implements LoggerAwareInterface
{
    use LoggerTrait;

    /**
     * Name of the identifier used in the routes to this RESTful controller
     */
    /* @var string */
    protected $identifierName = 'lpaIds';

    /* @var $applicationsService ApplicationsService */
    private $applicationsService;

    /* @var $authorizationService AuthorizationService */
    private $authorizationService;

    /* @var $processingStatusService ProcessingStatusService */
    private $processingStatusService;

    /* @var $routeUserId string */
    private $routeUserId;

    /**
     * Get the service to use
     *
     * @return ApplicationsService
     */
    protected function getService()
    {
        return $this->applicationsService;
    }

    /**
     * @param AuthorizationService $authorizationService
     * @param ApplicationsService $applicationsService
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
     * @param MvcEvent $e
     * @return mixed|ApiProblemResponse
     * @throws ApiProblemException
     */
    public function onDispatch(MvcEvent $e)
    {
        //  If possible get the user and LPA from the ID values in the route
        $this->routeUserId = $e->getRouteMatch()->getParam('userId');

        if (empty($this->routeUserId)) {
            //  userId MUST be present in the URL
            throw new ApiProblemException('User identifier missing from URL', 400);
        }

        if (!$this->authorizationService->isGranted('authenticated')) {
            throw new UnauthorizedException('You need to be authenticated to access this service');
        }

        try {
            return parent::onDispatch($e);
        } catch (UnauthorizedException $ex) {
            return new ApiProblemResponse(new ApiProblem(401, 'Access Denied'));
        } catch (LockedException $ex) {
            return new ApiProblemResponse(new ApiProblem(403, 'LPA has been locked'));
        }
    }

    // $lpaId: ID of LPA to update
    // $metaData: existing metadata for the LPA; [] if no metadata exists yet
    // $data: data to use to update the existing metadata
    private function updateMetadata(string $lpaId, $metaData, array $data): void
    {
        // Update metadata in DB
        $newMeta[LPA::SIRIUS_PROCESSING_STATUS] = $data['status'];
        $newMeta[LPA::APPLICATION_REGISTRATION_DATE] = $data['registrationDate'];
        $newMeta[LPA::APPLICATION_RECEIPT_DATE] = $data['receiptDate'];
        $newMeta[LPA::APPLICATION_REJECTED_DATE] = $data['rejectedDate'];
        $newMeta[LPA::APPLICATION_INVALID_DATE] = $data['invalidDate'];
        $newMeta[LPA::APPLICATION_WITHDRAWN_DATE] = $data['withdrawnDate'];

        // TODO edit third party library
        $newMeta['application-return-unpaid'] = $data['returnUnpaid'];
        $newMeta['application-dispatch-date'] = $data['dispatchDate'];

        if ($this->hasDifference($newMeta, $metaData)) {
            $metaData = array_merge($metaData, $newMeta);
            $this->getService()->patch(['metadata' => $metaData], $lpaId, $this->routeUserId);
            $this->getLogger()->debug('Updated MetaData for LPA', [
                'lpaId' => $lpaId,
                'metaData' => $metaData
            ]);
        }
    }

    /**
     * @param array<string, mixed> $array
     */
    private function getValue(array|null $array, string $key, array|null $default = null)
    {
        return ($array[$key] ?? $default);
    }

    // returns true if the value of at least one key in $array1 is different
    // from that key in $array2
    /**
     * @param array<string, mixed> $array1
     */
    private function hasDifference(array $array1, $array2): bool
    {
        foreach ($array1 as $key => $array1Value) {
            $array2Value = $this->getValue($array2, $key);

            if (
                $array1Value != $array2Value
                && (!is_null($array1Value) || !is_null($array2Value))
            ) {
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
     * {"found": true, "status": "<processing status>", "deleted": true|false, "rejectedDate": "<date string>"}
     *
     * @param mixed $id Comma-separated list of IDs to retrieve (named $id because AbstractRestfulController
     *     expects that parameter name)
     * @return Json
     * @throws Exception
     * @throws \Http\Client\Exception
     */
    public function get($id)
    {
        if (empty($this->routeUserId)) {
            //  userId MUST be present in the URL
            throw new ApiProblemException('User identifier missing from URL', 400);
        }

        $explodedIds = explode(',', $id);

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
        foreach ($explodedIds as $explodedId) {
            $allIdsToCheckStatusInSirius[] = $explodedId;

            if (array_key_exists($explodedId, $lpaMetas)) {
                // We got a record from db: status=status in db
                $dbResults[$explodedId] = [
                    'status' => $this->getValue($lpaMetas[$explodedId], LPA::SIRIUS_PROCESSING_STATUS),
                    'inDb' => true,
                ];
            } else {
                // We found no record for it
                $dbResults[$explodedId] = [
                    'status' => null,
                    'inDb' => false,
                ];
            }
        }

        // This is our eventual return value
        $results = [];

        // LPA-3534 Log the request
        $this->getLogger()->info(
            'All application ids to check in Sirius :' .
            implode("','", $allIdsToCheckStatusInSirius) .
            "'"
        );
        $this->getLogger()->info(
            'Count of all application ids to check in Sirius :' .
            count($allIdsToCheckStatusInSirius)
        );

        // Get status update from Sirius
        $siriusResponseArray = $this->processingStatusService->getStatuses($allIdsToCheckStatusInSirius);
        // Update the results for the status received back from Sirius
        foreach ($siriusResponseArray as $lpaId => $lpaDetail) {
            // If the processStatusService didn't get a response for
            // this LPA (i.e. it hasn't been received yet), the detail is null
            // and the LPA will display as "Waiting"; note we also get
            // a null response for deleted (code 410) LPAs, so we guard
            // against that here
            if (is_null($lpaDetail['response']) && !$lpaDetail['deleted']) {
                $results[$lpaId] = ['found' => false];
            } else {
                // There was a status returned by processStatusService
                // or the LPA application was deleted
                $dbResult = $dbResults[$lpaId];
                $dbProcessingStatus = $this->getValue($dbResult, 'status');

                // Common data, whether the status is set or not
                $data = [
                    'deleted' => $lpaDetail['deleted'],
                    'status' => $this->getValue($lpaDetail['response'], 'status'),
                    'rejectedDate' => $this->getValue($lpaDetail['response'], 'rejectedDate')
                ];

                // If the application was deleted, treat this as if we got a "Waiting"
                // status from Sirius; this ensures we update the status in the db so
                // it's not stale on the next request
                if ($lpaDetail['deleted']) {
                    $data['status'] = 'Waiting';
                }

                if (isset($data['status'])) {
                    // Data we only need if status is set already
                    $data['receiptDate'] = $this->getValue($lpaDetail['response'], 'receiptDate');
                    $data['registrationDate'] = $this->getValue($lpaDetail['response'], 'registrationDate');
                    $data['invalidDate'] = $this->getValue($lpaDetail['response'], 'invalidDate');
                    $data['withdrawnDate'] = $this->getValue($lpaDetail['response'], 'withdrawnDate');
                    $data['dispatchDate'] = $this->getValue($lpaDetail['response'], 'dispatchDate');
                    $data['returnUnpaid'] = $this->getValue($lpaDetail['response'], 'returnUnpaid');

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
                        'found' => true,
                        'status' => $data['status'],
                        'returnUnpaid' => $data['returnUnpaid']
                    ];
                } elseif (!is_null($dbProcessingStatus) && is_null($data['rejectedDate'])) {
                    // Use the db status if we got nothing from Sirius, providing there's
                    // no rejection date
                    $results[$lpaId] = [
                        'found' => true,
                        'status' => $dbProcessingStatus,
                    ];
                } else {
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
