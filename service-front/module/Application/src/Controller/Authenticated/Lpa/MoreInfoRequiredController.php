<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractLpaController;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class MoreInfoRequiredController extends AbstractLpaController
{
    use LoggerTrait;

    public function indexAction()
    {
        return new ViewModel(['lpaId' => $this->getLpa()->id],);;
    }
}
