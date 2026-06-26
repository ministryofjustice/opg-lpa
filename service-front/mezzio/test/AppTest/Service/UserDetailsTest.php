<?php

declare(strict_types=1);

namespace AppTest\Service;

use App\Authentication\AuthenticationService;
use App\Service\ApiClient\Client as ApiClient;
use App\Service\ApiClient\Exception\ApiException;
use App\Service\Mail\MailParameters;
use App\Service\Mail\Transport\MailTransportInterface;
use App\Service\UserDetails;
use App\Storage\MezzioSessionStorage;
use App\Model\Service\Authentication\Identity\User as Identity;
use MakeShared\DataModel\User\User;
use Mezzio\Helper\UrlHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

final class UserDetailsTest extends TestCase
{
    private AuthenticationService&MockObject $authService;
    private ApiClient&MockObject $apiClient;
    private MailTransportInterface&MockObject $mailTransport;
    private MezzioSessionStorage&MockObject $sessionStorage;
    private UrlHelper&MockObject $urlHelper;
    private LoggerInterface&MockObject $logger;
    private Identity&MockObject $identity;
    private UserDetails $service;

    protected function setUp(): void
    {
        $this->authService    = $this->createMock(AuthenticationService::class);
        $this->apiClient      = $this->createMock(ApiClient::class);
        $this->mailTransport  = $this->createMock(MailTransportInterface::class);
        $this->sessionStorage = $this->createMock(MezzioSessionStorage::class);
        $this->urlHelper      = $this->createMock(UrlHelper::class);
        $this->logger         = $this->createMock(LoggerInterface::class);
        $this->identity       = $this->createMock(Identity::class);

        $this->identity->method('id')->willReturn('user-123');
        $this->identity->method('token')->willReturn('valid-token');

        $this->authService->method('getIdentity')->willReturn($this->identity);

        $this->service = new UserDetails($this->authService, [], $this->mailTransport);
        $this->service->setApiClient($this->apiClient);
        $this->service->setSessionStorage($this->sessionStorage);
        $this->service->setUrlHelper($this->urlHelper);
        $this->service->setLogger($this->logger);
    }

    // -------------------------------------------------------------------------
    // getUserDetails
    // -------------------------------------------------------------------------

    public function testGetUserDetailsReturnsUserOnSuccess(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->with('/v2/user/user-123')
            ->willReturn(['id' => 'user-123', 'email' => ['address' => 'a@b.com']]);

        $result = $this->service->getUserDetails();

        $this->assertInstanceOf(User::class, $result);
    }

    public function testGetUserDetailsReturnsFalseOnApiException(): void
    {
        $this->apiClient->method('httpGet')
            ->willThrowException($this->makeApiException(500));

        $result = $this->service->getUserDetails();

        $this->assertFalse($result);
    }

    // -------------------------------------------------------------------------
    // updatePassword
    // -------------------------------------------------------------------------

    public function testUpdatePasswordReturnsTrueAndPersistsNewToken(): void
    {
        $this->apiClient->expects($this->once())
            ->method('updateToken')
            ->with('valid-token');

        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with(
                '/v2/users/user-123/password',
                ['currentPassword' => 'oldPass', 'newPassword' => 'newPass']
            )
            ->willReturn(['token' => 'refreshed-token', 'expiresIn' => 3600]);

        // getUserDetails() call to get email for the notification
        $this->apiClient->method('httpGet')
            ->willReturn(['id' => 'user-123', 'email' => ['address' => 'user@example.com']]);

        $this->mailTransport->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(MailParameters::class));

        $this->identity->expects($this->once())
            ->method('setToken')
            ->with('refreshed-token');

        $this->sessionStorage->expects($this->once())
            ->method('write')
            ->with($this->identity);

        $result = $this->service->updatePassword('oldPass', 'newPass');

