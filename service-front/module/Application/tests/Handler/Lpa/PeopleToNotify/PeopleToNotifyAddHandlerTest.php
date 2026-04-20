<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyAddHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\ActorReuseDetailsService;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
use Application\Model\Service\Lpa\Metadata;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeShared\DataModel\User\User;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PeopleToNotifyAddHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private Metadata&MockObject $metadata;
    private ActorReuseDetailsService&MockObject $actorReuseDetailsService;
    private PeopleToNotifyAddHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);
        $this->metadata = $this->createMock(Metadata::class);
        $this->actorReuseDetailsService = $this->createMock(ActorReuseDetailsService::class);

        $this->handler = new PeopleToNotifyAddHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
            $this->metadata,
            $this->actorReuseDetailsService,
        );
    }

    private function createLpa(int $numPeople = 0): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];
        $lpa->document->peopleToNotify = [];
        $lpa->metadata = [];

        for ($i = 0; $i < $numPeople; $i++) {
            $np = new NotifiedPerson();
            $np->id = $i + 1;
            $np->name = new Name(['title' => 'Mr', 'first' => 'Person' . $i, 'last' => 'Notify']);
            $np->address = new Address(['address1' => $i . ' Road', 'postcode' => 'AB1 2CD']);
            $lpa->document->peopleToNotify[] = $np;
        }

        return $lpa;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->id = 'user-id-123';
        return $user;
    }

    private function createRequest(
        string $method,
        Lpa $lpa,
        array $postData = [],
        array $queryParams = [],
        array $headers = []
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/people-to-notify');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/people-to-notify/add')
            ->withAttribute(RequestAttribute::USER_DETAILS, $this->createUser())
            ->withQueryParams($queryParams);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRedirectsWhenFivePeopleAlreadyExist(): void
    {
        $lpa = $this->createLpa(5);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetRedirectsToReuseDetailsWhenMultipleOptions(): void
    {
        $lpa = $this->createLpa(0);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Person 1', 'data' => []],
            ['label' => 'Person 2', 'data' => []],
        ]);
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/reuse-details');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testGetShowsFormWithSingleReuseOption(): void
    {
        $lpa = $this->createLpa(0);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([
            ['label' => 'Person 1', 'data' => []],
        ]);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetWithReuseDetailsIndexBindsForm(): void
    {
        $lpa = $this->createLpa(0);

        $reuseData = [
            '-1' => ['label' => 'Person 1', 'data' => ['name-first' => 'Test']],
        ];
        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn($reuseData);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->expects($this->once())->method('bind')->with(['name-first' => 'Test']);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('<html>form</html>');

        $response = $this->handler->handle(
            $this->createRequest('GET', $lpa, [], ['reuseDetailsIndex' => '-1'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidAddsPersonAndRedirects(): void
    {
        $lpa = $this->createLpa(0);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'New', 'last' => 'Person'],
            'address' => ['address1' => '1 Road', 'postcode' => 'AB1 2CD'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->expects($this->once())
            ->method('addNotifiedPerson')
            ->willReturn(true);

        $this->metadata->expects($this->once())->method('setPeopleToNotifyConfirmed');
        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle(
            $this->createRequest('POST', $lpa, ['name-first' => 'New'])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidReturnsJsonWhenPopup(): void
    {
        $lpa = $this->createLpa(0);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'New', 'last' => 'Person'],
            'address' => ['address1' => '1 Road', 'postcode' => 'AB1 2CD'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->method('addNotifiedPerson')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle(
            $this->createRequest('POST', $lpa, ['name-first' => 'New'], [], ['X-Requested-With' => 'XMLHttpRequest'])
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPostThrowsOnApiFailure(): void
    {
        $lpa = $this->createLpa(0);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'New', 'last' => 'Person'],
            'address' => ['address1' => '1 Road', 'postcode' => 'AB1 2CD'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->method('addNotifiedPerson')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle($this->createRequest('POST', $lpa, ['name-first' => 'New']));
    }

    public function testPostInvalidRendersForm(): void
    {
        $lpa = $this->createLpa(0);

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostDoesNotSetMetadataWhenAlreadySet(): void
    {
        $lpa = $this->createLpa(0);
        $lpa->metadata = [Lpa::PEOPLE_TO_NOTIFY_CONFIRMED => true];

        $this->actorReuseDetailsService->method('getActorReuseDetails')->willReturn([]);

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'New', 'last' => 'Person'],
            'address' => ['address1' => '1 Road', 'postcode' => 'AB1 2CD'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->method('addNotifiedPerson')->willReturn(true);
        $this->metadata->expects($this->never())->method('setPeopleToNotifyConfirmed');
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $this->handler->handle($this->createRequest('POST', $lpa, ['name-first' => 'New']));
    }
}
