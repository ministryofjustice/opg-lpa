<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PrimaryAttorney;

use Application\Form\Lpa\AttorneyForm;
use Application\Handler\Lpa\PrimaryAttorney\PrimaryAttorneyAddHandler;
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
use MakeShared\DataModel\Common\Name as UserName;
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
    private SessionUtility&MockObject $sessionUtility;
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
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->form = $this->createMock(AttorneyForm::class);

        $this->formElementManager->method('get')->willReturn($this->form);

        $this->handler = new PrimaryAttorneyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->applicantService,
            $this->replacementAttorneyCleanup,
            $this->sessionUtility,
        );
    }

    private function createLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->type = Document::LPA_TYPE_PF;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];
        $lpa->document->donor = null;
        $lpa->document->certificateProvider = null;
        $lpa->seed = null;

        return $lpa;
    }

    private function createUserDetails(string $first = 'John', string $last = 'Smith'): User
    {
        $user = new User();
        $user->name = new UserName(['first' => $first, 'last' => $last]);
        $user->dob = null;

        return $user;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        ?User $userDetails = null,
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
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/primary-attorney/add')
            ->withAttribute(RequestAttribute::USER_DETAILS, $userDetails);

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

    public function testGetSetsDisplayReuseSessionUserLinkWhenUserDetailsAvailable(): void
    {
        $user = $this->createUserDetails();
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/lpa/primary-attorney/person-form.twig',
                $this->callback(function (array $params): bool {
                    $this->assertTrue($params['displayReuseSessionUserLink'] ?? false);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], null, $user));
    }

    public function testGetDoesNotSetReuseWhenNoUserDetails(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertArrayNotHasKey('displayReuseSessionUserLink', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], null, null));
    }

    public function testGetSetsSwitchAttorneyTypeRouteForPfLpa(): void
    {
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertEquals(
                        'lpa/primary-attorney/add-trust',
                        $params['switchAttorneyTypeRoute']
                    );
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }

    public function testGetDoesNotSetSwitchAttorneyTypeRouteForHwLpa(): void
    {
        $lpa = $this->createLpa();
        $lpa->document->type = Document::LPA_TYPE_HW;

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertArrayNotHasKey('switchAttorneyTypeRoute', $params);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], $lpa));
    }

    public function testPostInvalidFormRendersForm(): void
    {
        $this->form->method('isValid')->willReturn(false);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Test'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidFormAddsAttorneyAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney'],
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('addPrimaryAttorney')
            ->willReturn(true);

        $this->replacementAttorneyCleanup
            ->expects($this->once())
            ->method('cleanUp');

        $this->applicantService
            ->expects($this->once())
            ->method('cleanUp');

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/how-primary-attorneys-make-decision');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Test'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidFormReturnsJsonForPopup(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Test', 'last' => 'Attorney'],
        ]);

        $this->lpaApplicationService->method('addPrimaryAttorney')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Test'], null, null, true)
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
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

        $this->handler->handle(
            $this->createRequest('POST', ['name-first' => 'Test'])
        );
    }

    public function testPostReuseDetailsBindsUserDataToForm(): void
    {
        $user = $this->createUserDetails('Jane', 'Doe');

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->form
            ->expects($this->never())
            ->method('setData');

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['reuse-details' => '0'], null, $user)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
