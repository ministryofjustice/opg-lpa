<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\DonorIndexHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\LongName;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DonorIndexHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MvcUrlHelper&MockObject $urlHelper;
    private DonorIndexHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new DonorIndexHandler(
            $this->renderer,
            $this->urlHelper,
        );
    }

    private function createLpa(?Donor $donor = null): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->donor = $donor;

        return $lpa;
    }

    private function createDonor(): Donor
    {
        $donor = new Donor();
        $donor->name = new LongName(['title' => 'Miss', 'first' => 'Unit', 'last' => 'Test']);
        $donor->dob = new Dob(['date' => '1970-02-01']);

        return $donor;
    }

    private function createRequest(Lpa $lpa): ServerRequest
    {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/when-lpa-starts');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/donor');
    }

    public function testIndexWithNoDonorRendersWithAddUrlOnly(): void
    {
        $lpa = $this->createLpa();

        $this->urlHelper
            ->expects($this->once())
            ->method('generate')
            ->with('lpa/donor/add', ['lpa-id' => $lpa->id])
            ->willReturn('/lpa/91333263035/donor/add');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/donor/index.twig',
                $this->callback(function (array $vars): bool {
                    return $vars['addUrl'] === '/lpa/91333263035/donor/add'
                        && $vars['editUrl'] === null
                        && $vars['nextUrl'] === null;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals('rendered-html', $response->getBody()->__toString());
    }

    public function testIndexWithDonorRendersWithAllUrls(): void
    {
        $lpa = $this->createLpa($this->createDonor());

        $this->urlHelper
            ->expects($this->exactly(3))
            ->method('generate')
            ->willReturnMap([
                ['lpa/donor/add', ['lpa-id' => $lpa->id], [], '/lpa/91333263035/donor/add'],
                ['lpa/donor/edit', ['lpa-id' => $lpa->id], [], '/lpa/91333263035/donor/edit'],
                ['lpa/when-lpa-starts', ['lpa-id' => $lpa->id], [], '/lpa/91333263035/when-lpa-starts'],
            ]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/donor/index.twig',
                $this->callback(function (array $vars): bool {
                    return $vars['addUrl'] === '/lpa/91333263035/donor/add'
                        && $vars['editUrl'] === '/lpa/91333263035/donor/edit'
                        && $vars['nextUrl'] === '/lpa/91333263035/when-lpa-starts';
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
