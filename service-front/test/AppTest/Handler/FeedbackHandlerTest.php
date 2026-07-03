<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Handler\FeedbackHandler;
use App\Service\Date\DateService;
use App\Service\Feedback\FeedbackService;
use App\Service\Feedback\FeedbackValidationException;
use DateTime;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

class FeedbackHandlerTest extends TestCase
{
    private TemplateRendererInterface&MockObject $renderer;
    private FormElementManager&MockObject $formElementManager;
    private FeedbackService&MockObject $feedbackService;
    private LoggerInterface&MockObject $logger;
    private DateService&MockObject $dateService;
    private SessionInterface&MockObject $session;
    private FormInterface&MockObject $form;
    private FeedbackHandler $handler;

    /** @var array<string, string> */
    private array $originalServer = [];

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->feedbackService = $this->createMock(FeedbackService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dateService = $this->createMock(DateService::class);
        $this->session = $this->createMock(SessionInterface::class);
        $this->form = $this->createMock(FormInterface::class);

        $this->formElementManager
            ->method('get')
            ->with('App\Form\General\FeedbackForm')
            ->willReturn($this->form);

        $this->handler = new FeedbackHandler(
            $this->renderer,
            $this->formElementManager,
            $this->feedbackService,
            $this->logger,
            $this->dateService,
        );

        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
    }

    private function createRequest(
        string $method = 'GET',
        array $body = [],
        array $headers = [],
    ): ServerRequest {
        $request = (new ServerRequest())
            ->withMethod($method)
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($method === 'POST') {
            $request = $request->withParsedBody($body);
        }

        return $request;
    }

    public function testGetStoresGeneratedTimeAndRefererAndRendersForm(): void
    {
        $now = new DateTime('2026-07-03 12:00:00');
        $calls = [];

        $this->dateService->expects($this->once())->method('getNow')->willReturn($now);
        $this->session
            ->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use (&$calls): void {
                $calls[$key] = $value;
            });

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn(array $params): bool => $params['form'] === $this->form)
            )
            ->willReturn('<html>feedback form</html>');

        $response = $this->handler->handle($this->createRequest(
            headers: ['Referer' => 'https://example.org/user/dashboard?page=2']
        ));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertSame($now->getTimestamp(), $calls['feedback_form_generated_time']);
        $this->assertSame('/user/dashboard', $calls['feedback_from_page']);
    }

    public function testPostValidFormRedirectsToThanksPage(): void
    {
        $now = new DateTime('2026-07-03 12:00:05');
        $_SERVER['HTTP_USER_AGENT'] = 'CLI Test Agent';

        $this->session->method('get')->willReturnCallback(
            fn(string $key): mixed => match ($key) {
                'feedback_form_generated_time' => $now->getTimestamp() - 5,
                'feedback_from_page' => null,
                default => null,
            }
        );
        $this->session->expects($this->once())->method('unset')->with('feedback_form_generated_time');

        $this->dateService->expects($this->once())->method('getNow')->willReturn($now);
        $this->form->expects($this->once())->method('setData')->with(['details' => 'Helpful']);
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->form->expects($this->once())->method('getData')->willReturn(['details' => 'Helpful']);

        $this->feedbackService
            ->expects($this->once())
            ->method('add')
            ->with([
                'details' => 'Helpful',
                'agent' => 'CLI Test Agent',
                'fromPage' => 'Unknown',
            ]);

        $response = $this->handler->handle($this->createRequest('POST', ['details' => 'Helpful']));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/feedback-thanks', $response->getHeaderLine('Location'));
    }

    public function testPostTooQuicklyRendersFormWithError(): void
    {
        $now = new DateTime('2026-07-03 12:00:02');

        $this->session->method('get')->with('feedback_form_generated_time')->willReturn($now->getTimestamp() - 1);
        $this->session->expects($this->once())->method('unset')->with('feedback_form_generated_time');

        $this->dateService->expects($this->once())->method('getNow')->willReturn($now);
        $this->form->expects($this->once())->method('setData')->with(['details' => 'Helpful']);
        $this->form->expects($this->never())->method('isValid');

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Feedback form submitted too quickly, possible bot submission');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn(array $params): bool => $params['form'] === $this->form
                    && $params['error'] === 'An error occurred while submitting feedback. Please try again.')
            )
            ->willReturn('<html>too quick</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['details' => 'Helpful']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostInvalidFormRendersFormWithValidationErrors(): void
    {
        $now = new DateTime('2026-07-03 12:00:05');

        $this->session->method('get')->with('feedback_form_generated_time')->willReturn($now->getTimestamp() - 5);
        $this->session->expects($this->once())->method('unset')->with('feedback_form_generated_time');

        $this->dateService->expects($this->once())->method('getNow')->willReturn($now);
        $this->form->expects($this->once())->method('setData')->with(['details' => '']);
        $this->form->expects($this->once())->method('isValid')->willReturn(false);

        $this->feedbackService->expects($this->never())->method('add');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn(array $params): bool => $params['form'] === $this->form && !isset($params['error']))
            )
            ->willReturn('<html>invalid form</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['details' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostValidationExceptionRendersFormWithErrorMessage(): void
    {
        $now = new DateTime('2026-07-03 12:00:05');

        $this->session->method('get')->willReturnCallback(
            fn(string $key): mixed => match ($key) {
                'feedback_form_generated_time' => $now->getTimestamp() - 5,
                'feedback_from_page' => '/user/dashboard',
                default => null,
            }
        );
        $this->session->expects($this->once())->method('unset')->with('feedback_form_generated_time');

        $this->dateService->expects($this->once())->method('getNow')->willReturn($now);
        $this->form->method('setData');
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->form->expects($this->once())->method('getData')->willReturn(['details' => 'Helpful']);

        $this->feedbackService
            ->expects($this->once())
            ->method('add')
            ->willThrowException(new FeedbackValidationException('Please enter more detail'));

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn(array $params): bool => $params['error'] === 'Please enter more detail')
            )
            ->willReturn('<html>validation error</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['details' => 'Helpful']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testPostGenericExceptionRendersFormWithGenericError(): void
    {
        $now = new DateTime('2026-07-03 12:00:05');
        $exception = new RuntimeException('API unavailable');

        $this->session->method('get')->willReturnCallback(
            fn(string $key): mixed => match ($key) {
                'feedback_form_generated_time' => $now->getTimestamp() - 5,
                'feedback_from_page' => '/user/dashboard',
                default => null,
            }
        );
        $this->session->expects($this->once())->method('unset')->with('feedback_form_generated_time');

        $this->dateService->expects($this->once())->method('getNow')->willReturn($now);
        $this->form->method('setData');
        $this->form->expects($this->once())->method('isValid')->willReturn(true);
        $this->form->expects($this->once())->method('getData')->willReturn(['details' => 'Helpful']);

        $this->feedbackService
            ->expects($this->once())
            ->method('add')
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'API exception while adding feedback from Feedback service',
                ['exception' => $exception]
            );

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn(array $params): bool => $params['error'] === 'An error occurred while submitting feedback')
            )
            ->willReturn('<html>generic error</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['details' => 'Helpful']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
