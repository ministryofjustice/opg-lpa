<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\GuidanceController;
use Application\Model\Service\Guidance\Guidance;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Zend\View\Model\ViewModel;

class GuidanceControllerTest extends AbstractControllerTest
{
    /**
     * @var GuidanceController
     */
    private $controller;
    /**
     * @var MockInterface|Guidance
     */
    private $guidanceService;

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(GuidanceController::class);

        $this->guidanceService = Mockery::mock(Guidance::class);
        $this->controller->setGuidanceService($this->guidanceService);
    }

    public function testIndexActionIsXmlHttpRequestTrue()
    {
        $this->guidanceService->shouldReceive('parseMarkdown')->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('guidance/opg-help-content.twig', $result->getTemplate());
    }

    public function testIndexActionIsXmlHttpRequestFalse()
    {
        $this->guidanceService->shouldReceive('parseMarkdown')->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('guidance/opg-help-with-layout.twig', $result->getTemplate());
    }
}
