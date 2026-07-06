<?php

declare(strict_types=1);

namespace AppTest\Authentication\Adapter;

use App\Authentication\Adapter\LpaAuthAdapter;
use App\Model\Service\Authentication\Identity\User;
use App\Service\ApiClient\Client;
use App\Service\ApiClient\Exception\ApiException;
use GuzzleHttp\Psr7\Response;
use Laminas\Authentication\Adapter\Exception\RuntimeException;
use Laminas\Authentication\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class LpaAuthAdapterTest extends TestCase
{
    private Client&MockObject $client;
    private LpaAuthAdapter $adapter;

    protected function setUp(): void
    {
        $this->client = $this->createMock(Client::class);
        $this->adapter = new LpaAuthAdapter($this->client);
    }

    public function testSetEmailNormalisesValueAndReturnsSameInstance(): void
    {
        $result = $this->adapter->setEmail('  TEST@Example.COM ');

        $this->assertSame($this->adapter, $result);
        $this->assertSame('test@example.com', (new \ReflectionProperty($this->adapter, 'email'))->getValue($this->adapter));
    }

    public function testSetPasswordStoresValueAndReturnsSameInstance(): void
    {
        $result = $this->adapter->setPassword('secret');  // pragma: allowlist secret

        $this->assertSame($this->adapter, $result);
        $this->assertSame('secret', (new \ReflectionProperty($this->adapter, 'password'))->getValue($this->adapter));
    }

    public function testAuthenticateThrowsWhenEmailMissing(): void
    {
        $this->adapter->setPassword('secret');  // pragma: allowlist secret

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Email address not set');

        $this->adapter->authenticate();
    }

    public function testAuthenticateThrowsWhenPasswordMissing(): void
    {
        $this->adapter->setEmail('test@example.com');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Password not set');

        $this->adapter->authenticate();
    }

    public function testAuthenticateReturnsSuccessResultWithIdentityAndMessage(): void
    {
        $this->adapter->setEmail(' TEST@example.com ')->setPassword('secret');

        $this->client->expects($this->once())
            ->method('httpPost')
            ->with('/v2/authenticate', [
                'username' => 'test@example.com',
                'password' => 'secret',  // pragma: allowlist secret
            ])
            ->willReturn([
                'userId' => 'user-123',
                'token' => 'token-abc',
                'expiresIn' => 900,
                'last_login' => '2024-05-10T12:34:56+00:00',
                'inactivityFlagsCleared' => true,
            ]);

        $result = $this->adapter->authenticate();

        $this->assertSame(Result::SUCCESS, $result->getCode());
        $this->assertSame(['inactivity-flags-cleared'], $result->getMessages());
        $this->assertInstanceOf(User::class, $result->getIdentity());
        $this->assertSame('user-123', $result->getIdentity()->id());
        $this->assertSame('token-abc', $result->getIdentity()->token());
        $this->assertSame('2024-05-10T12:34:56+00:00', $result->getIdentity()->lastLogin()->format('c'));
    }

    public function testAuthenticateReturnsCredentialFailureForUnauthenticatedResponseAndClearsPassword(): void
    {
        $this->adapter->setEmail('test@example.com')->setPassword('secret');  // pragma: allowlist secret
        $this->client->expects($this->once())->method('httpPost')->willReturn([]);

        $result = $this->adapter->authenticate();

        $this->assertSame(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertSame([null], $result->getMessages());
        $this->assertNull($result->getIdentity());
        $this->assertNull((new \ReflectionProperty($this->adapter, 'password'))->getValue($this->adapter));
    }

    public function testAuthenticateReturnsGeneralFailureForServerError(): void
    {
        $this->adapter->setEmail('test@example.com')->setPassword('secret');  // pragma: allowlist secret
        $this->client->method('httpPost')->willThrowException($this->makeApiException(500, 'server-error'));

        $result = $this->adapter->authenticate();

        $this->assertSame(Result::FAILURE, $result->getCode());
        $this->assertSame(['api-error'], $result->getMessages());
    }

    #[DataProvider('authenticationFailureProvider')]
    public function testAuthenticateMapsKnownAuthenticationFailures(string $apiMessage, string $expectedMessage): void
    {
        $this->adapter->setEmail('test@example.com')->setPassword('secret');  // pragma: allowlist secret
        $this->client->method('httpPost')->willThrowException($this->makeApiException(400, $apiMessage));

        $result = $this->adapter->authenticate();

        $this->assertSame(Result::FAILURE_CREDENTIAL_INVALID, $result->getCode());
        $this->assertSame([$expectedMessage], $result->getMessages());
    }

    public static function authenticationFailureProvider(): array
    {
        return [
            'locked account' => ['account-locked/max-login-attempts', 'locked'],
            'inactive account' => ['account-not-active', 'not-activated'],
            'other auth error' => ['anything-else', 'authentication-failed'],
        ];
    }

    public function testGetSessionExpiryReturnsApiResponse(): void
    {
        $this->client->expects($this->once())
            ->method('httpGet')
            ->with('/v2/session-expiry', [], true, true, ['CheckedToken' => 'token-abc'])
            ->willReturn(['valid' => true, 'remainingSeconds' => 120]);

        $this->assertSame(['valid' => true, 'remainingSeconds' => 120], $this->adapter->getSessionExpiry('token-abc'));
    }

    public function testGetSessionExpiryReturnsNullWhenApiThrows(): void
    {
        $this->client->method('httpGet')->willThrowException($this->makeApiException(401, 'expired'));

        $this->assertNull($this->adapter->getSessionExpiry('token-abc'));
    }

    public function testSetSessionExpiryReturnsApiResponse(): void
    {
        $this->client->expects($this->once())
            ->method('httpPost')
            ->with('/v2/session-set-expiry', ['expireInSeconds' => 600], ['CheckedToken' => 'token-abc'])
            ->willReturn(['valid' => true, 'remainingSeconds' => 600]);

        $this->assertSame(['valid' => true, 'remainingSeconds' => 600], $this->adapter->setSessionExpiry('token-abc', 600));
    }

    public function testSetSessionExpiryReturnsApiErrorMessageWhenApiThrows(): void
    {
        $this->client->method('httpPost')->willThrowException($this->makeApiException(400, 'bad-expiry'));

        $this->assertSame('bad-expiry', $this->adapter->setSessionExpiry('token-abc', 600));
    }

    private function makeApiException(int $statusCode, string $message): ApiException
    {
        return new ApiException(new Response($statusCode, [], json_encode(['detail' => $message], JSON_THROW_ON_ERROR)), $message);
    }
}
