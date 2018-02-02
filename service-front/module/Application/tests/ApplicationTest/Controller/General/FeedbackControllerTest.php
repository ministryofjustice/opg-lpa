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
use Zend\View\Model\ViewModel;

class FeedbackControllerTest extends AbstractControllerTest
{
    /**
     * @var FeedbackController
     */
    private $controller;
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
        $this->controller = parent::controllerSetUp(FeedbackController::class);

        $this->form = Mockery::mock(Feedback::class);
        $this->formElementManager->shouldReceive('get')
            ->withArgs(['Application\Form\General\FeedbackForm'])->andReturn($this->form);

        $this->feedbackService = Mockery::mock(Feedback::class);
        $this->controller->setFeedbackService($this->feedbackService);

        $_SERVER['HTTP_USER_AGENT'] = 'UnitTester';
    }

    public function testSendFeedbackFormInvalid()
    {
        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

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
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->feedbackService->shouldReceive('sendMail')->andReturn(false)->once();

        $this->controller->indexAction();
    }

    public function testSendFeedbackSuccess()
    {
        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->feedbackService->shouldReceive('sendMail')->andReturn(true)->once();
        $this->url->shouldReceive('fromRoute')->withArgs(['home'])->andReturn('home')->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('application/feedback/thankyou', $result->getTemplate());
        $this->assertEquals(false, $result->getVariable('strictVars'));
    }

    public function testSendFeedbackFormGetReferer()
    {
        $referer = new Referer();
        $referer->setUri('https://localhost/lpa/3503563157/when-lpa-starts');
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn($referer)->twice();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testSendFeedbackFormNoReferer()
    {
        $this->request->shouldReceive('isPost')->andReturn(false)->once();
        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->andReturn(false)->once();

        /** @var ViewModel $result */
        $result = $this->controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }
}
