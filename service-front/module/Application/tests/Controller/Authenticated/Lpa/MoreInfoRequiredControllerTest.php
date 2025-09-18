<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\MoreInfoRequiredController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\View\Model\ViewModel;

final class MoreInfoRequiredControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction()
    {
        /** @var MoreInfoRequiredController $controller */
        $controller = $this->getController(MoreInfoRequiredController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->lpa->id, $result->getVariable('lpaId'));
    }
}
