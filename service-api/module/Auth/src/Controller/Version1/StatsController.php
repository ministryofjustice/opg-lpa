<?php
namespace Application\Controller\Version1;

use Application\Model\Service\StatsService;
use Zend\View\Model\JsonModel;
use Zend\Mvc\Controller\AbstractActionController;

class StatsController extends AbstractActionController {

    /**
     * @var StatsService
     */
    private $statsService;

    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
    }

    public function indexAction(){

        return new JsonModel($this->statsService->getStats());

    } // function
} // class
