<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\DashboardController;

class TestableDashboardController extends DashboardController
{
    public function testCheckAuthenticated($allowRedirect = true)
    {
        return parent::checkAuthenticated($allowRedirect);
    }
}
