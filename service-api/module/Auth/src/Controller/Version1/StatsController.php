<?php
namespace Auth\Controller\Version1;

use Auth\Model\Service\StatsService;
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
