<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\FeedbackController;
use Application\Form\General\FeedbackForm;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use Application\Model\Service\Session\SessionUtility;
use ApplicationTest\Controller\AbstractControllerTestCase;
use Mockery;
use Mockery\MockInterface;
use Laminas\Http\Header\Referer;
use Laminas\Http\Response;
use Laminas\Uri\Uri;
use Laminas\View\Model\ViewModel;
use RuntimeException;

final class FeedbackControllerTest extends AbstractControllerTestCase
{
    private MockInterface|FeedbackForm $form;

    private array $postData = [
        'rating' => '5',
        'details' => 'Awesome!',
        'email' => 'unit@test.com',
        'phone' => '0123456789',
    ];

    /**
     * @var MockInterface|Feedback
     */
    private $feedbackService;

    public function setUp(): void
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

        // Mock Feedback service
        $this->feedbackService = Mockery::mock(Feedback::class);
        $controller->setFeedbackService($this->feedbackService);

        $controller->setSessionUtility(new SessionUtility());

        return $controller;
    }


    public function testSendFeedbackFormInvalid(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostInvalid($this->form, $this->postData);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testSendFeedbackFail(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->feedbackService
            ->shouldReceive('add')
            ->andThrow(new RuntimeException('something unexpected went wrong'))
            ->once();

        $controller->getLogger()
            ->shouldReceive('error')
            ->with('API exception while adding feedback from Feedback service: something unexpected went wrong', Mockery::any())
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($this->form, $result->getVariables()['form']);
        $this->assertEquals('An error occurred while submitting feedback', $result->getVariables()['error']);
    }

    public function testSendFeedbackFailWithValidationException(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form->shouldReceive('getData')->andReturn($this->postData)->once();

        $this->feedbackService->shouldReceive('add')->andThrow(new FeedbackValidationException('a validation error occurred'))->once();

        $result = $controller->indexAction();

        $this->assertEquals($this->form, $result->getVariables()['form']);
        $this->assertEquals('a validation error occurred', $result->getVariables()['error']);
    }

    public function testSendFeedbackSuccess(): void
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

    public function testSendFeedbackFormGetReferer(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->request->shouldReceive('isPost')->andReturn(false)->once();

        $uri = new Uri('https://localhost/lpa/3503563157/when-lpa-starts');
        $referer = Mockery::mock(Referer::class);
        $referer->shouldReceive('uri')->once()->andReturn($uri);

        $this->request->shouldReceive('getHeader')->withArgs(['Referer'])->once()->andReturn($referer);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testSendFeedbackFormNoReferer(): void
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
