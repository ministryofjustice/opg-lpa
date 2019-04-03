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

        foreach ($exploded_ids as $id) {
            $this->getLogger()->debug('Checking Sirius status for ' . $id);

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

            // If application has already reached the last stage of processing ('Concluded') do not check for updates
            if ($metaData[LPA::SIRIUS_PROCESSING_STATUS] == 'Returned') {
                $results[$id] = ['found' => true, 'status' => 'Returned'];
                continue;
            }

            $siriusStatusResult = $this->processingStatusService->getStatus($id);

            if ($siriusStatusResult != null && $siriusStatusResult != $metaData[LPA::SIRIUS_PROCESSING_STATUS]) {

                // Update metadata in DB
                $metaData[LPA::SIRIUS_PROCESSING_STATUS] = $siriusStatusResult;

                $this->getService()->patch(['metadata' => $metaData], $id, $this->routeUserId);
            }

            $results[$id] = ['found' => true, 'status' => $metaData[LPA::SIRIUS_PROCESSING_STATUS]];
        }


        return new Json($results);
    }

}