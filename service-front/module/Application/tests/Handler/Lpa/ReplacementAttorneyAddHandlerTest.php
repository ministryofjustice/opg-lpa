<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use MakeSharedTest\DataModel\FixturesData;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ReplacementAttorneyAddHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private Metadata&MockObject $metadata;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    /** @var \Application\Form\Lpa\AttorneyForm&MockObject */
    private $form;
    private ReplacementAttorneyAddHandler $handler;

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

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->actorReuseDetailsService->method('getActorsList')->willReturn([]);
        $this->actorReuseDetailsService->method('allowTrust')->willReturn(true);

        $this->form = $this->createMock(\Application\Form\Lpa\AttorneyForm::class);
        $this->formElementManager->method('get')->willReturn($this->form);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->actorReuseDetailsService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );
    }

    private function createLpa(bool $clearAttorneys = false): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [];
        if ($clearAttorneys) {
            $lpa->document->primaryAttorneys = [];
        }
        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        array $queryParams = [],
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();
        $user = $this->createMock(User::class);
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/when-replacement-attorney-step-in');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withQueryParams($queryParams)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/replacement-attorney/add')
            ->withAttribute(RequestAttribute::USER_DETAILS, $user);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWithNoReuseDetailsRendersForm(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/replacement-attorney/person-form.twig', $this->callback(
                fn(array $vars) => isset($vars['form'])
                    && isset($vars['cancelUrl'])
                    && isset($vars['switchAttorneyTypeRoute'])
            ))
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithOneReuseOptionShowsUseMyDetailsLink(): void
    {
        $actorReuseService = $this->createMock(ActorReuseDetailsService::class);
        $actorReuseService->method('getActorReuseDetails')->willReturn([['label' => 'Me', 'data' => []]]);
        $actorReuseService->method('getActorsList')->willReturn([]);

        $handler = new ReplacementAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $actorReuseService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );

        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/replacement-attorney/person-form.twig', $this->callback(
                fn(array $vars) => ($vars['displayReuseSessionUserLink'] ?? false) === true
            ))
            ->willReturn('html');

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithMultipleReuseOptionsRedirectsToReuseDetails(): void
    {
        $actorReuseService = $this->createMock(ActorReuseDetailsService::class);
        $actorReuseService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Me', 'data' => []],
            ['label' => 'Other', 'data' => []],
        ]);
        $actorReuseService->method('getActorsList')->willReturn([]);

        $handler = new ReplacementAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $actorReuseService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('reuse-details', $response->getHeaderLine('Location'));
    }

    public function testGetWithReuseDetailsIndexPreFillsForm(): void
    {
        $reuseData = ['name-first' => 'Unit', 'name-last' => 'Test'];
        $actorReuseService = $this->createMock(ActorReuseDetailsService::class);
        $actorReuseService->method('getActorReuseDetails')->willReturn([
            0 => ['label' => 'Me', 'data' => $reuseData],
            1 => ['label' => 'Other', 'data' => []],
        ]);
        $actorReuseService->method('getActorsList')->willReturn([]);
        $actorReuseService->method('allowTrust')->willReturn(true);

        $handler = new ReplacementAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $actorReuseService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );

        // The form should be pre-filled with the selected reuse data
        $this->form->expects($this->once())->method('setData')->with($reuseData);
        $this->renderer->method('render')->willReturn('html');

        $response = $handler->handle($this->createRequest('GET', [], null, ['reuseDetailsIndex' => '0']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithReuseDetailsIndexShowsBackButton(): void
    {
        $actorReuseService = $this->createMock(ActorReuseDetailsService::class);
        $actorReuseService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Me', 'data' => []],
            ['label' => 'Other', 'data' => []],
        ]);
        $actorReuseService->method('getActorsList')->willReturn([]);
        $actorReuseService->method('allowTrust')->willReturn(true);

        $handler = new ReplacementAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $actorReuseService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );

        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(
                fn(array $vars) => isset($vars['backButtonUrl'])
            ))
            ->willReturn('html');

        // reuseDetailsIndex present — no redirect, shows form with back button
        $response = $handler->handle($this->createRequest('GET', [], null, ['reuseDetailsIndex' => '0']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    private function createHandlerWithTrustDisallowed(): ReplacementAttorneyAddHandler
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([]);
        $reuseService->method('getActorsList')->willReturn([]);
        $reuseService->method('allowTrust')->willReturn(false);

        return new ReplacementAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $reuseService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );
    }

    public function testGetWithNoTrustAllowedRendersFormWithoutTrustRoute(): void
    {
        $handler = $this->createHandlerWithTrustDisallowed();

        $this->renderer->expects($this->once())->method('render')
            ->with('application/authenticated/lpa/replacement-attorney/person-form.twig', $this->callback(
                fn(array $vars) => !isset($vars['switchAttorneyTypeRoute'])
            ))
            ->willReturn('html');

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataHuman));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidThrowsExceptionWhenApiCallFails(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a replacement attorney');

        $this->handler->handle($this->createRequest('POST', $this->postDataHuman));
    }

    public function testPostValidRedirectsOnSuccess(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->expects($this->once())->method('cleanUp');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataHuman));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('when-replacement-attorney-step-in', $response->getHeaderLine('Location'));
    }

    public function testPostValidSetsMetadataWhenNotAlreadyConfirmed(): void
    {
        $lpa = $this->createLpa();
        unset($lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->method('cleanUp');
        $this->metadata->expects($this->once())->method('setReplacementAttorneysConfirmed');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataHuman, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidDoesNotSetMetadataWhenAlreadyConfirmed(): void
    {
        $lpa = $this->createLpa();
        $lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED] = true;

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataHuman);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->method('cleanUp');
        $this->metadata->expects($this->never())->method('setReplacementAttorneysConfirmed');

        $this->handler->handle($this->createRequest('POST', $this->postDataHuman, $lpa));
    }
}
