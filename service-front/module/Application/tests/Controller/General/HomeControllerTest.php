<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\HomeController;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Laminas\View\Model\ViewModel;

final class HomeControllerTest extends AbstractControllerTestCase
{
    public function testIndexAction()
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(82, $result->getVariable('lpaFee'));
        $this->assertEquals('1.2.3.4-test', $result->getVariable('dockerTag'));
    }

    public function testRedirectAction()
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        $this->redirect->shouldReceive('toUrl')
            ->withArgs(['https://www.gov.uk/power-of-attorney/make-lasting-power'])
            ->andReturn('https://www.gov.uk/power-of-attorney/make-lasting-power')->once();

        $result = $controller->redirectAction();

        $this->assertEquals('https://www.gov.uk/power-of-attorney/make-lasting-power', $result);
    }

    public function testCookieAction()
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->enableCookieAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testAccessibilityAction()
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->accessibilityAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testTermsAction()
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->termsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testContactAction()
    {
        /** @var HomeController $controller */
        $controller = $this->getController(HomeController::class);

        /** @var ViewModel $result */
        $result = $controller->contactAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }
}
