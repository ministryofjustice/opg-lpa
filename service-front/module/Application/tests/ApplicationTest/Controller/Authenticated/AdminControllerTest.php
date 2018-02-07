<?php

namespace ApplicationTest\Controller\Authenticated;

use Application\Controller\Authenticated\AdminController;
use Application\Form\Admin\PaymentSwitch;
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
use Zend\Mvc\Router\RouteMatch;
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
    /**
     * @var MockInterface|PaymentSwitch
     */
    private $paymentSwitchForm;
    private $paymentSwitchPostData = [
        'percentage' => 50
    ];

    public function setUp()
    {
        $this->controller = parent::controllerSetUp(AdminController::class);

        $this->systemMessageForm = Mockery::mock(SystemMessageForm::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Admin\SystemMessageForm'])->andReturn($this->systemMessageForm);

        $this->paymentSwitchForm = Mockery::mock(PaymentSwitch::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\Admin\PaymentSwitch'])->andReturn($this->paymentSwitchForm);
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
        $response = new Response();
        $event = new MvcEvent();

        $this->user = FixturesData::getUser();
        $this->user->email = ['address' => ''];
        $this->userDetailsSession->user = $this->user;
        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testOnDispatchUserNotAdmin()
    {
        $response = new Response();
        $event = new MvcEvent();

        $this->user = FixturesData::getUser();
        $this->user->email = ['address' => 'notadmin@test.com'];
        $this->userDetailsSession->user = $this->user;
        $this->redirect->shouldReceive('toRoute')->withArgs(['home'])->andReturn($response)->once();

        $result = $this->controller->onDispatch($event);

        $this->assertEquals($response, $result);
    }

    public function testOnDispatchUserIsAdminPageNotFound()
    {
        $event = new MvcEvent();
        $routeMatch = $this->getRouteMatch($this->controller);
        $event->setRouteMatch($routeMatch);
        $this->controller->setEvent($event);

        $this->user = FixturesData::getUser();
        $this->user->email = ['address' => 'admin@test.com'];
        $this->userDetailsSession->user = $this->user;
        $this->userIdentity = new User($this->user->id, 'token', 60 * 60, new DateTime());
        $this->authenticationService->shouldReceive('getIdentity')->andReturn($this->userIdentity)->once();
        $this->controller->setUser($this->userIdentity);
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

    public function testPaymentSwitchActionGet()
    {
        $percentageElement = Mockery::mock(Element::class);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->paymentSwitchForm->shouldReceive('get')->withArgs(['percentage'])->andReturn($percentageElement)->once();
        $this->cache->shouldReceive('getItem')->withArgs(['worldpay-percentage'])->andReturn(100)->once();
        $percentageElement->shouldReceive('setValue')->withArgs([100])->once();

        /** @var ViewModel $result */
        $result = $this->controller->paymentSwitchAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->paymentSwitchForm, $result->getVariable('form'));
        $this->assertEquals(false, $result->getVariable('save'));
    }

    public function testPaymentSwitchActionGetPercentageNonNumeric()
    {
        $percentageElement = Mockery::mock(Element::class);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->paymentSwitchForm->shouldReceive('get')->withArgs(['percentage'])->andReturn($percentageElement)->once();
        $this->cache->shouldReceive('getItem')->withArgs(['worldpay-percentage'])->andReturn('50%')->once();
        $percentageElement->shouldReceive('setValue')->withArgs([0])->once();

        /** @var ViewModel $result */
        $result = $this->controller->paymentSwitchAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->paymentSwitchForm, $result->getVariable('form'));
        $this->assertEquals(false, $result->getVariable('save'));
    }

    public function testPaymentSwitchActionPostInvalid()
    {
        $this->setPostInvalid($this->paymentSwitchForm, $this->paymentSwitchPostData);

        /** @var ViewModel $result */
        $result = $this->controller->paymentSwitchAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->paymentSwitchForm, $result->getVariable('form'));
        $this->assertEquals(false, $result->getVariable('save'));
    }

    public function testPaymentSwitchActionPostPercentage()
    {
        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->paymentSwitchPostData)->once();
        $this->paymentSwitchForm->shouldReceive('setData')->withArgs([$this->paymentSwitchPostData])->once();
        $this->paymentSwitchForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->paymentSwitchForm->shouldReceive('getData')->andReturn($this->paymentSwitchPostData)->once();
        $this->cache->shouldReceive('setItem')
            ->withArgs(['worldpay-percentage', $this->paymentSwitchPostData['percentage']])->once();

        /** @var ViewModel $result */
        $result = $this->controller->paymentSwitchAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->paymentSwitchForm, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('save'));
    }
}
