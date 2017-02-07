<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Zend\View\Model\ViewModel;

class SummaryController extends AbstractLpaController
{
    public function indexAction()
    {
        $fromPage = $this->params()->fromRoute('from-page');

        $viewParams = [
            'returnRoute' => 'lpa/' . $fromPage,
        ];

        return new ViewModel($viewParams);
    }
}
