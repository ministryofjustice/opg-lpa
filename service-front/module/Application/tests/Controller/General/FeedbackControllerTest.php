<?php

namespace ApplicationTest\Controller\General;

use Application\Controller\General\FeedbackController;
use Application\Form\General\FeedbackForm;
use Application\Model\Service\Feedback\Feedback;
use ApplicationTest\Controller\AbstractControllerTest;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Zend\Http\Header\Referer;
use Zend\Http\Response;
use Zend\View\Model\ViewModel;

class FeedbackControllerTest extends AbstractControllerTest
{
    /**
     * @var MockInterface|FeedbackForm
     */
    private $form;

    private $postData = [
        'rating' => '5',
        'details' => 'Awesome!',
        'email' => 'unit@test.com',
        'phone' => '0123456789',
    ];

    /**
     * @var MockInterface|Feedback
     */
    private $feedbackService;

    public function setUp()
    {
        parent::setUp();

        $this->form = Mockery::mock(Feedback::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\General\FeedbackForm'])->andReturn($this->form);

        $_SERVER['HTTP_USER_AGENT'] = 'UnitTester';
    }

    protected function getController(string $controllerName)
    {
        /** @var FeedbackController $controller */
        $controller = parent::getController($controllerName);

        $this->feedbackService = Mockery::mock(Feedback::class);
        $controller->setFeedbackService($this->feedbackService);

        return $controller;
    }

    public function testSendFeedbackFormInvalid()
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    /**
     * @expectedException        Exception
     * @expectedExceptionMessage Error sending feedback email
     */
    public function testSendFeedbackFail()
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->feedbackService->shouldReceive('add')->andReturn(false)->once();

        $controller->indexAction();
    }

    public function testSendFeedbackSuccess()
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->feedbackService->shouldReceive('add')->andReturn(true)->once();

        $response = new Response();
        $this->redirect->shouldReceive('toRoute')->andReturn($response)->once();

        $result = $controller->indexAction();

        $this->assertEquals($result, $response);
    }

    public function testSendFeedbackFormGetReferer()
    {
        $controller = $this->getController(FeedbackController::class);

        $referer = new Referer();
        $referer->setUri('https://localhost/lpa/3503563157/when-lpa-starts');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->twice();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testSendFeedbackFormNoReferer()
    {
        $controller = $this->getController(FeedbackController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }
}
