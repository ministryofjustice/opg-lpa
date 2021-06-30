<?php

namespace Application\Controller\General;

use Application\Model\Service\Guidance\Guidance;
use Laminas\View\Model\ViewModel;
use Application\Controller\AbstractBaseController;

class GuidanceController extends AbstractBaseController
{
    /**
     * @var Guidance
     */
    private $guidanceService;

    public function indexAction()
    {
        $model = new ViewModel($this->guidanceService->parseMarkdown());


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

    public function setGuidanceService(Guidance $guidanceService): void
    {
        $this->guidanceService = $guidanceService;
    }
}
