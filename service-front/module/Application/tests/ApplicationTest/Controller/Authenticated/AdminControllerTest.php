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
        $this->controller = new AdminController();
        parent::controllerSetUp($this->controller);

        $this->systemMessageForm = Mockery::mock(SystemMessageForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Admin\SystemMessageForm')->andReturn($this->systemMessageForm);

        $this->paymentSwitchForm = Mockery::mock(PaymentSwitch::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Admin\PaymentSwitch')->andReturn($this->paymentSwitchForm);
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
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

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
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

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
        $this->logger->shouldReceive('info')->with('Request to ' . AdminController::class, $this->userIdentity->toArray())->once();
        $routeMatch->shouldReceive('getParam')->with('action', 'not-found')->andReturn('not-found')->once();
        $routeMatch->shouldReceive('setParam')->with('action', 'not-found')->once();

        /** @var ViewModel $result */
        $result = $this->controller->onDispatch($event);

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('Page not found', $result->getVariable('content'));
    }

    public function testStatsAction()
    {
        $this->apiClient->shouldReceive('getApiStats')->andReturn($this->getLpasPerUserStats())->once();
        $this->apiClient->shouldReceive('getAuthStats')->andReturn($this->getAuthStats())->once();

        /** @var ViewModel $result */
        $result = $this->controller->statsAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->getLpasPerUserStats()['byLpaCount'], $result->getVariable('api_stats'));
        $this->assertEquals($this->getAuthStats(), $result->getVariable('auth_stats'));
        $this->assertEquals('Admin stats', $result->getVariable('pageTitle'));
    }

    public function testSystemMessageActionGet()
    {
        $messageElement = Mockery::mock(Element::class);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->systemMessageForm->shouldReceive('get')->with('message')->andReturn($messageElement)->once();
        $this->cache->shouldReceive('getItem')->with('system-message')->andReturn('System unit test message')->once();
        $messageElement->shouldReceive('setValue')->with('System unit test message')->once();

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
        $this->systemMessageForm->shouldReceive('setData')->with($postData)->once();
        $this->systemMessageForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->cache->shouldReceive('removeItem')->with('system-message')->once();
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

        $result = $this->controller->systemMessageAction();

        $this->assertEquals($response, $result);
    }

    public function testSystemMessageActionPostMessage()
    {
        $response = new Response();

        $this->request->shouldReceive('isPost')->andReturn(true)->once();
        $this->request->shouldReceive('getPost')->andReturn($this->systemMessagePostData)->once();
        $this->systemMessageForm->shouldReceive('setData')->with($this->systemMessagePostData)->once();
        $this->systemMessageForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->cache->shouldReceive('setItem')->with('system-message', $this->systemMessagePostData['message'])->once();
        $this->redirect->shouldReceive('toRoute')->with('home')->andReturn($response)->once();

        $result = $this->controller->systemMessageAction();

        $this->assertEquals($response, $result);
    }

    public function testPaymentSwitchActionGet()
    {
        $percentageElement = Mockery::mock(Element::class);
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->paymentSwitchForm->shouldReceive('get')->with('percentage')->andReturn($percentageElement)->once();
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn(100)->once();
        $percentageElement->shouldReceive('setValue')->with(100)->once();

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
        $this->paymentSwitchForm->shouldReceive('get')->with('percentage')->andReturn($percentageElement)->once();
        $this->cache->shouldReceive('getItem')->with('worldpay-percentage')->andReturn('50%')->once();
        $percentageElement->shouldReceive('setValue')->with(0)->once();

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
        $this->paymentSwitchForm->shouldReceive('setData')->with($this->paymentSwitchPostData)->once();
        $this->paymentSwitchForm->shouldReceive('isValid')->andReturn(true)->once();
        $this->paymentSwitchForm->shouldReceive('getData')->andReturn($this->paymentSwitchPostData)->once();
        $this->cache->shouldReceive('setItem')->with('worldpay-percentage', $this->paymentSwitchPostData['percentage'])->once();

        /** @var ViewModel $result */
        $result = $this->controller->paymentSwitchAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->paymentSwitchForm, $result->getVariable('form'));
        $this->assertEquals(true, $result->getVariable('save'));
    }

    private function getLpasPerUserStats()
    {
        $stats = [
            'byLpaCount' => [1 => 2]
        ];

        return $stats;
    }

    private function getAuthStats()
    {
        return [
            'total' => 1,
            'activated' => 1,
            'activated-this-month' => 1,
            'deleted' => 1,
        ];
    }
}