<?php
namespace Application\Controller;

use Zend\Mvc\MvcEvent;
use DateTime;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Feedback\Service as FeedbackService;
use Zend\Mvc\Controller\AbstractRestfulController;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZfcRbac\Service\AuthorizationService;
use Zend\Mvc\Controller\PluginManager;

class FeedbackController extends AbstractRestfulController
{

    /**
     * @var FeedbackService
     */
    private $service;

    /**
     * @var AuthorizationService
     */
    protected $authorizationService;


    /**
     * FeedbackController constructor.
     * @param FeedbackService $service
     * @param AuthorizationService $authorizationService
     */
    public function __construct(FeedbackService $service, AuthorizationService $authorizationService)
    {
        $this->service = $service;
        $this->authorizationService = $authorizationService;
    }

    /**
     * Returns all feedback for the given date range
     *
     * @return JsonResponse|mixed|ApiProblemResponse
     */
    public function getList()
    {
        if (!$this->authorizationService->isGranted('authenticated')) {
            return new ApiProblemResponse(
                new ApiProblem(401, 'You need to be authenticated to access this service.')
            );
        }

        //---

        $query = $this->params()->fromQuery();

        if (!isset($query['from']) || !isset($query['to'])) {
            return new ApiProblemResponse(
                new ApiProblem(400, "Both 'from' and 'to' parameters are required.")
            );
        }

        //  Create the date limits and ensure that the to date is adjsuted to the end of the day
        $from = new DateTime($query['from']);
        $to = new DateTime($query['to']);
        $to->setTime(23, 59, 59);

        $results = $this->service->get($from, $to);

        $output = iterator_to_array($results);

        return new JsonResponse([
            'count' => count($output),
            'results' => $output,
            'prunedBefore' => $this->service->getPruneDate()->format('c'),
        ]);
    }


    /**
     * Adds a new item of feedback
     *
     * @param mixed $data
     * @return NoContentResponse|mixed|ApiProblemResponse
     */
    public function create($data)
    {
        $result = $this->service->add($data);

        if ($result === false) {
            return new ApiProblemResponse(
                new ApiProblem(400, 'Unable to save feedback. Ensure at least one valid field is sent.')
            );
        }

        return new NoContentResponse;
    }

}