        $this->assertTrue($result);
    }

    public function testUpdatePasswordReturnsUnknownErrorWhenApiReturnsNoToken(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')->willReturn(['someOtherKey' => 'value']);

        $result = $this->service->updatePassword('oldPass', 'newPass');

        $this->assertSame('unknown-error', $result);
    }

    public function testUpdatePasswordReturnsUnknownErrorOnApiException(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(401));

        $result = $this->service->updatePassword('oldPass', 'newPass');

        $this->assertSame('unknown-error', $result);
    }

    public function testUpdatePasswordDoesNotWriteSessionStorageWhenNotSet(): void
    {
        $service = new UserDetails($this->authService, [], $this->mailTransport);
        $service->setApiClient($this->apiClient);
        $service->setUrlHelper($this->urlHelper);
        $service->setLogger($this->logger);
        // No setSessionStorage()

        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willReturn(['token' => 'new-token', 'expiresIn' => 3600]);
        $this->apiClient->method('httpGet')
            ->willReturn(['id' => 'user-123', 'email' => ['address' => 'u@example.com']]);

        $this->identity->method('setToken');

        $this->sessionStorage->expects($this->never())->method('write');

        $result = $service->updatePassword('oldPass', 'newPass');

        $this->assertTrue($result);
    }

    public function testUpdatePasswordContinuesWhenMailSendFails(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willReturn(['token' => 'new-token', 'expiresIn' => 3600]);
        $this->apiClient->method('httpGet')
            ->willReturn(['id' => 'user-123', 'email' => ['address' => 'u@example.com']]);

        $this->mailTransport->method('send')
            ->willThrowException(new \RuntimeException('SMTP error'));

        $this->identity->method('setToken');
        $this->sessionStorage->expects($this->once())->method('write');

        $result = $this->service->updatePassword('oldPass', 'newPass');

        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // requestEmailUpdate
    // -------------------------------------------------------------------------

    public function testRequestEmailUpdateReturnsTrueOnSuccess(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willReturn(['token' => 'email-change-token']);

        $this->urlHelper->method('generate')
            ->willReturn('/user/change-email-address/verify/email-change-token');

        $this->mailTransport->expects($this->exactly(2))
            ->method('send');

        $result = $this->service->requestEmailUpdate('new@example.com', 'old@example.com');

        $this->assertTrue($result);
    }

    public function testRequestEmailUpdateReturnsErrorCodeWhenAlreadyHasEmail(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(400, 'User already has this email'));

        $result = $this->service->requestEmailUpdate('same@example.com', 'same@example.com');

        $this->assertSame('user-already-has-email', $result);
    }

    public function testRequestEmailUpdateReturnsErrorCodeWhenEmailExists(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(400, 'Email already exists for another user'));

        $result = $this->service->requestEmailUpdate('taken@example.com', 'old@example.com');

        $this->assertSame('email-already-exists', $result);
    }

    public function testRequestEmailUpdateReturnsFailedSendingEmailWhenVerifyMailFails(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')
            ->willReturn(['token' => 'change-token']);

        $this->urlHelper->method('generate')->willReturn('/some/path');

        $this->mailTransport->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                null,
                $this->throwException(new \RuntimeException('Mail error'))
            );

        $result = $this->service->requestEmailUpdate('new@example.com', 'old@example.com');

        $this->assertSame('failed-sending-email', $result);
    }

    // -------------------------------------------------------------------------
    // updateEmailUsingToken
    // -------------------------------------------------------------------------

    public function testUpdateEmailUsingTokenReturnsTrueOnSuccess(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/v2/users/email', ['emailUpdateToken' => 'abc123'])
            ->willReturn(null);

        $this->assertTrue($this->service->updateEmailUsingToken('abc123'));
    }

    public function testUpdateEmailUsingTokenReturnsFalseOnApiException(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(400));

        $this->assertFalse($this->service->updateEmailUsingToken('invalid-token'));
    }

    // -------------------------------------------------------------------------
    // registerAccount
    // -------------------------------------------------------------------------

    public function testRegisterAccountReturnsTrueAndSendsActivationEmail(): void
    {
        $this->apiClient->method('httpPost')
            ->willReturn(['activation_token' => 'act-token-123']);

        $this->urlHelper->method('generate')->willReturn('/register/confirm/act-token-123');

        $this->mailTransport->expects($this->once())
            ->method('send')
            ->with($this->isInstanceOf(MailParameters::class));

        $result = $this->service->registerAccount('new@example.com', 'Pass@word1');

        $this->assertTrue($result);
    }

    public function testRegisterAccountReturnsAddressAlreadyRegisteredWhenDuplicate(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(409, 'username-already-exists'));

        $this->mailTransport->expects($this->once())
            ->method('send');

        $result = $this->service->registerAccount('existing@example.com', 'Pass@word1');

        $this->assertSame('address-already-registered', $result);
    }

    public function testRegisterAccountReturnsApiErrorOnOtherException(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(500, 'server-error'));

        $result = $this->service->registerAccount('new@example.com', 'Pass@word1');

        $this->assertSame('api-error', $result);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    public function testDeleteReturnsTrueOnSuccess(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpDelete')
            ->with('/v2/user/user-123')
            ->willReturn(null);

        $this->assertTrue($this->service->delete());
    }

    public function testDeleteReturnsFalseOnApiException(): void
    {
        $this->apiClient->method('httpDelete')
            ->willThrowException($this->makeApiException(500));

        $this->assertFalse($this->service->delete());
    }

    // -------------------------------------------------------------------------
    // activateAccount
    // -------------------------------------------------------------------------

    public function testActivateAccountReturnsTrueOnSuccess(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/v2/users', ['activationToken' => 'tok'])
            ->willReturn(null);

        $this->assertTrue($this->service->activateAccount('tok'));
    }

    public function testActivateAccountReturnsFalseOnApiException(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(400));

        $this->assertFalse($this->service->activateAccount('bad-token'));
    }

    // -------------------------------------------------------------------------
    // setNewPassword
    // -------------------------------------------------------------------------

    public function testSetNewPasswordReturnsTrueOnSuccess(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/v2/users/password', [
                'passwordToken' => 'reset-tok',
                'newPassword'   => 'NewPass@1',
            ])
            ->willReturn(null);

        $this->assertTrue($this->service->setNewPassword('reset-tok', 'NewPass@1'));
    }

    public function testSetNewPasswordReturnsInvalidTokenOnBadToken(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(400, 'Invalid passwordToken'));

        $this->assertSame('invalid-token', $this->service->setNewPassword('bad', 'NewPass@1'));
    }

    public function testSetNewPasswordReturnsExceptionMessageOnOtherError(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(400, 'some-other-error'));

        $this->assertSame('some-other-error', $this->service->setNewPassword('tok', 'NewPass@1'));
    }

    // -------------------------------------------------------------------------
    // url helper
    // -------------------------------------------------------------------------

    public function testUrlGeneratesRelativePath(): void
    {
        $this->urlHelper->method('generate')
            ->with('some-route', ['id' => '1'])
            ->willReturn('/some/path/1');

        $this->assertSame('/some/path/1', $this->service->url('some-route', ['id' => '1']));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeApiException(int $statusCode, string $message = 'error'): ApiException
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('__toString')->willReturn('{"detail":"' . $message . '"}');

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($statusCode);
        $response->method('getBody')->willReturn($stream);

        return new ApiException($response, $message);
    }
}
