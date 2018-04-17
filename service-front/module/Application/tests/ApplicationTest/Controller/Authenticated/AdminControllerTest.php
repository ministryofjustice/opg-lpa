<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AdminController;
use Application\Form\Admin\SystemMessageForm;
use Application\Model\Service\Authentication\Identity\User;
use ApplicationTest\Controller\AbstractControllerTest;
use DateTime;
use Mockery;
use Mockery\MockInterface;
use OpgTest\Lpa\DataModel\FixturesData;
use Zend\Form\Element;
use Zend\Http\Response;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\ViewModel;

class AdminControllerTest extends AbstractControllerTest
{
    /**
     * @var AdminController
     */
    private $controller;
    /**
     * @var MockInterface|SystemMessageForm
     */
    private $systemMessageForm;
    private $systemMessagePostData = [
        'message' => 'New system unit test message'
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(AdminController::class);

        $this->systemMessageForm = Mockery::mock(SystemMessageForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Admin\SystemMessageForm'])->andReturn($this->systemMessageForm);

        //  By default set up the user as admin
        $this->user->email->address = 'admin@test.com';
    }

    public function testIndexAction()
    {
        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals('Placeholder page', $result->getVariable('content'));
    }

    public function testOnDispatchEmptyEmail()
    {
        $this->user->email->address = '';

        $response = new Response();
        $event = new MvcEvent();

        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testOnDispatchUserNotAdmin()
    {
        $this->user->email->address = 'unit@test.com';

        $response = new Response();
        $event = new MvcEvent();

        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testOnDispatchUserIsAdminPageNotFound()
    {
        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($this->controller);
        $event->setRouteMatch($routeMatch);
        $response = new Response();
        $event->setResponse($response);
        $this->controller->setEvent($event);

        $this->logger->shouldReceive('info')
            ->withArgs(['Request to ' . AdminController::class, $this->userIdentity->toArray()])->once();
        $routeMatch->shouldReceive('getParam')->withArgs(['action', 'not-found'])->andReturn('not-found')->once();
        $routeMatch->shouldReceive('setParam')->withArgs(['action', 'not-found'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->onDispatch($event);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->getVariable('content'));
    }

    public function testSystemMessageActionGet()
    {
        $messageElement = Mockery::mock(Element::class);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->systemMessageForm->shouldReceive('get')->withArgs(['message'])->andReturn($messageElement)->once();
        $this->cache->shouldReceive('getItem')
            ->withArgs(['system-message'])->andReturn('System unit test message')->once();
        $messageElement->shouldReceive('setValue')->withArgs(['System unit test message'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->systemMessageAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->systemMessageForm, $result->getVariable('form'));
    }

    public function testSystemMessageActionPostInvalid()
    {
        $this->setPostInvalid($this->systemMessageForm, $this->systemMessagePostData);

        /** @var ViewModel $result */
        $result = $this->controller->systemMessageAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->systemMessageForm, $result->getVariable('form'));
    }

    public function testSystemMessageActionPostEmptyMessage()
    {
        $response = new Response();

        $postData = $this->systemMessagePostData;
        $postData['message'] = '';

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($postData)->once();
        $this->systemMessageForm->shouldReceive('setData')->withArgs([$postData])->once();
        $this->systemMessageForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->cache->shouldReceive('removeItem')->withArgs(['system-message'])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->systemMessageAction();

        $this->assertEquals($response, $result);
    }

    public function testSystemMessageActionPostMessage()
    {
        $response = new Response();

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->systemMessagePostData)->once();
        $this->systemMessageForm->shouldReceive('setData')->withArgs([$this->systemMessagePostData])->once();
        $this->systemMessageForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->cache->shouldReceive('setItem')
            ->withArgs(['system-message', $this->systemMessagePostData['message']])->once();
        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->systemMessageAction();

        $this->assertEquals($response, $result);
    }
}
