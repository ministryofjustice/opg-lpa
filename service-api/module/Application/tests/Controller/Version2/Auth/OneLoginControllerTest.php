<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\OneLoginController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use Laminas\View\Model\JsonModel;
use MakeShared\OneLogin\LinkReason;
use Mockery;

class OneLoginControllerTest extends AbstractAuthControllerTestCase
{
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

    public function testCallbackActionReturnsUnauthorizedWhenAuthenticationFails(): void
    {
        $body = [
            'code'         => 'auth-code-123',
            'state'        => 'state-abc',
            'nonce'        => 'nonce-xyz',
            'redirect_uri' => 'https://example.com/auth/redirect',
        ];

        $this->service->shouldReceive('handleCallback')
            ->andThrow(new OneLoginAuthenticationException('token_exchange_failed'))
            ->once();

        $this->logger->shouldReceive('error')
            ->with('auth.onelogin.callback_failed', ['reason' => 'token_exchange_failed'])
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
        ];

        $serviceResult = ['linked' => false, 'reason' => LinkReason::ALREADY_LINKED];

        $this->service->shouldReceive('linkExistingAccount')
            ->with($body['username'], $body['password'], $body['oneLoginSub'], $body['oneLoginEmail'])
            ->andReturn($serviceResult)
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->linkAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($serviceResult, $result->getVariables());
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
        $body = [
            'oneLoginSub'   => 'urn:fdc:gov.uk:2022:new',
            'oneLoginEmail' => 'brand.new.user@gmail.com',
        ];

        $identity = [
            'userId'         => 'uid-new',
            'token'          => 'tok-new',
            'tokenExpiresAt' => '2030-01-01T00:00:00+00:00',
            'lastLogin'      => '2025-01-01T00:00:00+00:00',
            'sharedSpaceId'  => null,
        ];

        $this->service->shouldReceive('createAndLinkAccount')
            ->with($body['oneLoginSub'], $body['oneLoginEmail'])
            ->andReturn($identity)
            ->once();

        /** @var OneLoginController $controller */
        $controller = $this->getController(OneLoginController::class, $body);

        $result = $controller->createAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($identity, $result->getVariables());
    }
}
