<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\AttorneyForm;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Applicant as ApplicantService;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\ReplacementAttorneyCleanup;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class PrimaryAttorneyAddHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ApplicantService&MockObject $applicantService;
    private ReplacementAttorneyCleanup&MockObject $replacementAttorneyCleanup;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private AttorneyForm&MockObject $form;
    private PrimaryAttorneyAddHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->applicantService = $this->createMock(ApplicantService::class);
        $this->replacementAttorneyCleanup = $this->createMock(ReplacementAttorneyCleanup::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->actorReuseDetailsService->method('getActorsList')->willReturn([]);
        $this->form = $this->createMock(AttorneyForm::class);

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = $this->makeHandler($this->actorReuseDetailsService);
    }

    private function makeHandler(ActorReuseDetailsService $reuseService): PrimaryAttorneyAddHandler
    {
        return new PrimaryAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
            $reuseService,
        );
    }

    private function createEmptyLpa(string $type = Document::LPA_TYPE_PF): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->type = $type;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];
        $lpa->document->donor = null;
        $lpa->document->certificateProvider = null;
        $lpa->seed = null;
        return $lpa;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        ?User $userDetails = null,
        array $queryParams = [],
        bool $isXhr = false,
    ): ServerRequest {
        $lpa = $lpa ?? $this->createEmptyLpa();

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/how-primary-attorneys-make-decision');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/primary-attorney/add')
            ->withAttribute(RequestAttribute::USER_DETAILS, $userDetails)
            ->withUri(new Uri('/lpa/91333263035/primary-attorney/add'))
            ->withQueryParams($queryParams);

        if ($isXhr) {
            $request = $request->withHeader('X-Requested-With', 'XMLHttpRequest');
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersForm(): void
    {
        $this->renderer->method('render')->willReturn('rendered-html');
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testGetWithNoReuseOptionsDoesNotSetDisplayReuseSessionUserLink(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(function (array $params): bool {
                $this->assertArrayNotHasKey('displayReuseSessionUserLink', $params);
                return true;
            }))
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }

    public function testGetSetsDisplayReuseSessionUserLinkWhenOneReuseOption(): void
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            ['label' => 'John Smith (myself)', 'data' => []],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        $user = $this->createMock(User::class);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(function (array $params): bool {
                $this->assertTrue($params['displayReuseSessionUserLink'] ?? false);
                return true;
            }))
            ->willReturn('rendered-html');

        $this->makeHandler($reuseService)->handle($this->createRequest('GET', [], null, $user));
    }

    public function testGetWithMultipleReuseOptionsRedirectsToReuseDetailsScreen(): void
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Amy Wheeler (was a primary attorney)', 'data' => []],
            ['label' => 'David Wheeler (was a primary attorney)', 'data' => []],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        $user = $this->createMock(User::class);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/reuse-details');

        $response = $this->makeHandler($reuseService)->handle($this->createRequest('GET', [], null, $user));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertStringContainsString('reuse-details', $response->getHeaderLine('Location'));
    }

    public function testGetWithMultipleReuseOptionsDoesNotRedirectWhenReuseIndexPresent(): void
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Amy Wheeler', 'data' => []],
            ['label' => 'David Wheeler', 'data' => []],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        $user = $this->createMock(User::class);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->expects($this->once())->method('render')->willReturn('html');

        // Returning from reuse-details screen — must NOT redirect again
        $response = $this->makeHandler($reuseService)->handle(
            $this->createRequest('GET', [], null, $user, ['reuseDetailsIndex' => '0'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithReuseDetailsIndexBindsCorrectEntryFromService(): void
    {
        $amyData   = ['name-first' => 'Amy',   'name-last' => 'Wheeler'];
        $davidData = ['name-first' => 'David', 'name-last' => 'Wheeler'];

        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            0 => ['label' => 'Amy Wheeler (was a primary attorney)',   'data' => $amyData],
            1 => ['label' => 'David Wheeler (was a primary attorney)', 'data' => $davidData],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        // bind() must be called with Amy's data (index 0), not David's (index 1)
        $this->form->expects($this->once())->method('bind')->with($amyData);

        $user = $this->createMock(User::class);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->method('render')->willReturn('html');

        $this->makeHandler($reuseService)->handle(
            $this->createRequest('GET', [], null, $user, ['reuseDetailsIndex' => '0'])
        );
    }

    public function testGetWithReuseDetailsIndexOutOfRangeDoesNotBindForm(): void
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            0 => ['label' => 'Amy Wheeler', 'data' => ['name-first' => 'Amy']],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        $this->form->expects($this->never())->method('bind');

        $user = $this->createMock(User::class);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->method('render')->willReturn('html');

        $this->makeHandler($reuseService)->handle(
            $this->createRequest('GET', [], null, $user, ['reuseDetailsIndex' => '99'])
        );
    }

    public function testGetSetsSwitchAttorneyTypeRouteForPfLpa(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(function (array $params): bool {
                $this->assertEquals('lpa/primary-attorney/add-trust', $params['switchAttorneyTypeRoute']);
                return true;
            }))
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }

    public function testGetDoesNotSetSwitchAttorneyTypeRouteForHwLpa(): void
    {
        $lpa = $this->createEmptyLpa(Document::LPA_TYPE_HW);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(function (array $params): bool {
                $this->assertArrayNotHasKey('switchAttorneyTypeRoute', $params);
                return true;
            }))
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testGetBackButtonUrlSetWhenMultipleReuseOptions(): void
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Amy Wheeler', 'data' => []],
            ['label' => 'David Wheeler', 'data' => []],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        $user = $this->createMock(User::class);
        $this->form->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')
            ->with($this->anything(), $this->callback(function (array $params): bool {
                $this->assertArrayHasKey('backButtonUrl', $params);
                return true;
            }))
            ->willReturn('rendered-html');

        // POST with invalid form so it falls through to render — back button should be set
        $this->makeHandler($reuseService)->handle(
            $this->createRequest('POST', ['name-first' => 'Test'], null, $user)
        );
    }

    public function testPostInvalidFormRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('POST', ['name-first' => 'Test']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidFormAddsAttorneyAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney'],
        ]);
        $this->lpaApplicationService->expects($this->once())->method('addPrimaryAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->expects($this->once())->method('cleanUp');
        $this->applicantService->expects($this->once())->method('cleanUp');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/how-primary-attorneys-make-decision');

        $response = $this->handler->handle($this->createRequest('POST', ['name-first' => 'Test']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidFormReturnsJsonForXhrRequest(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney'],
        ]);
        $this->lpaApplicationService->method('addPrimaryAttorney')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Test'], null, null, [], true)
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $body = json_decode($response->getBody()->__toString(), true);
        $this->assertTrue($body['success']);
    }

    public function testPostThrowsExceptionWhenApiAddFails(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney'],
        ]);
        $this->lpaApplicationService->method('addPrimaryAttorney')->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a primary attorney for id: 91333263035');

        $this->handler->handle($this->createRequest('POST', ['name-first' => 'Test']));
    }

    public function testPostWithSingleReuseOptionBindsDataWithoutValidating(): void
    {
        $reuseData = ['name-first' => 'John', 'name-last' => 'Smith'];
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            0 => ['label' => 'John Smith (myself)', 'data' => $reuseData],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        $user = $this->createMock(User::class);
        // bind() should be called with reuse data; addPrimaryAttorney must NOT be called
        $this->form->expects($this->once())->method('bind')->with($reuseData);
        $this->lpaApplicationService->expects($this->never())->method('addPrimaryAttorney');
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->method('render')->willReturn('html');

        $this->makeHandler($reuseService)->handle(
            $this->createRequest('POST', ['reuse-details' => '0'], null, $user)
        );
    }

    public function testPostReuseWithMultipleOptionsDoesNotAutoBindOnZero(): void
    {
        $reuseService = $this->createMock(ActorReuseDetailsService::class);
        $reuseService->method('getActorReuseDetails')->willReturn([
            0 => ['label' => 'Amy Wheeler', 'data' => ['name-first' => 'Amy']],
            1 => ['label' => 'David Wheeler', 'data' => ['name-first' => 'David']],
        ]);
        $reuseService->method('getActorsList')->willReturn([]);

        // With multiple options, reuse-details=0 in POST should NOT auto-bind;
        // the normal setData/isValid path should run instead
        $this->form->expects($this->never())->method('bind');
        $this->form->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/url');
        $this->renderer->method('render')->willReturn('html');

        $this->makeHandler($reuseService)->handle(
            $this->createRequest('POST', ['reuse-details' => '0'])
        );
    }
}
