<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\PrimaryAttorneyHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrimaryAttorneyHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MvcUrlHelper&MockObject $urlHelper;
    private PrimaryAttorneyHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new PrimaryAttorneyHandler(
            $this->renderer,
            $this->urlHelper,
        );
    }

    private function createLpa(int $attorneyCount = 0): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];

        for ($i = 0; $i < $attorneyCount; $i++) {
            $attorney = new Human();
            $attorney->name = new Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney' . $i]);
            $attorney->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);
            $lpa->document->primaryAttorneys[] = $attorney;
        }

        return $lpa;
    }

    private function createRequest(?Lpa $lpa = null): ServerRequest
    {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/how-primary-attorneys-make-decision');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/primary-attorney');
    }

    public function testGetWithNoAttorneysRendersPageWithoutNextUrl(): void
    {
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney/add');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/index.twig',
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('addUrl', $params);
                    $this->assertArrayNotHasKey('attorneys', $params);
                    $this->assertArrayNotHasKey('nextUrl', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetWithAttorneysRendersPageWithNextUrl(): void
    {
        $lpa = $this->createLpa(2);

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/index.twig',
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('addUrl', $params);
                    $this->assertArrayHasKey('attorneys', $params);
                    $this->assertCount(2, $params['attorneys']);
                    $this->assertArrayHasKey('nextUrl', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testAttorneyParamsContainEditAndDeleteUrls(): void
    {
        $lpa = $this->createLpa(1);

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $attorney = $params['attorneys'][0];
                    $this->assertArrayHasKey('editUrl', $attorney);
                    $this->assertArrayHasKey('confirmDeleteUrl', $attorney);
                    $this->assertArrayHasKey('attorney', $attorney);
                    $this->assertArrayHasKey('name', $attorney['attorney']);
                    $this->assertArrayHasKey('address', $attorney['attorney']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest($lpa));
    }
}
