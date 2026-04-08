<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\ReplacementAttorneyAddTrustHandler;
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

class ReplacementAttorneyAddTrustHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private Metadata&MockObject $metadata;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private $form;
    private ReplacementAttorneyAddTrustHandler $handler;

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
        $this->metadata = $this->createMock(Metadata::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->actorReuseDetailsService->method('getActorsList')->willReturn([]);
        $this->actorReuseDetailsService->method('allowTrust')->willReturn(true);

        $this->form = $this->createMock(\Application\Form\Lpa\TrustCorporationForm::class);
        $this->formElementManager->method('get')->willReturn($this->form);

        $this->urlHelper->method('generate')->willReturnCallback(
            fn(string $route, array $params = [], array $options = []) =>
                '/lpa/' . ($params['lpa-id'] ?? '') . '/' . $route
        );

        $this->handler = new ReplacementAttorneyAddTrustHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->actorReuseDetailsService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );
    }

    private function createLpa(): Lpa
    {
        $lpa = FixturesData::getPfLpa();
        $lpa->document->replacementAttorneys = [];
        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();
        $user = $this->createMock(User::class);
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/when-replacement-attorney-step-in');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/replacement-attorney/add-trust')
            ->withAttribute(RequestAttribute::USER_DETAILS, $user);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    private function createHandlerWithTrustDisallowed(): ReplacementAttorneyAddTrustHandler
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([]);
        $reuseService->method('getActorsList')->willReturn([]);
        $reuseService->method('allowTrust')->willReturn(false);

        return new ReplacementAttorneyAddTrustHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $reuseService,
            $this->metadata,
            $this->replacementAttorneyCleanup,
        );
    }

    public function testGetRedirectsToAddHumanWhenTrustNotAllowed(): void
    {
        $handler = $this->createHandlerWithTrustDisallowed();

        $response = $handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('replacement-attorney/add', $response->getHeaderLine('Location'));
    }

    public function testGetRendersForm(): void
    {
        $this->renderer->expects($this->once())->method('render')
            ->with(
                'application/authenticated/lpa/replacement-attorney/trust-form.twig',
                $this->callback(
                    fn(array $vars) => isset($vars['form'])
                        && isset($vars['cancelUrl'])
                        && $vars['switchAttorneyTypeRoute'] === 'lpa/replacement-attorney/add'
                )
            )
            ->willReturn('html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataTrust));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidThrowsExceptionWhenApiCallFails(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataTrust);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add trust corporation replacement attorney');

        $this->handler->handle($this->createRequest('POST', $this->postDataTrust));
    }

    public function testPostValidRedirectsOnSuccess(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataTrust);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->expects($this->once())->method('cleanUp');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataTrust));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('when-replacement-attorney-step-in', $response->getHeaderLine('Location'));
    }

    public function testPostValidSetsMetadataWhenNotAlreadyConfirmed(): void
    {
        $lpa = $this->createLpa();
        unset($lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED]);

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataTrust);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->method('cleanUp');
        $this->metadata->expects($this->once())->method('setReplacementAttorneysConfirmed');

        $response = $this->handler->handle($this->createRequest('POST', $this->postDataTrust, $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidDoesNotSetMetadataWhenAlreadyConfirmed(): void
    {
        $lpa = $this->createLpa();
        $lpa->metadata[Lpa::REPLACEMENT_ATTORNEYS_CONFIRMED] = true;

        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn($this->postDataTrust);
        $this->lpaApplicationService->method('addReplacementAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->method('cleanUp');
        $this->metadata->expects($this->never())->method('setReplacementAttorneysConfirmed');

        $this->handler->handle($this->createRequest('POST', $this->postDataTrust, $lpa));
    }
}
