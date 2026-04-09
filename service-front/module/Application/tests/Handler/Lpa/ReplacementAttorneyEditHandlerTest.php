<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReplacementAttorneyEditHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private ReplacementAttorneyEditHandler $handler;

    private array $postDataHuman = [
        'name' => ['title' => 'Miss', 'first' => 'Unit', 'last' => 'Test'],
        'address' => [
            'address1' => 'Address line 1',
            'address2' => 'Address line 2',
            'address3' => 'Address line 3',
            'postcode' => 'PO5 3DE',
        ],
        'email' => ['address' => 'unit@test.com'],
        'dob' => ['day' => '01', 'month' => '02', 'year' => '1980'],
    ];

    private array $postDataTrust = [
        'name' => 'Unit Test Company',
        'number' => '0123456789',
        'address' => [
            'address1' => 'Address line 1',
            'address2' => 'Address line 2',
            'address3' => 'Address line 3',
            'postcode' => 'PO5 3DE',
        ],
        'email' => ['address' => 'unit@test.com'],
    ];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);

        $this->actorReuseDetailsService->method('getActorsList')->willReturn([]);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyEditHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->actorReuseDetailsService,
        );
    }

    private function createLpa(bool $withTrust = false): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        if ($withTrust) {
            $lpa->document->replacementAttorneys[] = FixturesData::getAttorneyTrust();
        }
        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        mixed $idx = 0,
        bool $isXhr = false,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/replacement-attorney');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $routeResult = $this->createMock(RouteResult::class);
        $routeResult->method('getMatchedParams')->willReturn(['lpa-id' => $lpa->id, 'idx' => $idx]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/replacement-attorney/edit')
            ->withAttribute(RouteResult::class, $routeResult);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        return $request;
    }

    public function testGetWithInvalidIdxReturns404(): void
    {
        $response = $this->handler->handle($this->createRequest('GET', [], null, -1));

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testGetHumanAttorneyRendersPersonForm(): void
    {
        $lpa = $this->createLpa();
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $this->formElementManager->method('get')->willReturn($form);
        $form->expects($this->once())->method('bind');

        $this->renderer->expects($this->once())->method('render')
            ->with(
                'application/authenticated/lpa/replacement-attorney/person-form.twig',
                $this->callback(fn(array $vars) => isset($vars['form']) && isset($vars['cancelUrl']))
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, 0));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetHumanAttorneyExcludesEditedAttorneyFromActorsList(): void
    {
        $lpa = $this->createLpa();
        $idx = 0;
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $this->formElementManager->method('get')->willReturn($form);

        // Assert getActorsList is called with the correct exclusion index
        $this->actorReuseDetailsService->expects($this->once())
            ->method('getActorsList')
            ->with($lpa, false, $idx)
            ->willReturn([]);

        $this->renderer->method('render')->willReturn('html');

        $this->handler->handle($this->createRequest('GET', [], $lpa, $idx));
    }

    public function testGetTrustAttorneyRendersTrustForm(): void
    {
        $lpa = $this->createLpa(true);
        $trustIdx = count($lpa->document->replacementAttorneys) - 1;

        $form = $this->createMock(\Application\Form\Lpa\TrustCorporationForm::class);
        $this->formElementManager->method('get')->willReturn($form);
        $form->expects($this->once())->method('bind');

        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/replacement-attorney/trust-form.twig', $this->anything())
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, $trustIdx));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetPopupAddsIsPopupParam(): void
    {
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $this->formElementManager->method('get')->willReturn($form);

        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(fn(array $vars) => ($vars['isPopup'] ?? false) === true))
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest('GET', [], null, 0, true));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->willReturn($form);

        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataHuman, null, 0));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidThrowsExceptionWhenApiCallFails(): void
    {
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->method('setReplacementAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update replacement attorney');

        $this->handler->handle($this->createRequest('POST', $this->postDataHuman, null, 0));
    }

    public function testPostValidRedirectsOnSuccess(): void
    {
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->method('setReplacementAttorney')->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataHuman, null, 0));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidReturnsJsonForPopup(): void
    {
        $form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->method('setReplacementAttorney')->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataHuman, null, 0, true));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertStringContainsString('"success":true', (string) $response->getBody());
    }

    public function testPostValidTrustAttorneyRedirectsOnSuccess(): void
    {
        $lpa = $this->createLpa(true);
        $trustIdx = count($lpa->document->replacementAttorneys) - 1;

        $form = $this->createMock(\Application\Form\Lpa\TrustCorporationForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn($this->postDataTrust);
        $this->formElementManager->method('get')->willReturn($form);
        $this->lpaApplicationService->method('setReplacementAttorney')->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataTrust, $lpa, $trustIdx));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
