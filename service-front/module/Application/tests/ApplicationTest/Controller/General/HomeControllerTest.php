<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\HomeController;
use ApplicationTest\Controller\AbstractControllerTest;
use Zend\View\Model\ViewModel;

class HomeControllerTest extends AbstractControllerTest
{
    /**
     * @var HomeController
     */
    private $controller;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(HomeController::class);
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals(82, $result->getVariable('lpaFee'));
        $this->assertEquals('1.2.3.4-test', $result->getVariable('dockerTag'));
    }

    public function testRedirectAction()
    {
        $this->redirect->shouldReceive('toUrl')
            ->withArgs(['https://www.gov.uk/power-of-attorney/make-lasting-power'])
            ->andReturn('https://www.gov.uk/power-of-attorney/make-lasting-power')->once();

        $result = $this->controller->redirectAction();

        $this->assertEquals('https://www.gov.uk/power-of-attorney/make-lasting-power', $result);
    }

    public function testCookieAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->enableCookieAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testTermsAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->termsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }

    public function testContactAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->contactAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
    }
}
