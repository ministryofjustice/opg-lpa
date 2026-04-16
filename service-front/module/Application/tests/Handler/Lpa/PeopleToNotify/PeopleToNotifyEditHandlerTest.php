<?php

declare(strict_types=1);

namespace ApplicationTest\Handler\Lpa\PeopleToNotify;

use Application\Handler\Lpa\PeopleToNotify\PeopleToNotifyEditHandler;
use Application\Helper\MvcUrlHelper;
use Application\Middleware\RequestAttribute;
use Application\Model\FormFlowChecker;
use Application\Model\Service\Lpa\Application as LpaApplicationService;
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
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PeopleToNotifyEditHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private LpaApplicationService&MockObject $lpaApplicationService;
    private MvcUrlHelper&MockObject $urlHelper;
    private PeopleToNotifyEditHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->lpaApplicationService = $this->createMock(LpaApplicationService::class);
        $this->urlHelper = $this->createMock(MvcUrlHelper::class);

        $this->handler = new PeopleToNotifyEditHandler(
            $this->renderer,
            $this->formElementManager,
            $this->lpaApplicationService,
            $this->urlHelper,
        );
    }

    private function createLpa(): Lpa
    {
        $lpa = new Lpa();
        $lpa->id = 91333263035;
        $lpa->document = new Document();
        $lpa->document->primaryAttorneys = [];
        $lpa->document->replacementAttorneys = [];

        $np = new NotifiedPerson();
        $np->id = 1;
        $np->name = new Name(['title' => 'Mr', 'first' => 'John', 'last' => 'Notify']);
        $np->address = new Address(['address1' => '1 Road', 'postcode' => 'AB1 2CD']);
        $lpa->document->peopleToNotify = [$np];

        return $lpa;
    }

    private function createRequest(
        string $method,
        Lpa $lpa,
        ?string $idx = '0',
        array $postData = [],
        array $headers = []
    ): ServerRequest {
        $flowChecker = $this->createMock(FormFlowChecker::class);
        $flowChecker->method('nextRoute')->willReturn('lpa/people-to-notify');
        $flowChecker->method('getRouteOptions')->willReturn([]);

        $routeParams = ['lpa-id' => $lpa->id];
        if ($idx !== null) {
            $routeParams['idx'] = $idx;
        }
        $route = new Route('/lpa/:lpa-id/people-to-notify/edit/:idx', new \Application\Middleware\StubMiddleware(), null, 'lpa/people-to-notify/edit');
        $routeResult = RouteResult::fromRoute($route, $routeParams);

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(RequestAttribute::LPA, $lpa)
            ->withAttribute(RequestAttribute::FLOW_CHECKER, $flowChecker)
            ->withAttribute(RequestAttribute::CURRENT_ROUTE_NAME, 'lpa/people-to-notify/edit')
            ->withAttribute(RouteResult::class, $routeResult);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($postData);
        }

        return $request;
    }

    public function testGetRendersEditForm(): void
    {
        $lpa = $this->createLpa();

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->expects($this->once())->method('bind');
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('GET', $lpa));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testGetReturns404ForInvalidIndex(): void
    {
        $lpa = $this->createLpa();

        $response = $this->handler->handle($this->createRequest('GET', $lpa, '99'));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetReturns404ForNullIndex(): void
    {
        $lpa = $this->createLpa();

        $response = $this->handler->handle($this->createRequest('GET', $lpa, null));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testPostValidUpdatesAndRedirects(): void
    {
        $lpa = $this->createLpa();

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Updated', 'last' => 'Person'],
            'address' => ['address1' => '2 Road', 'postcode' => 'CD3 4EF'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->expects($this->once())
            ->method('setNotifiedPerson')
            ->willReturn(true);

        $this->urlHelper->method('generate')->willReturn('/lpa/91333263035/people-to-notify');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, '0', ['name-first' => 'Updated']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testPostValidReturnsJsonWhenPopup(): void
    {
        $lpa = $this->createLpa();

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Updated', 'last' => 'Person'],
            'address' => ['address1' => '2 Road', 'postcode' => 'CD3 4EF'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->method('setNotifiedPerson')->willReturn(true);
        $this->urlHelper->method('generate')->willReturn('/some-url');

        $response = $this->handler->handle(
            $this->createRequest('POST', $lpa, '0', ['name-first' => 'Updated'], ['X-Requested-With' => 'XMLHttpRequest'])
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testPostThrowsOnApiFailure(): void
    {
        $lpa = $this->createLpa();

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(true);
        $form->method('getModelDataFromValidatedForm')->willReturn([
            'name' => ['title' => 'Mr', 'first' => 'Updated', 'last' => 'Person'],
            'address' => ['address1' => '2 Road', 'postcode' => 'CD3 4EF'],
        ]);
        $this->formElementManager->method('get')->willReturn($form);

        $this->lpaApplicationService->method('setNotifiedPerson')->willReturn(false);

        $this->expectException(\RuntimeException::class);

        $this->handler->handle($this->createRequest('POST', $lpa, '0', ['name-first' => 'Updated']));
    }

    public function testPostInvalidRendersForm(): void
    {
        $lpa = $this->createLpa();

        $form = $this->createMock(\Application\Form\Lpa\AbstractActorForm::class);
        $form->method('isValid')->willReturn(false);
        $this->formElementManager->method('get')->willReturn($form);
        $this->urlHelper->method('generate')->willReturn('/some-url');
        $this->renderer->method('render')->willReturn('<html>form</html>');

        $response = $this->handler->handle($this->createRequest('POST', $lpa, '0', []));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
