<?php

declare(strict_types=1);

namespace ApplicationTest\Controller\General;

use Application\Controller\General\FeedbackController;
use Application\Form\General\FeedbackForm;
use Application\Model\Service\Date\DateService;
use Application\Model\Service\Date\IDateService;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Feedback\FeedbackValidationException;
use ApplicationTest\Controller\AbstractControllerTestCase;
use DateTime;
use Laminas\Session\Container;
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
    private MockInterface|Feedback $feedbackService;
    private MockInterface|DateService $dateService;

    protected $sessionManager;

    private array $postData = [
        'rating' => '5',
        'details' => 'Awesome!',
        'email' => 'unit@test.com',
        'phone' => '0123456789',
    ];


    public function setUp(): void
    {
        parent::setUp();

        $this->form = Mockery::mock(Feedback::class);
        $this->formElementManager
            ->shouldReceive('get')
            ->withArgs(['Application\Form\General\FeedbackForm'])
            ->andReturn($this->form);

        $this->dateService = Mockery::mock(DateService::class);

        $_SERVER['HTTP_USER_AGENT'] = 'UnitTester';
    }

    protected function getController(string $controllerName)
    {
        /** @var FeedbackController $controller */
        $controller = parent::getController($controllerName);

        // Mock Feedback service
        $this->feedbackService = Mockery::mock(Feedback::class);
        $controller->setFeedbackService($this->feedbackService);

        $controller->setDateService($this->dateService);
        $controller->setSessionUtility($this->sessionUtility);

        return $controller;
    }

    public function testSendFeedbackFormInvalid(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostInvalid($this->form, $this->postData);

        $now = new DateTime();

        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->andReturn($now->getTimestamp() - 5);
        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');

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
            ->with('API exception while adding feedback from Feedback service', Mockery::any())
            ->once();

        $now = new DateTime();
        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->andReturn($now->getTimestamp() - 5);
        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'feedbackLinkClickedFromPage')
            ->andReturn('Unknown')
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($this->form, $result->getVariables()['form']);
        $this->assertEquals('An error occurred while submitting feedback', $result->getVariables()['error']);
    }

    public function testSendFeedbackFailWithValidationException(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form
            ->shouldReceive('getData')
            ->andReturn($this->postData)
            ->once();

        $this->feedbackService
            ->shouldReceive('add')
            ->andThrow(new FeedbackValidationException('a validation error occurred'))
            ->once();

        $now = new DateTime();
        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->andReturn($now->getTimestamp() - 5);
        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'feedbackLinkClickedFromPage')
            ->andReturn('Unknown')
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($this->form, $result->getVariables()['form']);
        $this->assertEquals('a validation error occurred', $result->getVariables()['error']);
    }

    public function testSendFeedbackSuccess(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->setPostValid($this->form, $this->postData);
        $this->form
            ->shouldReceive('getData')
            ->andReturn($this->postData)
            ->once();

        $this->feedbackService
            ->shouldReceive('add')
            ->andReturn(true)
            ->once();

        $response = new Response();
        $this->redirect
            ->shouldReceive('toRoute')
            ->andReturn($response)
            ->once();

        $now = new DateTime();
        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->andReturn($now->getTimestamp() - 5);
        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');
        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'feedbackLinkClickedFromPage')
            ->andReturn('Unknown')
            ->once();

        $result = $controller->indexAction();

        $this->assertEquals($result, $response);
    }

    public function testSendFeedbackFormGetReferer(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->request
            ->shouldReceive('isPost')
            ->andReturn(false)
            ->once();

        $now = new DateTime();

        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('setExpirationHopsInMvc')
            ->with('feedback', 1)
            ->once();
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('feedback', 'formGeneratedTime', $now->getTimestamp());
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('feedback', 'feedbackLinkClickedFromPage', '/lpa/3503563157/when-lpa-starts')
            ->once();

        $uri = new Uri('https://localhost/lpa/3503563157/when-lpa-starts');
        $referer = Mockery::mock(Referer::class);
        $referer
            ->shouldReceive('uri')
            ->once()
            ->andReturn($uri);

        $this->request
            ->shouldReceive('getHeader')
            ->withArgs(['Referer'])
            ->once()
            ->andReturn($referer);

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testSendFeedbackFormNoReferer(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $this->request
            ->shouldReceive('isPost')
            ->andReturn(false)
            ->once();

        $this->request
            ->shouldReceive('getHeader')
            ->withArgs(['Referer'])
            ->andReturn(false)
            ->once();

        $now = new DateTime();
        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('feedback', 'formGeneratedTime', $now->getTimestamp())
            ->once();
        $this->sessionUtility
            ->shouldReceive('setInMvc')
            ->with('feedback', 'feedbackLinkClickedFromPage', null)
            ->once();
        $this->sessionUtility
            ->shouldReceive('setExpirationHopsInMvc')
            ->with('feedback', 1)
            ->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertInstanceOf(ViewModel::class, $result);
        $this->assertEquals('', $result->getTemplate());
        $this->assertEquals($this->form, $result->getVariable('form'));
    }

    public function testSendFeedbackFormSubmittedTooQuickly(): void
    {
        $controller = $this->getController(FeedbackController::class);

        $now = new DateTime();
        $this->dateService
            ->shouldReceive('getNow')
            ->andReturn($now)
            ->once();

        $this->sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->andReturn($now->getTimestamp());
        $this->sessionUtility
            ->shouldReceive('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');

        $this->request
            ->shouldReceive('isPost')
            ->andReturn(true)
            ->once();

        $this->logger
            ->shouldReceive('error')
            ->with('Feedback form submitted too quickly, possible bot submission')
            ->once();

        /** @var ViewModel $result */
        $result = $controller->indexAction();

        $this->assertEquals($this->form, $result->getVariables()['form']);
        $this->assertEquals('An error occurred while submitting feedback. Please try again.', $result->getVariables()['error']);
    }
}
