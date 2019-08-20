<?php

namespace ApplicationTest\Controller\Version2\Lpa;


use Application\Controller\Version2\Lpa\AbstractLpaController;
use Application\Model\Service\AbstractService;

class TestableAbstractLpaController extends AbstractLpaController
{

    /**
     * Get the service to use
     *
     * @return AbstractService
     */
    protected function getService()
    {
        return null;
    }
}