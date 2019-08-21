<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\StatusController;


class TestableStatusController extends StatusController
{
    public function testCheckAuthenticated($allowRedirect = true)
    {
        return parent::checkAuthenticated($allowRedirect);
    }
}

