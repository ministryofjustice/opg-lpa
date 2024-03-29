<?php

namespace Application\Controller\General;

use Application\Model\Service\Guidance\Guidance;
use Laminas\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class GuidanceController extends AbstractBaseController
{
    /** @var Guidance */
    private $guidanceService;

    public function indexAction()
    {
        $model = new ViewModel($this->guidanceService->parseMarkdown());

        /**
         * Laminas controller request specifies the wrong interface, so the
         * request has no useful methods. It's actually a Laminas HTTP request
         * which does have the methods we want.
         *
         * @psalm-suppress UndefinedInterfaceMethod
         */
        if ($this->request->isXmlHttpRequest()) {
            // if this is accessed via ajax request, disable layout, and return the core text content
            $model->setTemplate('guidance/opg-help-content.twig');
        } else {
            // Otherwise include the layout.
            $model->setTemplate('guidance/opg-help-with-layout.twig');
        }

        $model->setTerminal(true);

        return $model;
    }

    public function setGuidanceService(Guidance $guidanceService)
    {
        $this->guidanceService = $guidanceService;
    }
}
