<?php
namespace Application\Controller;

use Application\Library\Http\Response\Json as JsonResponse;
use Application\Library\Http\Response\NoContent as NoContentResponse;
use Application\Model\Service\Feedback\Service as FeedbackService;
use Zend\Mvc\Controller\AbstractRestfulController;
use ZF\ApiProblem\ApiProblem;
use ZF\ApiProblem\ApiProblemResponse;

class FeedbackController extends AbstractRestfulController
{

    /**
     * @var FeedbackService
     */
    private $service;

    public function __construct(FeedbackService $service)
    {
        $this->service = $service;
    }

    public function getList()
    {
        return new JsonResponse(['test' => true]);
    }

    public function create($data)
    {
        $result = $this->service->add($data);

        if ($result === false) {
            return new ApiProblemResponse(new ApiProblem(400, 'Unable to save feedback. Ensure at least one valid field is sent.'));
        }

        return new NoContentResponse;
    }

}
