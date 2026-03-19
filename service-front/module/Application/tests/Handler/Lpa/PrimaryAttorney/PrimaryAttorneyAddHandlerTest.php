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
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Dob;
use MakeShared\DataModel\Common\Name as UserName;
use MakeShared\DataModel\Lpa\Document\Attorneys\Human;
use MakeShared\DataModel\Lpa\Document\CertificateProvider;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
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

    private function makeAddress(): array
    {
        return [
            'address1' => '1 Test Street',
            'address2' => '',
            'address3' => '',
            'postcode' => 'AB1 2CD',
        ];
    }

    private function makeName(string $first, string $last): array
    {
        return ['title' => 'Mr', 'first' => $first, 'last' => $last];
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

    private function createLpaWithActors(): Lpa
    {
        $lpa = $this->createEmptyLpa();
        $lpa->document->donor = new Donor([
            'name' => $this->makeName('Donor', 'Person'),
            'address' => $this->makeAddress(),
        ]);
        $lpa->document->certificateProvider = new CertificateProvider([
            'name' => $this->makeName('Cert', 'Provider'),
            'address' => $this->makeAddress(),
        ]);
        $lpa->document->primaryAttorneys = [
            new Human([
                'id' => 1,
                'name' => $this->makeName('Primary', 'One'),
                'address' => $this->makeAddress(),
                'dob' => ['date' => '1980-01-01T00:00:00.000000+0000'],
            ]),
        ];
        return $lpa;
    }

    private function createLpaWithSeed(): Lpa
    {
        $lpa = $this->createEmptyLpa();
        $lpa->seed = '99999';
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
            ->withAttribute(RequestAttribute::CURRENT_ROUTE, 'lpa/primary-attorney/add')
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

    public function testGetSetsDisplayReuseSessionUserLinkWhenOneReuseOption(): void
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
                    $this->assertEquals('lpa/primary-attorney/add-trust', $params['switchAttorneyTypeRoute']);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest());
    }

    public function testGetDoesNotSetSwitchAttorneyTypeRouteForHwLpa(): void
    {
        $lpa = $this->createEmptyLpa(Document::LPA_TYPE_HW);
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
        $this->renderer->expects($this->once())->method('render')->willReturn('rendered-html');

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

        $this->lpaApplicationService->expects($this->once())->method('addPrimaryAttorney')->willReturn(true);
        $this->replacementAttorneyCleanup->expects($this->once())->method('cleanUp');
        $this->applicantService->expects($this->once())->method('cleanUp');

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
            $this->createRequest('POST', ['name-first' => 'Test'], null, null, [], true)
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

        $this->handler->handle($this->createRequest('POST', ['name-first' => 'Test']));
    }

    public function testPostReuseDetailsBindsUserDataToForm(): void
    {
        $user = $this->createUserDetails('Jane', 'Doe');

        $this->form->expects($this->once())->method('bind');
        $this->form->expects($this->never())->method('setData');

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('POST', ['reuse-details' => '0'], null, $user)
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetRedirectsToReuseDetailsWhenMultipleOptionsFromSeed(): void
    {
        $lpa = $this->createLpaWithSeed();
        $user = $this->createUserDetails();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'donor' => [
                'name' => ['first' => 'Seed', 'last' => 'Donor'],
                'address' => $this->makeAddress(),
            ],
            'certificateProvider' => [
                'name' => ['first' => 'Seed', 'last' => 'CertProv'],
                'address' => $this->makeAddress(),
            ],
        ]);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/reuse-details');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, $user));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetBindsReuseDetailsFromQueryParam(): void
    {
        $lpa = $this->createLpaWithSeed();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'donor' => [
                'name' => ['first' => 'Seed', 'last' => 'Donor'],
                'address' => $this->makeAddress(),
            ],
        ]);

        $this->form->expects($this->once())->method('bind');
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, null, ['reuseDetailsIndex' => '0'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetFetchesSeedDetailsFromApiWhenNotInSession(): void
    {
        $lpa = $this->createLpaWithSeed();
        $user = $this->createUserDetails();

        $this->sessionUtility->method('getFromMvc')->willReturn(null);

        $this->lpaApplicationService
            ->expects($this->atLeastOnce())
            ->method('getSeedDetails')
            ->with($lpa->id)
            ->willReturn([
                'donor' => [
                    'name' => ['first' => 'Seed', 'last' => 'Donor'],
                    'address' => $this->makeAddress(),
                ],
            ]);

        $this->sessionUtility
            ->expects($this->atLeastOnce())
            ->method('setInMvc')
            ->with('clone', '99999', $this->isType('array'));

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/reuse-details');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, $user));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testSeedTrustEntryIncluded(): void
    {
        $lpa = $this->createLpaWithSeed();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'primaryAttorneys' => [[
                'name' => 'Trust Corp Ltd',
                'type' => 'trust',
                'number' => '12345',
                'address' => $this->makeAddress(),
            ]],
        ]);

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, null));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSeedPeopleToNotifyIncluded(): void
    {
        $lpa = $this->createLpaWithSeed();
        $user = $this->createUserDetails();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'peopleToNotify' => [[
                'name' => ['first' => 'Notify', 'last' => 'Person'],
                'address' => $this->makeAddress(),
            ]],
        ]);

        $this->urlHelper->method('generate')->willReturn('/reuse-details');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, $user));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testSeedCorrespondentOtherTypeIncluded(): void
    {
        $lpa = $this->createLpaWithSeed();
        $user = $this->createUserDetails();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'correspondent' => [
                'who' => 'other',
                'name' => ['first' => 'Corr', 'last' => 'Person'],
                'address' => $this->makeAddress(),
            ],
        ]);

        $this->urlHelper->method('generate')->willReturn('/reuse-details');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, $user));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testSeedCorrespondentNonOtherTypeExcluded(): void
    {
        $lpa = $this->createLpaWithSeed();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'correspondent' => [
                'who' => 'donor',
                'name' => ['first' => 'Corr', 'last' => 'Person'],
                'address' => $this->makeAddress(),
            ],
        ]);

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, null));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testUserDetailsNotAddedWhenAlreadyUsedAsActor(): void
    {
        $lpa = $this->createLpaWithActors();
        $user = $this->createUserDetails('Donor', 'Person');

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

        $this->handler->handle($this->createRequest('GET', [], $lpa, $user));
    }

    public function testUserDetailsIncludeDobWhenPresent(): void
    {
        $user = new User();
        $user->name = new UserName(['first' => 'John', 'last' => 'Smith']);
        $user->dob = new Dob(['date' => '1985-06-15T00:00:00.000000+0000']);

        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                $this->anything(),
                $this->callback(function (array $params): bool {
                    $this->assertTrue($params['displayReuseSessionUserLink'] ?? false);
                    return true;
                })
            )
            ->willReturn('rendered-html');

        $this->handler->handle($this->createRequest('GET', [], null, $user));
    }

    public function testBackButtonShownWhenMultipleReuseOptionsOnPost(): void
    {
        $lpa = $this->createLpaWithSeed();
        $user = $this->createUserDetails();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'donor' => [
                'name' => ['first' => 'Seed', 'last' => 'Donor'],
                'address' => $this->makeAddress(),
            ],
        ]);

        $this->form->method('isValid')->willReturn(false);
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

        $this->handler->handle($this->createRequest('POST', ['name-first' => 'Test'], $lpa, $user));
    }

    public function testSeedTrustExcludedForHwLpa(): void
    {
        $lpa = $this->createLpaWithSeed();
        $lpa->document->type = Document::LPA_TYPE_HW;

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'primaryAttorneys' => [[
                'name' => 'Trust Corp',
                'type' => 'trust',
                'number' => '12345',
                'address' => $this->makeAddress(),
            ]],
        ]);

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, null));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSeedReplacementAttorneysIncluded(): void
    {
        $lpa = $this->createLpaWithSeed();
        $user = $this->createUserDetails();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'replacementAttorneys' => [[
                'name' => ['first' => 'Repl', 'last' => 'Attorney'],
                'type' => 'human',
                'address' => $this->makeAddress(),
            ]],
        ]);

        $this->urlHelper->method('generate')->willReturn('/reuse-details');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, $user));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testFlattenDataHandlesDobArray(): void
    {
        $lpa = $this->createLpaWithSeed();

        $this->sessionUtility->method('getFromMvc')->willReturn([
            'donor' => [
                'name' => ['first' => 'Seed', 'last' => 'Donor'],
                'address' => $this->makeAddress(),
                'dob' => ['date' => '1990-03-15T00:00:00.000000+0000'],
            ],
        ]);

        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('rendered-html');

        $response = $this->handler->handle($this->createRequest('GET', [], $lpa, null));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
