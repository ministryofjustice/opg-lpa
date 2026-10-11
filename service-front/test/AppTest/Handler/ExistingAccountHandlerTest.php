<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\User\ExistingAccountForm;
use App\Handler\ExistingAccountHandler;
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

class ExistingAccountHandlerTest extends TestCase
{
    private const array PENDING_LINK = [
        'sub'     => 'urn:fdc:gov.uk:2022:newuser',
        'email'   => 'newuser@example.com',
        'idToken' => 'pending.id.token',
    ];

    private const array ANSWERED_USER_TYPE = ['userType' => 'lay'];

    private TemplateRendererInterface&MockObject $renderer;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private ExistingAccountForm $form;
    private ExistingAccountHandler $handler;

    protected function setUp(): void
    {
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
        $this->logger   = $this->createMock(LoggerInterface::class);
        $this->session  = $this->createMock(SessionInterface::class);

        $this->form = new ExistingAccountForm();
        $this->form->init();

        $formElementManager = $this->createMock(FormElementManager::class);
        $formElementManager->method('get')->with(ExistingAccountForm::class)->willReturn($this->form);

        $this->handler = new ExistingAccountHandler(
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
        ?array $onboarding = self::ANSWERED_USER_TYPE,
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
            ->withUri(new Uri('/existing-account'))
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

    public function testTheUserIsSentBackIfTheyHaveNotSaidWhichBestDescribesThem(): void
    {
        $this->renderer->expects($this->never())->method('render');

        $response = $this->handler->handle($this->createRequest('GET', [], self::PENDING_LINK, null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/which-best-describes-you', $response->getHeaderLine('Location'));
    }

    public function testGetShowsTheQuestionWithNothingSelected(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/onboarding/existing-account.twig',
                $this->callback(fn (array $vars) => $vars['form'] === $this->form && $vars['csrfToken'] === 'test-token'),
            )
            ->willReturn('<html>question</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertNull($this->form->get('hasExistingAccount')->getValue());
    }

    /**
     * @return array<string, array{bool, string, string}>
     */
    public static function answerProvider(): array
    {
        return [
            'new to the service' => [false, 'no', '/your-details'],
            'existing account'   => [true, 'yes', '/keep-your-lpas'],
        ];
    }

    #[DataProvider('answerProvider')]
    public function testGetShowsTheAnswerAlreadyGiven(bool $hasExistingAccount, string $selected, string $next): void
    {
        $this->renderer->method('render')->willReturn('<html>question</html>');

        $this->handler->handle($this->createRequest(
            'GET',
            [],
            self::PENDING_LINK,
            self::ANSWERED_USER_TYPE + ['hasExistingAccount' => $hasExistingAccount],
        ));

        $this->assertSame($selected, $this->form->get('hasExistingAccount')->getValue());
    }

    public function testPostWithoutAnAnswerShowsTheQuestionAgainWithAnError(): void
    {
        $this->session->expects($this->never())->method('set');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['hasExistingAccount' => '']));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertArrayHasKey('isEmpty', $this->form->get('hasExistingAccount')->getMessages());
    }

    #[DataProvider('answerProvider')]
    public function testPostKeepsTheAnswerInTheSessionAndContinuesOnboarding(
        bool $hasExistingAccount,
        string $answer,
        string $next,
    ): void {
        $this->session->expects($this->once())
            ->method('set')
            ->with('onelogin_onboarding', [
                'userType'            => 'lay',
                'hasExistingAccount'  => $hasExistingAccount,
                'keepExistingAccount' => null,
            ]);

        $response = $this->handler->handle($this->createRequest('POST', ['hasExistingAccount' => $answer]));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($next, $response->getHeaderLine('Location'));
    }
}
