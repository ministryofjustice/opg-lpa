<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\User\KeepYourLpasForm;
use App\Handler\KeepYourLpasHandler;
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

class KeepYourLpasHandlerTest extends TestCase
{
    private const array PENDING_LINK = [
        'sub'     => 'urn:fdc:gov.uk:2022:newuser',
        'email'   => 'newuser@example.com',
        'idToken' => 'pending.id.token',
    ];

    private const array HAS_AN_ACCOUNT = ['userType' => 'lay', 'hasExistingAccount' => true];

    private TemplateRendererInterface&MockObject $renderer;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private KeepYourLpasForm $form;
    private KeepYourLpasHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->logger   = $this->createMock(LoggerInterface::class);
        $this->session  = $this->createMock(SessionInterface::class);

        $this->form = new KeepYourLpasForm();
        $this->form->init();

        $formElementManager = $this->createMock(FormElementManager::class);
        $formElementManager->method('get')->with(KeepYourLpasForm::class)->willReturn($this->form);

        $this->handler = new KeepYourLpasHandler(
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
        ?array $onboarding = self::HAS_AN_ACCOUNT,
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
            ->withUri(new Uri('/keep-your-lpas'))
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

    /**
     * @return array<string, array{array<string, mixed>|null, string}>
     */
    public static function earlierQuestionProvider(): array
    {
        return [
            'nothing answered'         => [null, '/which-best-describes-you'],
            'existing account not yet' => [['userType' => 'lay'], '/existing-account'],
            'new to Make'              => [['userType' => 'lay', 'hasExistingAccount' => false], '/existing-account'],
        ];
    }

    /**
     * @param array<string, mixed>|null $onboarding
     */
    #[DataProvider('earlierQuestionProvider')]
    public function testOnlyAUserWithAnExistingAccountIsAsked(?array $onboarding, string $expected): void
    {
        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest('GET', [], self::PENDING_LINK, $onboarding));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected, $response->getHeaderLine('Location'));
    }

    public function testGetShowsTheQuestionWithNothingSelected(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/onboarding/keep-your-lpas.twig',
                $this->callback(fn (array $vars) => $vars['form'] === $this->form && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>question</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertNull($this->form->get('keepExistingAccount')->getValue());
    }

    /**
     * @return array<string, array{bool, string, string}>
     */
    public static function answerProvider(): array
    {
        return [
            'keep'       => [true, 'yes', '/link-account'],
            'start again' => [false, 'no', '/your-details'],
        ];
    }

    #[DataProvider('answerProvider')]
    public function testGetShowsTheAnswerAlreadyGiven(bool $keep, string $selected, string $next): void
    {
        $this->renderer->method('render')->willReturn('<html>question</html>');

        $this->handler->handle($this->createRequest('GET', [], self::PENDING_LINK, self::HAS_AN_ACCOUNT + ['keepExistingAccount' => $keep]));

        $this->assertSame($selected, $this->form->get('keepExistingAccount')->getValue());
    }

    public function testPostWithoutAnAnswerShowsTheQuestionAgainWithAnError(): void
    {
        $this->session->expects($this->never())->method('set');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['keepExistingAccount' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertArrayHasKey('isEmpty', $this->form->get('keepExistingAccount')->getMessages());
    }

    #[DataProvider('answerProvider')]
    public function testPostKeepsTheAnswerInTheSessionAndContinuesOnboarding(bool $keep, string $answer, string $next): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with('onelogin_onboarding', ['userType' => 'lay', 'hasExistingAccount' => true, 'keepExistingAccount' => $keep]);

        $response = $this->handler->handle($this->createRequest('POST', ['keepExistingAccount' => $answer]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($next, $response->getHeaderLine('Location'));
    }
}
