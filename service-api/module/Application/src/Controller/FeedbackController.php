<?php
namespace Application\Controller;

use Zend\Mvc\MvcEvent;
use Application\Library\DateTime;
use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Feedback\Service as FeedbackService;
use Zend\Mvc\Controller\AbstractRestfulController;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;
use ZfcRbac\Service\AuthorizationService;

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
     * Execute the request
     *
     * @param MvcEvent $event
     * @return mixed|ApiProblem|ApiProblemResponse
     */
    public function onDispatch(MvcEvent $event)
    {
        if (!$this->authorizationService->isGranted('authenticated')) {
            return new ApiProblemResponse(
                new ApiProblem(401, 'You need to be authenticated to access this service.')
            );
        }

        return parent::onDispatch($event);
    }


    /**
     * Returns all feedback for the given date range
     *
     * @return JsonResponse|mixed|ApiProblemResponse
     */
    public function getList()
    {

        $query = $this->params()->fromQuery();

        if (!isset($query['from']) || !isset($query['to'])) {
            return new ApiProblemResponse(
                new ApiProblem(400, "Both 'from' and 'to' parameters are required.")
            );
        }

        $from   = new DateTime($query['from']);
        $to     = new DateTime($query['to']);

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
