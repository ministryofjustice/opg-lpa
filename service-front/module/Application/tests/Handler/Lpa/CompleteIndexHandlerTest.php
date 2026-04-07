<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\CompleteIndexHandler;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Service\CompleteViewParamsHelper;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompleteIndexHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private CompleteViewParamsHelper&MockObject $completeViewParamsHelper;
    private CompleteIndexHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->completeViewParamsHelper = $this->createMock(CompleteViewParamsHelper::class);

        $this->completeViewParamsHelper->method('build')->willReturn([]);
        $this->renderer->method('render')->willReturn('html');

        $this->handler = new CompleteIndexHandler(
            $this->renderer,
            $this->lpaApplicationService,
            $this->completeViewParamsHelper,
        );
    }

    private function createRequest(?Lpa $lpa = null): ServerRequest
    {
        $lpa = $lpa ?? FixturesData::getPfLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/complete');
    }

    public function testLocksLpaWhenNotAlreadyLocked(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->locked = false;

        $this->lpaApplicationService->expects($this->once())->method('lockLpa')->with($lpa);

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testDoesNotLockLpaWhenAlreadyLocked(): void
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->locked = true;

        $this->lpaApplicationService->expects($this->never())->method('lockLpa');

        $this->handler->handle($this->createRequest($lpa));
    }

    public function testReturnsHtmlResponse(): void
    {
        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testRendersCorrectTemplate(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/complete/complete.twig', $this->anything())
            ->willReturn('html');

        $this->handler->handle($this->createRequest());
    }

    public function testViewParamsFromHelperPassedToRenderer(): void
    {
        $lpa = FixturesData::getPfLpa();
        $params = ['lp1Url' => '/lpa/download/123', 'cloneUrl' => '/clone/123'];

        $completeViewParamsHelper = $this->createMock(CompleteViewParamsHelper::class);
        $completeViewParamsHelper->method('build')->willReturn($params);

        $handler = new CompleteIndexHandler(
            $this->renderer,
            $this->lpaApplicationService,
            $completeViewParamsHelper,
        );

        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(
                fn(array $vars) => $vars['lp1Url'] === '/lpa/download/123'
                    && $vars['cloneUrl'] === '/clone/123'
            ))
            ->willReturn('html');

        $handler->handle($this->createRequest($lpa));
    }
}
