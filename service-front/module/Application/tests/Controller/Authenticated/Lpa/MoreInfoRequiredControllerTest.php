<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\MoreInfoRequiredController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\View\Model\ViewModel;

final class MoreInfoRequiredControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction(): void
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
