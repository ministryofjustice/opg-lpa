<?php

declare(strict_types=1);

namespace ApplicationTest\Handler;

use Application\Handler\FeedbackHandler;
use DateTimeImmutable;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Form\FormElementManager;
use Laminas\Form\FormInterface;
use Application\Model\Service\Feedback\Feedback;
use Application\Model\Service\Session\SessionUtility;
use Application\Model\Service\Date\IDateService;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;

class FeedbackHandlerTest extends TestCase
{
    private FormElementManager|MockObject $formElementManager;
    private TemplateRendererInterface|MockObject $renderer;
    private Feedback|MockObject $feedbackService;
    private SessionUtility|MockObject $sessionUtility;
    private IDateService|MockObject $dateService;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->formElementManager = $this->createMock(FormElementManager::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->feedbackService = $this->createMock(Feedback::class);
        $this->sessionUtility = $this->createMock(SessionUtility::class);
        $this->dateService = $this->createMock(IDateService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testInvalidFormRendersHtmlResponse(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('setData');
        $form->method('isValid')->willReturn(false);

        $this->formElementManager
            ->method('get')
            ->with('Application\Form\General\FeedbackForm')
            ->willReturn($form);

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn ($ctx) => isset($ctx['form']))
            )
            ->willReturn('<html>invalid</html>');

        $now = new DateTimeImmutable();
        $this->dateService
            ->method('getNow')
            ->willReturn($now);

        $this->sessionUtility
            ->method('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->willReturn($now->getTimestamp() - 5);
        $this->sessionUtility
            ->method('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');

        $handler = new FeedbackHandler(
            $this->renderer,
            $this->formElementManager,
            $this->feedbackService,
            $this->sessionUtility,
            $this->logger,
            $this->dateService,
        );

        $response = $handler->handle(
            (new ServerRequest())->withMethod('POST')->withParsedBody(['foo' => 'bar'])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testTooQuickSubmissionLogsAndRendersError(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('setData');

        $this->formElementManager
            ->method('get')
            ->willReturn($form);

        $now = new DateTimeImmutable();
        $this->dateService
            ->method('getNow')
            ->willReturn($now);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with('Feedback form submitted too quickly, possible bot submission');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn ($ctx) =>
                    isset($ctx['error'])
                    && str_contains($ctx['error'], 'Please try again'))
            )
            ->willReturn('<html>too quick</html>');

        $this->sessionUtility
            ->method('getFromMvc')
            ->with('feedback', 'formGeneratedTime')
            ->willReturn($now->getTimestamp());
        $this->sessionUtility
            ->expects($this->once())
            ->method('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');

        $handler = new FeedbackHandler(
            $this->renderer,
            $this->formElementManager,
            $this->feedbackService,
            $this->sessionUtility,
            $this->logger,
            $this->dateService,
        );

        $response = $handler->handle(
            (new ServerRequest())->withMethod('POST')->withParsedBody([])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testValidationExceptionRendersError(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('setData');
        $form
            ->method('isValid')
            ->willReturn(true);
        $form
            ->method('getData')
            ->willReturn([]);

        $this->formElementManager
            ->method('get')
            ->willReturn($form);

        $this->feedbackService
            ->method('add')
            ->willThrowException(new \Application\Model\Service\Feedback\FeedbackValidationException('error occurred'));

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn ($ctx) => isset($ctx['error']) && $ctx['error'] === 'error occurred')
            )
            ->willReturn('<html>validation error</html>');

        $now = new DateTimeImmutable();
        $this->dateService
            ->method('getNow')
            ->willReturn($now);

        $handler = new FeedbackHandler(
            $this->renderer,
            $this->formElementManager,
            $this->feedbackService,
            $this->sessionUtility,
            $this->logger,
            $this->dateService,
        );

        $response = $handler->handle(
            new ServerRequest()->withMethod('POST')->withParsedBody([])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testServiceExceptionRendersFallbackError(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('setData');
        $form
            ->method('isValid')
            ->willReturn(true);
        $form
            ->method('getData')
            ->willReturn([]);

        $this->formElementManager->method('get')->willReturn($form);

        $this->feedbackService
            ->method('add')
            ->willThrowException(new \RuntimeException('fail'));

        $this->logger
            ->expects($this->once())
            ->method('error');

        $this->renderer
            ->expects($this->once())
            ->method('render')
            ->with(
                'application/general/feedback/index.twig',
                $this->callback(fn ($ctx) => isset($ctx['error']))
            )
            ->willReturn('<html>service error</html>');

        $now = new DateTimeImmutable();
        $this->dateService
            ->method('getNow')
            ->willReturn($now);

        $handler = new FeedbackHandler(
            $this->renderer,
            $this->formElementManager,
            $this->feedbackService,
            $this->sessionUtility,
            $this->logger,
            $this->dateService,
        );

        $response = $handler->handle(
            (new ServerRequest())->withMethod('POST')->withParsedBody([])
        );

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    public function testSuccessfulSubmissionRedirects(): void
    {
        $form = $this->createMock(FormInterface::class);
        $form
            ->expects($this->once())
            ->method('setData');
        $form
            ->method('isValid')
            ->willReturn(true);
        $form
            ->method('getData')
            ->willReturn([]);

        $this->formElementManager
            ->method('get')
            ->willReturn($form);

        $this->feedbackService
            ->expects($this->once())
            ->method('add');

        $now = new DateTimeImmutable();
        $this->dateService
            ->method('getNow')
            ->willReturn($now);

        $this->sessionUtility
            ->method('getFromMvc')
            ->willReturnCallback(function ($namespace, $key) use ($now) {
                if ($namespace === 'feedback' && $key === 'formGeneratedTime') {
                    return $now->getTimestamp() - 5;
                }
                if ($namespace === 'feedback' && $key === 'feedbackLinkClickedFromPage') {
                    return '/somewhere';
                }
                return null;
            });
        $this->sessionUtility
            ->expects($this->exactly(1))
            ->method('unsetInMvc')
            ->with('feedback', 'formGeneratedTime');

        $handler = new FeedbackHandler(
            $this->renderer,
            $this->formElementManager,
            $this->feedbackService,
            $this->sessionUtility,
            $this->logger,
            $this->dateService,
        );

        $response = $handler->handle(
            (new ServerRequest())->withMethod('POST')->withParsedBody([])
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
