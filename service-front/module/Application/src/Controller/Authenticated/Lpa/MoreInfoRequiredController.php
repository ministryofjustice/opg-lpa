<?php

namespace Application\Controller\Authenticated\Lpa;

use Application\Controller\AbstractAuthenticatedController;
use Application\Listener\LpaLoaderTrait;
use Laminas\View\Model\ViewModel;
use MakeShared\Logging\LoggerTrait;

class MoreInfoRequiredController extends AbstractAuthenticatedController
{
    use LoggerTrait;
    use LpaLoaderTrait;

    public function indexAction()
    {
        return new ViewModel(['lpaId' => $this->getLpa()->id]);
    }
}
