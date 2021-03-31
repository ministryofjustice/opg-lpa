<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Opg\Lpa\DataModel\Lpa\Payment\Calculator;
use Laminas\View\Model\ViewModel;
use function number_format;
use function floatval;

class SummaryController extends AbstractLpaController
{
    public function indexAction()
    {
        //  Get the return route from the query - if none was specified then default to the applicant route
        $returnRoute = $this->params()->fromQuery('return-route', 'lpa/applicant');

        $isRepeatApplication = ($this->getLpa()->repeatCaseNumber != null);

        $lowIncomeFee = Calculator::getLowIncomeFee($isRepeatApplication);
        $lowIncomeFee = number_format(floatval($lowIncomeFee), 2);

        $fullFee = Calculator::getFullFee($isRepeatApplication);
        $fullFee = number_format(floatval($fullFee), 2);

        $viewParams = [
            'returnRoute' => $returnRoute,
            'fullFee' => $fullFee,
            'lowIncomeFee' => $lowIncomeFee,
        ];

        return new ViewModel($viewParams);
    }
}
