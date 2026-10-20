<?php

declare(strict_types=1);

namespace AppTest\Handler;

use App\Form\User\AboutYou;
use App\Handler\YourDetailsHandler;
use App\Middleware\CsrfValidationMiddleware;
use App\Service\OneLogin\OneLoginService;
use App\Service\OneLogin\OneLoginSessionManager;
use App\Service\OneLogin\SignedOutSubjects;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use Laminas\Form\FormElementManager;
use MakeShared\OneLogin\UserType;
use Mezzio\Session\SessionInterface;
use Mezzio\Session\SessionMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;

class YourDetailsHandlerTest extends TestCase
{
    private const array PENDING_LINK = [
        'sub'     => 'urn:fdc:gov.uk:2022:newuser',
        'email'   => 'newuser@example.com',
        'idToken' => 'pending.id.token',
    ];

    private const array NEW_USER = ['userType' => 'professional', 'hasExistingAccount' => false];

    private const array DETAILS = [
        'name-title'       => 'Mr',
        'name-first'       => 'Joe',
        'name-last'        => 'Bloggs',
        'dob-date'         => ['day' => '14', 'month' => '04', 'year' => '1980'],
        'address-address1' => '1 High Street',
        'address-address2' => 'Town',
        'address-address3' => '',
        'address-postcode' => 'AB1 2CD',
    ];

    private const array IDENTITY = [
        'userId'         => 'uid-new',
        'token'          => 'tok-new',
        'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
        'lastLogin'      => '2025-01-01T00:00:00+00:00',
        'sharedSpaceId'  => null,
    ];

    private TemplateRendererInterface&MockObject $renderer;
    private OneLoginService&MockObject $oneLoginService;
    private LoggerInterface&MockObject $logger;
    private SessionInterface&MockObject $session;
    private AboutYou $form;
    private YourDetailsHandler $handler;

