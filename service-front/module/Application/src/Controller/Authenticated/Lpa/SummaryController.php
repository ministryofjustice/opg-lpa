<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Laminas\View\Model\ViewModel;

class SummaryController extends AbstractLpaController
{
    public function indexAction()
    {
        //  Get the return route from the query - if none was specified then default to the applicant route
        $returnRoute = $this->params()->fromQuery('return-route', 'lpa/applicant');

        $isRepeatApplication = ($this->getLpa()->repeatCaseNumber != null);

        $lowIncomeFee = Calculator::getLowIncomeFee($isRepeatApplication);
        $fullFee = Calculator::getFullFee($isRepeatApplication);

        $viewParams = [
            'returnRoute' => $returnRoute,
            'fullFee' => $fullFee,
            'lowIncomeFee' => $lowIncomeFee,
        ];

        return new ViewModel($viewParams);
    }
}
