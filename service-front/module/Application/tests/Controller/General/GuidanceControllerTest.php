<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\GuidanceController;
use Application\Model\Service\Guidance\Guidance;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use Laminas\View\Model\ViewModel;

class GuidanceControllerTest extends AbstractControllerTestCase
{
    /**
     * @var MockInterface|Guidance
     */
    private $guidanceService;

    protected function getController(string $controllerName)
    {
        /** @var GuidanceController $controller */
        $controller = parent::getController(GuidanceController::class);

        $this->guidanceService = Mockery::mock(Guidance::class);
        $controller->setGuidanceService($this->guidanceService);

        return $controller;
    }

    public function testIndexActionIsXmlHttpRequestTrue()
    {
        $controller = $this->getController(GuidanceController::class);

        $this->guidanceService->shouldReceive('parseMarkdown')->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('guidance/opg-help-content.twig', $result->getTemplate());
    }

    public function testIndexActionIsXmlHttpRequestFalse()
    {
        $controller = $this->getController(GuidanceController::class);

        $this->guidanceService->shouldReceive('parseMarkdown')->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('guidance/opg-help-with-layout.twig', $result->getTemplate());
    }
}