    protected function setUp(): void
    {
        $this->renderer        = $this->createMock(TemplateRendererInterface::class);
        $this->oneLoginService = $this->createMock(OneLoginService::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->session         = $this->createMock(SessionInterface::class);

        $this->form = new AboutYou();
        $this->form->init();

        $formElementManager = $this->createMock(FormElementManager::class);
        $formElementManager->method('get')->with(AboutYou::class)->willReturn($this->form);

        $this->handler = new YourDetailsHandler(
            $this->renderer,
            $formElementManager,
            new OneLoginSessionManager($this->createStub(SignedOutSubjects::class), new NullLogger()),
            $this->oneLoginService,
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
        ?array $onboarding = self::NEW_USER,
    ): ServerRequest {
        $this->session
            ->method('get')
            ->willReturnCallback(fn (string $key) => match ($key) {
                // Signed in just now, so the pending link has not expired
                'onelogin_pending_link' => $pendingLink === null ? null : $pendingLink + ['signedInAt' => time()],
                'onelogin_onboarding'   => $onboarding,
                default                 => null,
            });

        $request = (new ServerRequest())
            ->withMethod($method)
            ->withUri(new Uri('/your-details'))
            ->withAttribute(CsrfValidationMiddleware::TOKEN_ATTRIBUTE, 'test-token')
            ->withAttribute(SessionMiddleware::SESSION_ATTRIBUTE, $this->session);

        return $method === 'POST' ? $request->withParsedBody($postData) : $request;
    }

    public function testWithoutAPendingOneLoginLinkTheUserIsSentToSignIn(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('auth.onelogin.onboarding_missing_pending_link');

        $response = $this->handler->handle($this->createRequest('GET', [], null));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    /**
     * @return array<string, array{array<string, mixed>|null, string}>
     */
    public static function unansweredProvider(): array
    {
        return [
            'nothing answered'                  => [null, '/which-best-describes-you'],
            'existing account not answered yet' => [['userType' => 'lay'], '/existing-account'],
            'keeps their existing account'      => [
                ['userType' => 'lay', 'hasExistingAccount' => true, 'keepExistingAccount' => true],
                '/existing-account',
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $onboarding
     */
    #[DataProvider('unansweredProvider')]
    public function testTheUserMustBeCreatingANewAccountToSeeThisPage(?array $onboarding, string $expected): void
    {
        $this->renderer->expects($this->never())->method('render');
        $this->oneLoginService->expects($this->never())->method('createAndLinkAccount');

        $response = $this->handler->handle($this->createRequest('GET', [], self::PENDING_LINK, $onboarding));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame($expected, $response->getHeaderLine('Location'));
    }

    public function testGetShowsTheEmptyDetailsForm(): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with(
                'application/authenticated/onboarding/your-details.twig',
                $this->callback(fn (array $vars) => $vars['form'] === $this->form && $vars['error'] === null),
            )
            ->willReturn('<html>details</html>');

        $response = $this->handler->handle($this->createRequest());

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function backLinkProvider(): array
    {
        return [
            'new to Make'                 => [self::NEW_USER, 'existing-account'],
            'chose not to keep an account' => [
                ['userType' => 'lay', 'hasExistingAccount' => true, 'keepExistingAccount' => false],
                'keep-your-lpas',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $onboarding
     */
    #[DataProvider('backLinkProvider')]
    public function testBackGoesToThePreviousQuestion(array $onboarding, string $backRoute): void
    {
        $this->renderer->expects($this->once())
            ->method('render')
            ->with($this->anything(), $this->callback(fn (array $vars) => $vars['backRoute'] === $backRoute))
            ->willReturn('<html>details</html>');

        $this->handler->handle($this->createRequest('GET', [], self::PENDING_LINK, $onboarding));
    }

    public function testPostWithMissingDetailsShowsTheFormAgainWithoutCreatingAnAccount(): void
    {
        $this->oneLoginService->expects($this->never())->method('createAndLinkAccount');
        $this->renderer->expects($this->once())->method('render')->willReturn('<html>errors</html>');

        $response = $this->handler->handle($this->createRequest('POST', ['name-first' => '', 'name-last' => ''] + self::DETAILS));

        $this->assertInstanceOf(HtmlResponse::class, $response);
        $this->assertNotEmpty($this->form->getMessages());
    }

    public function testSavingCreatesTheAccountWithTheOnboardingAnswersAndSignsTheUserIn(): void
    {
        $this->oneLoginService->expects($this->once())
            ->method('createAndLinkAccount')
            ->with(
                self::PENDING_LINK['sub'],
                self::PENDING_LINK['email'],
                UserType::Professional,
                $this->callback(fn (array $profile) => array_keys($profile) === ['name', 'dob', 'address']
                    && $profile['name']['first'] === 'Joe'
                    && $profile['address']['postcode'] === 'AB1 2CD'),
            )
            ->willReturn(self::IDENTITY);

        $this->session->expects($this->once())->method('clear');
        $this->session->expects($this->once())->method('regenerate');

        $response = $this->handler->handle($this->createRequest('POST', self::DETAILS));

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/user/dashboard?account=created', $response->getHeaderLine('Location'));
    }

    public function testAnApiFailureKeepsTheUserOnThePageWithAnError(): void
    {
        $this->oneLoginService->method('createAndLinkAccount')->willThrowException(new RuntimeException('api down'));
        $this->session->expects($this->never())->method('clear');
        $this->logger->expects($this->once())->method('error')->with('auth.onelogin.create_error');

        $this->renderer->expects($this->once())
            ->method('render')
            ->with('application/authenticated/onboarding/your-details.twig', $this->callback(fn (array $vars) => $vars['error'] === 'api-error'))
            ->willReturn('<html>error</html>');

        $response = $this->handler->handle($this->createRequest('POST', self::DETAILS));

        $this->assertInstanceOf(HtmlResponse::class, $response);
    }
}
