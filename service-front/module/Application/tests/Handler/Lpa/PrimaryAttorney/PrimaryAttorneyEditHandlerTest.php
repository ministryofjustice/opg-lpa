<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\AttorneyForm;
use Application\Form\Lpa\TrustCorporationForm;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Dob;
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
use RuntimeException;

class PrimaryAttorneyEditHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private AttorneyForm&MockObject $attorneyForm;
    private TrustCorporationForm&MockObject $trustForm;
    private PrimaryAttorneyEditHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->attorneyForm = $this->createMock(AttorneyForm::class);
        $this->trustForm = $this->createMock(TrustCorporationForm::class);

        $this->formElementManager->method('get')
            ->willReturnCallback(function (string $name) {
                if ($name === 'Application\Form\Lpa\TrustCorporationForm') {
                    return $this->trustForm;
                }
                return $this->attorneyForm;
            });

        $this->handler = new PrimaryAttorneyEditHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createHumanAttorney(): Human
    {
        $attorney = new Human();
        $attorney->id = 1;
        $attorney->name = new Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney']);
        $attorney->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);
        $attorney->dob = new Dob(['date' => new \DateTime('1980-01-15')]);

        return $attorney;
    }

    private function createTrustAttorney(): TrustCorporation
    {
        $attorney = new TrustCorporation();
        $attorney->id = 2;
        $attorney->name = 'Test Trust Corp';
        $attorney->number = '12345678';
        $attorney->address = new Address(['address1' => '1 Street', 'postcode' => 'AB1 2CD']);

        return $attorney;
    }

    private function createLpaWithAttorney($attorney): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->type = Document::LPA_TYPE_PF;
        $lpa->document->primaryAttorneys = [0 => $attorney];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];
        $lpa->document->donor = null;
        $lpa->document->certificateProvider = null;
        $lpa->document->correspondent = null;

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        int|string|null $idx = 0,
        bool $isXhr = false,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpaWithAttorney($this->createHumanAttorney());

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/how-primary-attorneys-make-decision');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $route = new Route('/lpa/:lpa-id/primary-attorney/edit/:idx', new \Application\Middleware\StubMiddleware(), null, 'lpa/primary-attorney/edit');
        $routeResult = RouteResult::fromRoute($route, ['lpa-id' => $lpa->id, 'idx' => $idx]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/primary-attorney/edit')
            ->withAttribute(RouteResult::class, $routeResult);

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWithHumanAttorneyBindsDataAndRendersPersonForm(): void
    {
        $this->attorneyForm->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/person-form.twig',
                $this->anything()
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithTrustAttorneyRendersTrustForm(): void
    {
        $lpa = $this->createLpaWithAttorney($this->createTrustAttorney());

        $this->trustForm->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/trust-form.twig',
                $this->anything()
            )
            ->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest('GET', [], null, 999));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testPostValidFormUpdatesAndRedirects(): void
    {
        $this->attorneyForm->method('isValid')->willReturn(true);
        $this->attorneyForm->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Updated', 'last' => 'Attorney'],
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setPrimaryAttorney')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/next-route');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Updated'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidFormReturnsJsonForPopup(): void
    {
        $this->attorneyForm->method('isValid')->willReturn(true);
        $this->attorneyForm->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Updated', 'last' => 'Attorney'],
        ]);

        $this->lpaApplicationService->method('setPrimaryAttorney')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Updated'], null, 0, true)
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPostThrowsExceptionWhenApiUpdateFails(): void
    {
        $this->attorneyForm->method('isValid')->willReturn(true);
        $this->attorneyForm->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Updated', 'last' => 'Attorney'],
        ]);

        $this->lpaApplicationService->method('setPrimaryAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update a primary attorney');

        $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Updated'])
        );
    }

    public function testPostInvalidFormRendersForm(): void
    {
        $this->attorneyForm->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer->expects($this->once())->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => ''])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
