<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PeopleToNotifyController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\PeopleToNotifyForm;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use MakeShared\DataModel\Common\Address;
use MakeShared\DataModel\Common\Name;
use MakeShared\DataModel\Lpa\Document\NotifiedPerson;
use MakeShared\DataModel\Lpa\Lpa;
use MakeSharedTest\DataModel\FixturesData;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Laminas\Http\Response;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

final class PeopleToNotifyControllerTest extends AbstractControllerTestCase
{
    private MockInterface|BlankMainFlowForm $blankMainFlowForm;
    private MockInterface|PeopleToNotifyForm $peopleToNotifyForm;
    private array $postData = [
        'name' => [
            'title' => 'Miss',
            'first' => 'Unit',
            'last' => 'Test'
        ],
        'address' => [
            'address1' => 'Address line 1',
            'address2' => 'Address line 2',
            'address3' => 'Address line 3',
            'postcode' => 'PO5 3DE'
        ]
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->lpa->document->peopleToNotify = [
            new NotifiedPerson([
                "id" => 1,
                "name" => [
                    "title" => "Miss",
                    "first" => "Elizabeth",
                    "last" => "Stout",
                ],
                "address" => [
                    "address1" => "747 Station Road",
                    "address2" => "Clayton le Moors",
                    "address3" => "Lancashire, England",
                    "postcode" => "WN8A 8AQ",
                ],
            ]),
        ];

        $this->blankMainFlowForm = Mockery::mock(BlankMainFlowForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\BlankMainFlowForm', ['lpa' => $this->lpa]])
            ->andReturn($this->blankMainFlowForm);

        $this->peopleToNotifyForm = Mockery::mock(PeopleToNotifyForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Lpa\PeopleToNotifyForm'])->andReturn($this->peopleToNotifyForm);
    }

    public function testIndexActionGetNoPeopleToNotify(): void
    {
        $this->lpa->document->peopleToNotify = [];

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($controller, 'lpa/people-to-notify');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals([], $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionGetMultiplePeopleToNotify(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($controller, 'lpa/people-to-notify');

        $expectedPeopleToNotifyParams = $this->getExpectedPeopleToNotifyParams();

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($expectedPeopleToNotifyParams, $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionGetFivePeopleToNotify(): void
    {
        while (count($this->lpa->document->peopleToNotify) < 5) {
            $this->lpa->document->peopleToNotify[] = FixturesData::getNotifiedPerson();
        }

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setMatchedRouteName($controller, 'lpa/people-to-notify');

        $expectedPeopleToNotifyParams = $this->getExpectedPeopleToNotifyParams();

        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->never();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals($expectedPeopleToNotifyParams, $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionPostInvalid(): void
    {
        $this->lpa->document->peopleToNotify = [];

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->setPostInvalid($this->blankMainFlowForm);
        $this->setMatchedRouteName($controller, 'lpa/people-to-notify');
        $this->url->shouldReceive('fromRoute')
            ->withArgs(['lpa/people-to-notify/add', ['lpa-id' => $this->lpa->id]])
            ->andReturn('lpa/people-to-notify/add?lpa-id=' . $this->lpa->id)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->blankMainFlowForm, $result->getVariable('form'));
        $this->assertEquals([], $result->getVariable('peopleToNotify'));
    }

    public function testIndexActionPostUpdateMetadata(): void
    {
        $this->lpa->document->peopleToNotify = [];

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->setPostValid($this->blankMainFlowForm);
        $this->metadata->shouldReceive('setPeopleToNotifyConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/people-to-notify');

        $result = $controller->indexAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/instructions', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionGetReuseDetails(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/certificate-provider/add');

        $result = $controller->addAction();

        $this->assertInstanceOf(ResponseInterface::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString(
            'lpa/91333263035/reuse-details?',
            $result->getHeaderLine('Location')
        );
    }

    public function testAddActionGetFivePeopleToNotify(): void
    {
        while (count($this->lpa->document->peopleToNotify) < 5) {
            $this->lpa->document->peopleToNotify[] = FixturesData::getNotifiedPerson();
        }

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/people-to-notify', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionGet(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->twice();
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/people-to-notify/form.twig', $result->getTemplate());
        $this->assertEquals($this->peopleToNotifyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testAddActionPostInvalid(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostInvalid($this->peopleToNotifyForm, [], null, 2);
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/people-to-notify/form.twig', $result->getTemplate());
        $this->assertEquals($this->peopleToNotifyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testAddActionPostFailed(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData, null, 2);
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('addNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson): bool {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to add a notified person for id: 91333263035');

        $controller->addAction();
    }

    public function testAddActionPostSuccess(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData, null, 2, 2);
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('addNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson): bool {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(true)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/people-to-notify');

        $result = $controller->addAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/instructions', $result->getHeaders()->get('Location')->getUri());
    }

    public function testAddActionPostMetadata(): void
    {
        unset($this->lpa->metadata[Lpa::PEOPLE_TO_NOTIFY_CONFIRMED]);

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData, null, 2, 1);
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('addNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson): bool {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(true)->once();
        $this->metadata->shouldReceive('setPeopleToNotifyConfirmed')->withArgs([$this->lpa])->once();

        /** @var JsonModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testAddActionPostReuseDetails(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->setSeedLpa($this->lpa, FixturesData::getPfLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->request->shouldReceive('isPost')->andReturn(true)->twice();
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add', 2);
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');
        $routeMatch = $this->setReuseDetails($controller, $this->peopleToNotifyForm, $this->user, 'attorney');
        $this->setMatchedRouteName($controller, 'lpa/people-to-notify/add', $routeMatch);
        $routeMatch->shouldReceive('getParam')->withArgs(['callingUrl'])
            ->andReturn("http://localhost/lpa/{$this->lpa->id}/lpa/people-to-notify/add")->once();

        /** @var ViewModel $result */
        $result = $controller->addAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/people-to-notify/form.twig', $result->getTemplate());
        $this->assertEquals($this->peopleToNotifyForm, $result->getVariable('form'));
        $this->assertEquals("http://localhost/lpa/{$this->lpa->id}/lpa/people-to-notify/add", $result->backButtonUrl);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionInvalidIndex(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $controller->setEvent($event);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testEditActionGet(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setFormActionIndex($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/edit', $idx);
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('bind')
            ->withArgs([$this->lpa->document->peopleToNotify[$idx]->flatten()])->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/people-to-notify/form.twig', $result->getTemplate());
        $this->assertEquals($this->peopleToNotifyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostInvalid(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostInvalid($this->peopleToNotifyForm);
        $this->setFormActionIndex($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/edit', $idx);
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/authenticated/lpa/people-to-notify/form.twig', $result->getTemplate());
        $this->assertEquals($this->peopleToNotifyForm, $result->getVariable('form'));
        $this->assertEquals($cancelUrl, $result->cancelUrl);
    }

    public function testEditActionPostFailed(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData);
        $this->setFormActionIndex($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/edit', $idx);
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson): bool {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to update notified person 0 for id: 91333263035');

        $controller->editAction();
    }

    public function testEditActionPostSuccess(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->twice();
        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData);
        $this->setFormActionIndex($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/edit', $idx);
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('setNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson): bool {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testConfirmDeleteActionInvalidIndex(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $controller->setEvent($event);

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testConfirmDeleteActionGetJs(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->peopleToNotify[$idx]->name, $result->personName);
        $this->assertEquals($this->lpa->document->peopleToNotify[$idx]->address, $result->personAddress);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(true, $result->isPopup);
    }

    public function testConfirmDeleteActionGetNoJs(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $this->params->shouldReceive('fromRoute')->withArgs(['idx'])->andReturn($idx)->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $deleteRoute = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify/delete', ['idx' => $idx]);
        $cancelUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify');

        /** @var ViewModel $result */
        $result = $controller->confirmDeleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($deleteRoute, $result->deleteRoute);
        $this->assertEquals($this->lpa->document->peopleToNotify[$idx]->name, $result->personName);
        $this->assertEquals($this->lpa->document->peopleToNotify[$idx]->address, $result->personAddress);
        $this->assertEquals($cancelUrl, $result->cancelUrl);
        $this->assertEquals(false, $result->isPopup);
    }

    public function testDeleteActionInvalidIndex(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($controller);
        $event->setRouteMatch($routeMatch);
        $response = Mockery::mock(Response::class);
        $event->setResponse($response);
        $controller->setEvent($event);

        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn(-1)->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();
        $response->shouldReceive('setStatusCode')->withArgs([404])->once();

        /** @var ViewModel $result */
        $result = $controller->deleteAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->content);
    }

    public function testDeleteActionFailed(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteNotifiedPerson')
            ->withArgs([$this->lpa, $this->lpa->document->peopleToNotify[$idx]->id])->andReturn(false)->once();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('API client failed to delete notified person 0 for id: 91333263035');

        $controller->deleteAction();
    }

    public function testDeleteActionSuccess(): void
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteNotifiedPerson')
            ->withArgs([$this->lpa, $this->lpa->document->peopleToNotify[$idx]->id])->andReturn(true)->once();

        $result = $controller->deleteAction();

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertStringContainsString('/lpa/91333263035/people-to-notify', $result->getHeaders()->get('Location')->getUri());
    }

    private function getExpectedPeopleToNotifyParams(): array
    {
        $expectedPeopleToNotifyParams = [];
        foreach ($this->lpa->document->peopleToNotify as $idx => $peopleToNotify) {
            $editUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify/edit', ['idx' => $idx]);
            $confirmDeleteUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify/confirm-delete', ['idx' => $idx]);
            $deleteUrl = $this->setUrlFromRoute($this->lpa, 'lpa/people-to-notify/delete', ['idx' => $idx]);

            $expectedPeopleToNotifyParams[] = [
                'notifiedPerson' => [
                    'name' => $peopleToNotify->name,
                    'address' => $peopleToNotify->address
                ],
                'editRoute' => $editUrl,
                'confirmDeleteRoute' => $confirmDeleteUrl,
                'deleteRoute' => $deleteUrl
            ];
        }
        return $expectedPeopleToNotifyParams;
    }
}
