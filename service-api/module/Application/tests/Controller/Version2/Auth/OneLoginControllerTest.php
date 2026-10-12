<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\OneLoginController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use Laminas\View\Model\JsonModel;
use InvalidArgumentException;
use MakeShared\OneLogin\LinkReason;
use MakeShared\OneLogin\UserType;
use Mockery;

class OneLoginControllerTest extends AbstractAuthControllerTestCase
{
    private const array CREATE_BODY = [
        'oneLoginSub'   => 'urn:fdc:gov.uk:2022:new',
        'oneLoginEmail' => 'brand.new.user@gmail.com',
        'userType'      => 'lay',
        'profile'       => ['name' => ['title' => 'Mr', 'first' => 'Joe', 'last' => 'Bloggs']],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->service = Mockery::mock(OneLoginService::class);
    }

    private function getOneLoginController(): OneLoginController
    {
        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class);

        return $controller;
    }

    /**
     * @return array<string, array{string|null}>
     */
    public static function invalidRedirectUrlProvider(): array
    {
        return [
            'null'         => [null],
            'empty string' => [''],
        ];
    }

    /**
     * @dataProvider invalidRedirectUrlProvider
     */
    public function testStartActionReturnsBadRequestForInvalidRedirectUrl(string|null $value): void
    {
        $this->params->shouldReceive('fromQuery')
            ->with('redirect_url')
            ->andReturn($value)
            ->once();

        $result = $this->getOneLoginController()->startAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
        $this->assertStringContainsString('redirect_url', $result->detail);
    }

    public function testStartActionReturnsJsonModelWithServiceResult(): void
    {
        $redirectUrl   = 'https://example.com/auth/redirect';
        $serviceResult = [
            'state' => 'some-state',
            'nonce' => 'some-nonce',
            'url'   => 'https://auth.example.com/authorize?foo=bar',
        ];

        $this->params->shouldReceive('fromQuery')
            ->with('redirect_url')
            ->andReturn($redirectUrl)
            ->once();

        $this->service->shouldReceive('createAuthenticationRequest')
            ->with($redirectUrl)
            ->andReturn($serviceResult)
            ->once();

        $result = $this->getOneLoginController()->startAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($serviceResult, $result->getVariables());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function missingCallbackFieldProvider(): array
    {
        return [
            'code'         => ['code'],
            'state'        => ['state'],
            'nonce'        => ['nonce'],
            'redirect_uri' => ['redirect_uri'],
        ];
    }

    /**
     * @dataProvider missingCallbackFieldProvider
     */
    public function testCallbackActionReturnsBadRequestWhenFieldMissing(string $missingField): void
    {
        $body = [
            'code'         => 'auth-code-123',
            'state'        => 'state-abc',
            'nonce'        => 'nonce-xyz',
            'redirect_uri' => 'https://example.com/auth/redirect',
        ];
        unset($body[$missingField]);

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->callbackAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
        $this->assertStringContainsString($missingField, $result->detail);
    }

    public function testCallbackActionReturnsJsonModelWithServiceResult(): void
    {
        $body = [
            'code'         => 'auth-code-123',
            'state'        => 'state-abc',
            'nonce'        => 'nonce-xyz',
            'redirect_uri' => 'https://example.com/auth/redirect',
        ];

        $serviceResult = [
            'linked'   => false,
            'sub'      => 'urn:fdc:gov.uk:2022:abc',
            'email'    => 'user@example.com',
            'identity' => null,
        ];

        $this->service->shouldReceive('handleCallback')
            ->with($body['code'], $body['state'], $body['nonce'], $body['redirect_uri'])
            ->andReturn($serviceResult)
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->callbackAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($serviceResult, $result->getVariables());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function authenticationFailureReasonProvider(): array
    {
        return [
            'token exchange failed' => ['token_exchange_failed'],
            'vot missing'           => ['missing_vot_claim'],
            'vot not Cl.Cm'         => ['invalid_vot_claim'],
        ];
    }

    /**
     * @dataProvider authenticationFailureReasonProvider
     */
    public function testCallbackActionReturnsUnauthorizedWhenAuthenticationFails(string $reason): void
    {
        $body = [
            'code'         => 'auth-code-123',
            'state'        => 'state-abc',
            'nonce'        => 'nonce-xyz',
            'redirect_uri' => 'https://example.com/auth/redirect',
        ];

        $this->service->shouldReceive('handleCallback')
            ->andThrow(new OneLoginAuthenticationException($reason))
            ->once();

        $this->logger->shouldReceive('error')
            ->with('auth.onelogin.callback_failed', ['reason' => $reason])
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->callbackAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(401, $result->status);
        $this->assertStringContainsString('One Login authentication failed', $result->detail);
    }

    public function testLinkActionReturnsJsonModelWithServiceResult(): void
    {
        $body = [
            'username'      => 'user@example.com',
            'password'      => 'sup3r-secret', //pragma: allowlist secret
            'oneLoginSub'   => 'urn:fdc:gov.uk:2022:new',
            'oneLoginEmail' => 'joe.bloggs@gmail.com',
            'userType'      => 'professional',
        ];

        $serviceResult = ['linked' => false, 'reason' => LinkReason::ALREADY_LINKED];

        $this->service->shouldReceive('linkExistingAccount')
            ->with($body['username'], $body['password'], $body['oneLoginSub'], $body['oneLoginEmail'], UserType::Professional)
            ->andReturn($serviceResult)
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->linkAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($serviceResult, $result->getVariables());
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function invalidLogoutBodyProvider(): array
    {
        $valid = ['idToken' => 'header.payload.sig', 'postLogoutRedirectUri' => 'https://example.com/done'];

        return [
            'missing idToken'               => [['postLogoutRedirectUri' => $valid['postLogoutRedirectUri']], 'idToken'],
            'empty idToken'                 => [['idToken' => ''] + $valid, 'idToken'],
            'missing postLogoutRedirectUri' => [['idToken' => $valid['idToken']], 'postLogoutRedirectUri'],
            'empty postLogoutRedirectUri'   => [['postLogoutRedirectUri' => ''] + $valid, 'postLogoutRedirectUri'],
        ];
    }

    /**
     * @dataProvider invalidLogoutBodyProvider
     * @param array<string, mixed> $body
     */
    public function testLogoutActionReturnsBadRequestForInvalidBody(array $body, string $expectedField): void
    {
        $this->service->shouldNotReceive('createLogoutRequest');

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->logoutAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
        $this->assertStringContainsString($expectedField, $result->detail);
    }

    public function testLogoutActionReturnsLogoutUrl(): void
    {
        $body = ['idToken' => 'header.payload.sig', 'postLogoutRedirectUri' => 'https://example.com/done'];

        $this->service->shouldReceive('createLogoutRequest')
            ->with('header.payload.sig', 'https://example.com/done')
            ->andReturn('https://oidc.example.com/logout?id_token_hint=header.payload.sig')
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->logoutAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertSame(
            ['url' => 'https://oidc.example.com/logout?id_token_hint=header.payload.sig'],
            $result->getVariables(),
        );
    }

    public function testBackChannelLogoutActionReturnsServiceResult(): void
    {
        $body = ['logoutToken' => 'a.logout.token'];

        $this->service->shouldReceive('handleBackChannelLogout')
            ->with('a.logout.token')
            ->andReturn(['accepted' => true])
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->backChannelLogoutAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertSame(['accepted' => true], $result->getVariables());
    }

    public function testBackChannelLogoutActionReturnsRejectionAsOkResponse(): void
    {
        $this->service->shouldReceive('handleBackChannelLogout')
            ->andReturn(['accepted' => false, 'reason' => 'invalid_signature'])
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, ['logoutToken' => 'forged']);

        $result = $controller->backChannelLogoutAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertFalse($result->getVariables()['accepted']);
    }

    public function testBackChannelLogoutActionRejectsMissingToken(): void
    {
        $this->service->shouldNotReceive('handleBackChannelLogout');

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, []);

        $result = $controller->backChannelLogoutAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertFalse($result->getVariables()['accepted']);
        $this->assertSame('missing_logout_token', $result->getVariables()['reason']);
    }

    public function testCreateActionReturnsJsonModelWithServiceResult(): void
    {
        $body = self::CREATE_BODY;

        $identity = [
            'userId'         => 'uid-new',
            'token'          => 'tok-new',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $this->service->shouldReceive('createAndLinkAccount')
            ->with($body['oneLoginSub'], $body['oneLoginEmail'], UserType::Lay, $body['profile'])
            ->andReturn($identity)
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->createAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($identity, $result->getVariables());
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidUserTypeProvider(): array
    {
        return [
            'missing userType' => [[]],
            'unknown userType' => [['userType' => 'robot']],
            'userType not a string' => [['userType' => ['lay']]],
        ];
    }

    /**
     * @dataProvider invalidUserTypeProvider
     * @param array<string, mixed> $userType
     */
    public function testLinkActionReturnsBadRequestForAnInvalidUserType(array $userType): void
    {
        $this->service->shouldNotReceive('linkExistingAccount');

        $body = [
            'username'      => 'user@example.com',
            'password'      => 'sup3r-secret', //pragma: allowlist secret
            'oneLoginSub'   => 'urn:fdc:gov.uk:2022:new',
            'oneLoginEmail' => 'joe.bloggs@gmail.com',
        ] + $userType;

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->linkAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
    }

    /**
     * @dataProvider invalidUserTypeProvider
     * @param array<string, mixed> $userType
     */
    public function testCreateActionReturnsBadRequestForAnInvalidUserType(array $userType): void
    {
        $this->service->shouldNotReceive('createAndLinkAccount');

        $body = $userType + self::CREATE_BODY;
        if ($userType === []) {
            unset($body['userType']);
        }

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidProfileBodyProvider(): array
    {
        return ['missing profile' => [null], 'profile not an object' => ['Joe Bloggs']];
    }

    /**
     * @dataProvider invalidProfileBodyProvider
     */
    public function testCreateActionReturnsBadRequestWithoutAProfile(mixed $profile): void
    {
        $this->service->shouldNotReceive('createAndLinkAccount');

        $body = ['profile' => $profile] + self::CREATE_BODY;

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
    }

    public function testCreateActionReturnsBadRequestWhenTheServiceRejectsTheProfile(): void
    {
        $this->service->shouldReceive('createAndLinkAccount')
            ->andThrow(new InvalidArgumentException('profile is not valid'))
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, self::CREATE_BODY);

        $result = $controller->createAction();

        $this->assertInstanceOf(ApiProblem::class, $result);
        $this->assertSame(400, $result->status);
        $this->assertSame('profile is not valid', $result->detail);
    }
}
