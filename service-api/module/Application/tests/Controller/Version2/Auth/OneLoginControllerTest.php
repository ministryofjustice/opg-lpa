<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\OneLoginController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\OneLogin\OneLoginAuthenticationException;
use Application\Model\Service\OneLogin\Service as OneLoginService;
use Laminas\View\Model\JsonModel;
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
}
