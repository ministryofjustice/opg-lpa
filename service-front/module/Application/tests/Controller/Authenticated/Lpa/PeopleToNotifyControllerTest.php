<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\PeopleToNotifyController;
use Application\Form\Lpa\BlankMainFlowForm;
use Application\Form\Lpa\PeopleToNotifyForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\Name;
use Opg\Lpa\DataModel\Lpa\Document\NotifiedPerson;
use Opg\Lpa\DataModel\Lpa\Lpa;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class PeopleToNotifyControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|BlankMainFlowForm
     */
    private $blankMainFlowForm;
    /**
     * @var MockInterface|PeopleToNotifyForm
     */
    private $peopleToNotifyForm;
    private $postData = [
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

    public function setUp()
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

    public function testIndexActionGetNoPeopleToNotify()
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

    public function testIndexActionGetMultiplePeopleToNotify()
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

    public function testIndexActionGetFivePeopleToNotify()
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

    public function testIndexActionPostInvalid()
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

    public function testIndexActionPostUpdateMetadata()
    {
        $this->lpa->document->peopleToNotify = [];

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $response = new Response();

        $this->setPostValid($this->blankMainFlowForm);
        $this->metadata->shouldReceive('setPeopleToNotifyConfirmed')->withArgs([$this->lpa])->once();
        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/people-to-notify');
        $this->setRedirectToRoute('lpa/instructions', $this->lpa, $response);

        $result = $controller->indexAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetReuseDetails()
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $response = new Response();

        $this->setSeedLpa($this->lpa, FixturesData::getHwLpa());

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();

        $this->setRedirectToReuseDetails($this->user, $this->lpa, 'lpa/certificate-provider/add', $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGetFivePeopleToNotify()
    {
        while (count($this->lpa->document->peopleToNotify) < 5) {
            $this->lpa->document->peopleToNotify[] = FixturesData::getNotifiedPerson();
        }

        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $response = new Response();

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(true)->once();
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->setRedirectToRoute('lpa/people-to-notify', $this->lpa, $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionGet()
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

    public function testAddActionPostInvalid()
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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to add a notified person for id: 91333263035
     */
    public function testAddActionPostFailed()
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->once();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData, null, 2);
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('addNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson) {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(false)->once();

        $controller->addAction();
    }

    public function testAddActionPostSuccess()
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $response = new Response();

        $this->request->shouldReceive('isXmlHttpRequest')->andReturn(false)->twice();
        $this->setPostValid($this->peopleToNotifyForm, $this->postData, null, 2, 2);
        $this->setFormAction($this->peopleToNotifyForm, $this->lpa, 'lpa/people-to-notify/add');
        $this->peopleToNotifyForm->shouldReceive('setActorData')->once();
        $this->peopleToNotifyForm->shouldReceive('getModelDataFromValidatedForm')->andReturn($this->postData)->once();
        $this->lpaApplicationService->shouldReceive('addNotifiedPerson')
            ->withArgs(function ($lpa, $notifiedPerson) {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(true)->once();
        $this->setMatchedRouteNameHttp($controller, 'lpa/people-to-notify');
        $this->setRedirectToRoute('lpa/instructions', $this->lpa, $response);

        $result = $controller->addAction();

        $this->assertEquals($response, $result);
    }

    public function testAddActionPostMetadata()
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
            ->withArgs(function ($lpa, $notifiedPerson) {
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

    public function testAddActionPostReuseDetails()
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

    public function testEditActionInvalidIndex()
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

    public function testEditActionGet()
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

    public function testEditActionPostInvalid()
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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to update notified person 0 for id: 91333263035
     */
    public function testEditActionPostFailed()
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
            ->withArgs(function ($lpa, $notifiedPerson) {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(false)->once();

        $controller->editAction();
    }

    public function testEditActionPostSuccess()
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
            ->withArgs(function ($lpa, $notifiedPerson) {
                return $lpa->id === $this->lpa->id
                    && $notifiedPerson->name == new Name($this->postData['name'])
                    && $notifiedPerson->address == new Address($this->postData['address']);
            })->andReturn(true)->once();

        /** @var JsonModel $result */
        $result = $controller->editAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals(true, $result->getVariable('success'));
    }

    public function testConfirmDeleteActionInvalidIndex()
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

    public function testConfirmDeleteActionGetJs()
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

    public function testConfirmDeleteActionGetNoJs()
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

    public function testDeleteActionInvalidIndex()
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

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage API client failed to delete notified person 0 for id: 91333263035
     */
    public function testDeleteActionFailed()
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteNotifiedPerson')
            ->withArgs([$this->lpa, $this->lpa->document->peopleToNotify[$idx]->id])->andReturn(false)->once();

        $controller->deleteAction();
    }

    public function testDeleteActionSuccess()
    {
        /** @var PeopleToNotifyController $controller */
        $controller = $this->getController(TestablePeopleToNotifyController::class);

        $response = new Response();

        $idx = 0;

        $routeMatch = $this->getHttpRouteMatch($controller);
        $routeMatch->shouldReceive('getParam')->withArgs(['idx'])->andReturn($idx)->once();
        $this->lpaApplicationService->shouldReceive('deleteNotifiedPerson')
            ->withArgs([$this->lpa, $this->lpa->document->peopleToNotify[$idx]->id])->andReturn(true)->once();
        $this->setRedirectToRoute('lpa/people-to-notify', $this->lpa, $response);

        $result = $controller->deleteAction();

        $this->assertEquals($response, $result);
    }

    /**
     * @return array
     */
    private function getExpectedPeopleToNotifyParams()
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
