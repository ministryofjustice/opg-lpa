<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\TrustCorporationForm;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PrimaryAttorneyAddTrustHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ApplicantService&MockObject $applicantService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private SessionUtility&MockObject $sessionUtility;
    private TrustCorporationForm&MockObject $form;
    private PrimaryAttorneyAddTrustHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->applicantService = $this->createMock(ApplicantService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->form = $this->createMock(TrustCorporationForm::class);

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new PrimaryAttorneyAddTrustHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
            $this->sessionUtility,
        );
    }

    private function createLpa(string $type = Document::LPA_TYPE_PF): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->type = $type;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];

        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        bool $isXhr = false,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/how-primary-attorneys-make-decision');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/primary-attorney/add-trust');

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersTrustForm(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetRedirectsToAddWhenTrustNotAllowedForHwLpa(): void
    {
        $lpa = $this->createLpa(Document::LPA_TYPE_HW);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney/add');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostInvalidFormRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name' => 'Test Trust'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidFormAddsTrustAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => 'Test Trust Corp',
            'number' => '12345678',
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('addPrimaryAttorney')
            ->willReturn(true);

        $this->replacementAttorneyCleanup->expects($this->once())->method('cleanUp');
        $this->applicantService->expects($this->once())->method('cleanUp');

        $this->urlHelper->method('generate')->willReturn('/next-route');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name' => 'Test Trust Corp'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidFormReturnsJsonForPopup(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => 'Test Trust Corp',
            'number' => '12345678',
        ]);

        $this->lpaApplicationService->method('addPrimaryAttorney')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name' => 'Test Trust Corp'], null, true)
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPostThrowsExceptionWhenApiAddFails(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => 'Test Trust Corp',
            'number' => '12345678',
        ]);

        $this->lpaApplicationService->method('addPrimaryAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a trust corporation attorney');

        $this->handler->handle(
            $this->createRequest('POST', ['name' => 'Test Trust Corp'])
        );
    }

    public function testTemplateParamsContainSwitchRoute(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/trust-form.twig',
                $this->callback(function (array $params): bool {
                    $this->assertEquals('lpa/primary-attorney/add', $params['switchAttorneyTypeRoute']);
                    $this->assertArrayHasKey('cancelUrl', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }
}
