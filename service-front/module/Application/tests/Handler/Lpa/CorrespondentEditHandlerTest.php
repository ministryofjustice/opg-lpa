<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa;

use Application\Handler\Lpa\CorrespondentEditHandler;
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
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\LongName;
use MakeShared\DataModel\Lpa\Document\Correspondence;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\Donor;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CorrespondentEditHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private MockObject $form;
    private CorrespondentEditHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);
        $this->form = $this->createMock(\Application\Form\Lpa\CorrespondentForm::class);

        $this->formElementManager
            ->method('get')
            ->willReturn($this->form);

        $this->urlHelper
            ->method('generate')
            ->willReturn('/lpa/123/correspondent/edit');

        $this->handler = new CorrespondentEditHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->actorReuseDetailsService,
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->name = new \MakeShared\DataModel\Common\Name(['title' => 'Mr', 'first' => 'Test', 'last' => 'User']);

        return $user;
    }

    private function createLpa(
        ?Correspondence $correspondent = null,
        string $whoIsRegistering = Correspondence::WHO_DONOR
    ): Lpa {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->whoIsRegistering = $whoIsRegistering;

        $donor = new Donor();
        $donor->name = new LongName(['title' => 'Mr', 'first' => 'John', 'last' => 'Doe']);
        $donor->address = new Address(['address1' => '1 Test Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->donor = $donor;

        $lpa->document->correspondent = $correspondent;
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];

        return $lpa;
    }

    private function createCorrespondence(): Correspondence
    {
        $correspondence = new Correspondence();
        $correspondence->who = Correspondence::WHO_OTHER;
        $correspondence->name = new LongName(['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith']);
        $correspondence->address = new Address(['address1' => '2 Test Road', 'postcode' => 'EF3 4GH']);
        $correspondence->contactByPost = true;
        $correspondence->contactInWelsh = false;

        return $correspondence;
    }

    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?Lpa $lpa = null,
        array $queryParams = []
    ): ServerRequest {
        $lpa = $lpa ?? $this->createLpa($this->createCorrespondence());

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/date-check');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $this->createUser())
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/correspondent/edit')
            ->withQueryParams($queryParams);

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetWithMultipleReuseDetailsRedirectsToReuseScreen(): void
    {
        $this->actorReuseDetailsService
            ->method('getCorrespondentReuseDetails')
            ->willReturn([
                ['label' => 'Actor 1', 'data' => []],
                ['label' => 'Actor 2', 'data' => []],
            ]);

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetWithSingleReuseDetailRendersForm(): void
    {
        $this->actorReuseDetailsService
            ->method('getCorrespondentReuseDetails')
            ->willReturn([
                ['label' => 'Actor 1', 'data' => []],
            ]);

        $this->renderer->method('render')->willReturn('<html></html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetEditingExistingCorrespondentRendersFormWithBoundData(): void
    {
        $lpa = $this->createLpa($this->createCorrespondence());

        $this->form
            ->expects($this->once())
            ->method('bind');

        $this->actorReuseDetailsService
            ->method('getCorrespondentReuseDetails')
            ->willReturn([]);

        $this->renderer->method('render')->willReturn('<html></html>');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], $lpa, ['reuse-details' => 'existing-correspondent'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetReturningFromReuseDetailsWithNonEditableDataProcessesDirectly(): void
    {
        $this->actorReuseDetailsService
            ->method('getCorrespondentReuseDetails')
            ->willReturn([
                0 => ['label' => 'John Doe (donor)', 'data' => ['who' => 'donor', 'name-first' => 'John']],
            ]);

        $this->form->method('isEditable')->willReturn(false);
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'who' => 'donor',
            'name' => ['title' => 'Mr', 'first' => 'John', 'last' => 'Doe'],
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setCorrespondent')
            ->willReturn(true);

        $response = $this->handler->handle(
            $this->createRequest('GET', [], null, ['reuseDetailsIndex' => '0'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetReturningFromReuseDetailsWithEditableDataRendersForm(): void
    {
        $this->actorReuseDetailsService
            ->method('getCorrespondentReuseDetails')
            ->willReturn([
                0 => ['label' => 'Other Person', 'data' => ['who' => 'other', 'name-first' => 'Jane']],
            ]);

        $this->form->method('isEditable')->willReturn(true);

        $this->renderer->method('render')->willReturn('<html></html>');

        $response = $this->handler->handle(
            $this->createRequest('GET', [], null, ['reuseDetailsIndex' => '0', 'callingUrl' => '/lpa/123/correspondent/edit'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidFormSavesAndRedirects(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'who' => 'other',
            'name' => ['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith'],
            'address' => ['address1' => '2 Test Road'],
        ]);

        $this->lpaApplicationService
            ->expects($this->once())
            ->method('setCorrespondent')
            ->willReturn(true);

        $response = $this->handler->handle($this->createRequest('POST', [
            'who' => 'other',
            'name-title' => 'Mrs',
            'name-first' => 'Jane',
            'name-last' => 'Smith',
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostInvalidFormRendersFormAgain(): void
    {
        $this->form->method('isValid')->willReturn(false);

        $this->actorReuseDetailsService
            ->method('getCorrespondentReuseDetails')
            ->willReturn([]);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->willReturn('<html></html>');

        $response = $this->handler->handle($this->createRequest('POST', ['name-first' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostApiFailureThrowsException(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'who' => 'other',
            'name' => ['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith'],
        ]);

        $this->lpaApplicationService
            ->method('setCorrespondent')
            ->willReturn(false);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update correspondent');

        $this->handler->handle($this->createRequest('POST', ['name-first' => 'Jane']));
    }

    public function testPostPopupReturnsJsonOnSuccess(): void
    {
        $this->form->method('isValid')->willReturn(true);
        $this->form->method('getModelDataFromValidatedForm')->willReturn([
            'who' => 'other',
            'name' => ['title' => 'Mrs', 'first' => 'Jane', 'last' => 'Smith'],
        ]);

        $this->lpaApplicationService
            ->method('setCorrespondent')
            ->willReturn(true);

        $lpa = $this->createLpa($this->createCorrespondence());

        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/date-check');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader('X-Requested-With', 'XMLHttpRequest')
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::USER_DETAILS, $this->createUser())
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/correspondent/edit')
            ->withParsedBody(['name-first' => 'Jane']);

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
    }
}
