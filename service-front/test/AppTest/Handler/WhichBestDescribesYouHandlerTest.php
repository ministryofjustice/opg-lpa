<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\User\WhichBestDescribesYouForm;
use App\Handler\WhichBestDescribesYouHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginSessionManager;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WhichBestDescribesYouHandlerTest extends TestCase
{
    private const array PENDING_LINK = [
        'sub'     => 'urn:fdc:gov.uk:2022:newuser',
        'email'   => 'newuser@example.com',
        'idToken' => 'pending.id.token',
    ];

    private TemplateRendererInterface&MockObject $renderer;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private WhichBestDescribesYouForm $form;
    private WhichBestDescribesYouHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->logger   = $this->createMock(LoggerInterface::class);
        $this->session  = $this->createMock(SessionInterface::class);

        $this->form = new WhichBestDescribesYouForm();
        $this->form->init();

        $formElementManager = $this->createMock(FormElementManager::class);
        $formElementManager->method('get')->with(WhichBestDescribesYouForm::class)->willReturn($this->form);

        $this->handler = new WhichBestDescribesYouHandler(
            $this->renderer,
            $formElementManager,
            new OneLoginSessionManager(),
            $this->logger,
        );
    }

    /**
     * @param array<string, mixed>|null $pendingLink
     * @param array<string, mixed>|null $onboarding
     */
    private function createRequest(
        string $method = 'GET',
        array $postData = [],
        ?array $pendingLink = self::PENDING_LINK,
        ?array $onboarding = null,
    ): ServerRequest {
        $this->session
            ->method('get')
            ->willReturnCallback(fn (string $key) => match ($key) {
                'onelogin_pending_link' => $pendingLink,
                'onelogin_onboarding'   => $onboarding,
                default                 => null,
            });

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/which-best-describes-you'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        return $method === 'POST' ? $request->withParsedBody($postData) : $request;
    }

    public function testWithoutAPendingOneLoginLinkTheUserIsSentToSignIn(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.onboarding_missing_pending_link');
        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest('GET', [], null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testGetShowsTheQuestionWithNothingSelected(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/onboarding/which-best-describes-you.twig',
                $this->callback(fn (array $vars) => $vars['form'] === $this->form && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>question</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertNull($this->form->get('userType')->getValue());
    }

    public function testGetShowsTheAnswerAlreadyGiven(): void
    {
        $this->renderer->method('render')->willReturn('<html>question</html>');

        $this->handler->handle($this->createRequest('GET', [], self::PENDING_LINK, ['userType' => 'professional']));

        $this->assertSame('professional', $this->form->get('userType')->getValue());
    }

    public function testPostWithoutAnAnswerShowsTheQuestionAgainWithAnError(): void
    {
        $this->session->expects($this->never())->method('set');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['userType' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertArrayHasKey('isEmpty', $this->form->get('userType')->getMessages());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function userTypeProvider(): array
    {
        return ['lay' => ['lay'], 'professional' => ['professional']];
    }

    #[DataProvider('userTypeProvider')]
    public function testPostKeepsTheAnswerInTheSessionAndContinuesOnboarding(string $userType): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with('onelogin_onboarding', ['userType' => $userType]);

        $response = $this->handler->handle($this->createRequest('POST', ['userType' => $userType]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/link-or-create-account', $response->getHeaderLine('Location'));
    }
}
