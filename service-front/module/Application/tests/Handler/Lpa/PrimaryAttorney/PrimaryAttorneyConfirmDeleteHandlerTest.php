<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PrimaryAttorney;

use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyConfirmDeleteHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PrimaryAttorneyConfirmDeleteHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private MvcUrlHelper&MockObject $urlHelper;
    private PrimaryAttorneyConfirmDeleteHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new PrimaryAttorneyConfirmDeleteHandler(
            $this->renderer,
            $this->urlHelper,
        );
    }

    private function createLpaWithAttorney($attorney): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [0 => $attorney];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        return $lpa;
    }

    private function createRequest(
        ?Lpa $lpa = null,
        int|string|null $idx = 0,
    ): ServerRequest {
        $attorney = new Human();
        $attorney->name = new Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney']);
        $attorney->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);
        $lpa = $lpa ?? $this->createLpaWithAttorney($attorney);

        $flowChecker = $this->createMock(FormFlowChecker::class);

        $route = new Route('/lpa/:lpa-id/primary-attorney/confirm-delete/:idx', new \Application\Middleware\StubMiddleware(), null, 'lpa/primary-attorney/confirm-delete');
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => $lpa->id, 'idx' => $idx]);

        return (new ServerRequest())
            ->withMethod('GET')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/primary-attorney/confirm-delete')
            ->withAttribute(RouteResult::class, $routeResult);
    }

    public function testRendersConfirmDeletePageForHumanAttorney(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/confirm-delete.twig',
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('deleteRoute', $params);
                    $this->assertArrayHasKey('attorneyName', $params);
                    $this->assertArrayHasKey('cancelUrl', $params);
                    $this->assertFalse($params['isTrust']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRendersConfirmDeletePageForTrust(): void
    {
        $trust = new TrustCorporation();
        $trust->name = 'Test Trust Corp';
        $trust->number = '12345678';
        $trust->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);

        $lpa = $this->createLpaWithAttorney($trust);

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertTrue($params['isTrust']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest($lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest(null, 999));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }
}
