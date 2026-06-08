<?php

declare(strict_types=1);

namespace AppTest\Handler\Lpa\PrimaryAttorney;

use App\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddTrustHandler;
use App\Middleware\RequestAttribute;
use App\Model\FormFlowChecker;
use App\Service\Lpa\Applicant as ApplicantService;
use App\Service\Lpa\Application as LpaApplicationService;
use App\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Attorneys\TrustCorporation;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PrimaryAttorneyAddTrustHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private UrlHelper&MockObject $urlHelper;
    private ApplicantService&MockObject $applicantService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    /** @var \Application\Form\Lpa\TrustCorporationForm&MockObject */
    private $form;
    private PrimaryAttorneyAddTrustHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(UrlHelper::class);
        $this->applicantService = $this->createMock(ApplicantService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->form = $this->createMock(\Application\Form\Lpa\TrustCorporationForm::class);

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new PrimaryAttorneyAddTrustHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
        );
    }

    private function makeAddress(): array
    {
        return [
            'address1' => '1 Test Street',
            'address2' => '',
            'address3' => '',
            'postcode' => 'AB1 2CD',
        ];
    }

    private function createLpa(string $type = Document::LPA_TYPE_PF): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->type = $type;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->seed = null;
        return $lpa;
    }

    private function createLpaWithSeed(): Lpa
    {
        $lpa = $this->createLpa();
        $lpa->seed = '88888';
        return $lpa;
    }

    private function createSession(?array $cloneData = null): SessionInterface&MockObject
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('get')
            ->with('clone_data')
            ->willReturn($cloneData);
        return $session;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        array $queryParams = [],
        bool $isXhr = false,
        ?SessionInterface $session = null,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/how-primary-attorneys-make-decision');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $session = $session ?? $this->createMock(SessionInterface::class);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/primary-attorney/add-trust')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $session)
            ->withQueryParams($queryParams);

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersFormForPfLpa(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetRedirectsToAddWhenHwLpa(): void
    {
        $lpa = $this->createLpa(Document::LPA_TYPE_HW);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney/add');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectsToAddWhenTrustAlreadyExistsInPrimary(): void
    {
        $lpa = $this->createLpa();
        $lpa->document->primaryAttorneys = [
            new TrustCorporation([
                'id' => 1,
                'name' => 'Existing Trust',
                'number' => '12345678',
                'address' => $this->makeAddress(),
            ]),
        ];

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/primary-attorney/add');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testRedirectsToAddWhenTrustExistsInReplacement(): void
    {
        $lpa = $this->createLpa();
        $lpa->document->replacementAttorneys = [
            new TrustCorporation([
                'id' => 2,
                'name' => 'Replacement Trust',
                'number' => '87654321',
                'address' => $this->makeAddress(),
            ]),
        ];

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

        $this->lpaApplicationService->expects($this->once())->method('addPrimaryAttorney')->willReturn(true);
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
            $this->createRequest('POST', ['name' => 'Test Trust Corp'], null, [], true)
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

    public function testGetBindsSeedReuseDetailsFromQueryParam(): void
    {
        $lpa = $this->createLpaWithSeed();

        $cloneData = [
            '88888' => [
                'primaryAttorneys' => [[
                    'name' => 'Trust Corp From Seed',
                    'type' => 'trust',
                    'number' => '99999999',
                    'address' => $this->makeAddress(),
                ]],
            ],
        ];
        $session = $this->createSession($cloneData);

        $this->form->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuseDetailsIndex' => 't'], false, $session)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetFetchesSeedDetailsFromApiWhenNotInSession(): void
    {
        $lpa = $this->createLpaWithSeed();

        $session = $this->createMock(SessionInterface::class);
        $session->method('get')->with('clone_data')->willReturn(null);
        $session->expects($this->once())->method('set');

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('getSeedDetails')
            ->with($lpa->id)
            ->willReturn([
                'primaryAttorneys' => [[
                    'name' => 'API Trust Corp',
                    'type' => 'trust',
                    'number' => '11111111',
                    'address' => $this->makeAddress(),
                ]],
            ]);

        $this->form->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuseDetailsIndex' => 't'], false, $session)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetDoesNotBindWhenReuseIndexNotFound(): void
    {
        $lpa = $this->createLpaWithSeed();

        // Clone data exists but has no trust attorney for index 't'
        $session = $this->createSession(['88888' => []]);

        $this->form->expects($this->never())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuseDetailsIndex' => 't'], false, $session)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithNoSeedReturnsEmptyReuseDetails(): void
    {
        $lpa = $this->createLpa();

        $this->form->expects($this->never())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuseDetailsIndex' => 't'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testBackButtonUrlShownWhenCallingUrlInQuery(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertArrayHasKey('backButtonUrl', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle(
            $this->createRequest('GET', [], null, ['callingUrl' => '/lpa/91333263035/primary-attorney/add'])
        );
    }

    public function testNoBackButtonWhenNoCallingUrl(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertArrayNotHasKey('backButtonUrl', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }

    public function testSeedReplacementAttorneyTrustIncluded(): void
    {
        $lpa = $this->createLpaWithSeed();

        $cloneData = [
            '88888' => [
                'replacementAttorneys' => [[
                    'name' => 'Replacement Trust Corp',
                    'type' => 'trust',
                    'number' => '77777777',
                    'address' => $this->makeAddress(),
                ]],
            ],
        ];
        $session = $this->createSession($cloneData);

        $this->form->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuseDetailsIndex' => 't'], false, $session)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSeedHumanAttorneysExcludedFromTrustReuse(): void
    {
        $lpa = $this->createLpaWithSeed();

        $cloneData = [
            '88888' => [
                'primaryAttorneys' => [[
                    'name' => ['first' => 'Human', 'last' => 'Attorney'],
                    'type' => 'human',
                    'address' => $this->makeAddress(),
                ]],
            ],
        ];
        $session = $this->createSession($cloneData);

        $this->form->expects($this->never())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuseDetailsIndex' => 't'], false, $session)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testTemplateParamsContainSwitchRouteAndCancelUrl(): void
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
