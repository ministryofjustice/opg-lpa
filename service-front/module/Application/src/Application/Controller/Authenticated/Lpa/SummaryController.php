<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class SummaryController extends AbstractLpaController
{
    public function indexAction()
    {
        $lpa = $this->getLpa();
        $currentRouteName = $this->getEvent()->getRouteMatch()->getMatchedRouteName();

        $fromPage = $this->params()->fromRoute('from-page');

        //var_dump($fromPage); die;

        /*
        switch ($fromPage) {
            case 'instructions':
                $returnRoute = 'lpa/instructions';
                break;
            default:
                throw new \Exception('Invalid return route provided for summary page');
        }
        */

        //var_dump($lpa); die;

        $returnRoute = 'lpa/'.$fromPage;

        $viewParams = [
            'returnRoute' => $returnRoute,
        ];


        return new ViewModel($viewParams);
    }
}
