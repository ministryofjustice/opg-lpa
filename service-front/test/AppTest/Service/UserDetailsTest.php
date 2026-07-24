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

        $this->service = new UserDetails($this->authService, [], $this->mailTransport, $this->logger);
        $this->service->setApiClient($this->apiClient);
        $this->service->setSessionStorage($this->sessionStorage);
        $this->service->setUrlHelper($this->urlHelper);
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
        $service = new UserDetails($this->authService, [], $this->mailTransport, $this->logger);
        $service->setApiClient($this->apiClient);
        $service->setUrlHelper($this->urlHelper);
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

    public function testGetAuthenticationServiceReturnsInjectedInstance(): void
    {
        $this->assertSame($this->authService, $this->service->getAuthenticationService());
    }

    public function testGetConfigReturnsInjectedConfig(): void
    {
        $service = new UserDetails($this->authService, ['feature' => true], $this->mailTransport, $this->logger);

        $this->assertSame(['feature' => true], $service->getConfig());
    }

    public function testGetMailTransportReturnsInjectedInstance(): void
    {
        $this->assertSame($this->mailTransport, $this->service->getMailTransport());
    }

    public function testFormatLpaIdReturnsFormattedValue(): void
    {
        $this->assertSame('A000 1234 5678', $this->service->formatLpaId(12345678));
    }

    public function testMoneyFormatReturnsFormattedValue(): void
    {
        $this->assertSame('41', $this->service->moneyFormat(41));
        $this->assertSame('41.50', $this->service->moneyFormat(41.5));
    }

    public function testUpdateAllDetailsReturnsApiResponseAfterNormalisingAddressAndDob(): void
    {
        $this->ensureLaminasHttpResponseClassExists();

        $user = json_decode(
            (string) file_get_contents(dirname(__DIR__, 4) . '/shared/module/MakeShared/tests/fixtures/user.json'),
            true
        );

        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->with('/v2/user/user-123')
            ->willReturn($user);

        $this->apiClient->expects($this->once())
            ->method('httpPut')
            ->with(
                '/v2/user/user-123',
                $this->callback(static function (array $payload): bool {
                    return $payload['name']['first'] === 'Alex'
                        && $payload['address'] === null
                        && $payload['dob'] === null
                        && $payload['email']['address'] === 'opgcasper+1498828259628334011473@gmail.com';
                })
            )
            ->willReturn(['updated' => true]);

        $result = $this->service->updateAllDetails([
            'name-title' => 'Mr',
            'name-first' => 'Alex',
            'name-last' => 'Smith',
            'address' => null,
        ]);

        $this->assertSame(['updated' => true], $result);
    }

    public function testUpdateAllDetailsThrowsRuntimeExceptionWhenValidationFails(): void
    {
        $this->ensureLaminasHttpResponseClassExists();

        $user = json_decode(
            (string) file_get_contents(dirname(__DIR__, 4) . '/shared/module/MakeShared/tests/fixtures/user.json'),
            true
        );

        $this->apiClient->method('httpGet')->willReturn($user);
        $this->apiClient->expects($this->never())->method('httpPut');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to save details');

        $this->service->updateAllDetails([
            'email-address' => 'not-an-email-address',
        ]);
    }

    public function testUpdateAllDetailsRethrowsApiExceptionWhenUpdateFails(): void
    {
        $this->ensureLaminasHttpResponseClassExists();

        $user = json_decode(
            (string) file_get_contents(dirname(__DIR__, 4) . '/shared/module/MakeShared/tests/fixtures/user.json'),
            true
        );
        $exception = $this->makeApiException(500, 'update failed');

        $this->apiClient->method('httpGet')->willReturn($user);
        $this->apiClient->method('httpPut')->willThrowException($exception);

        $this->expectExceptionObject($exception);

        $this->service->updateAllDetails([
            'name-title' => 'Mr',
            'name-first' => 'Alex',
            'name-last' => 'Smith',
        ]);
    }

    public function testGetTokenInfoReturnsSuccessPayloadWhenAuthenticationSucceeds(): void
    {
        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->with('/v2/authenticate', ['authToken' => 'token-123'])
            ->willReturn(['expiresIn' => 1800]);

        $this->assertSame([
            'success' => true,
            'failureCode' => null,
            'expiresIn' => 1800,
        ], $this->service->getTokenInfo('token-123'));
    }

    public function testGetTokenInfoReturnsFailurePayloadForUnauthorisedToken(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(401, 'expired'));

        $this->assertSame([
            'success' => false,
            'failureCode' => 401,
            'expiresIn' => null,
        ], $this->service->getTokenInfo('expired-token'));
    }

    public function testGetTokenInfoReturnsFailurePayloadForServerError(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(503, 'server error'));

        $this->assertSame([
            'success' => false,
            'failureCode' => 503,
            'expiresIn' => null,
        ], $this->service->getTokenInfo('broken-token'));
    }

    public function testRequestPasswordResetEmailSendsActivationEmailWhenActivationTokenReturned(): void
    {
        $originalHttps = $_SERVER['HTTPS'] ?? null;
        $originalHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'front.example';

        try {
            $this->apiClient->expects($this->once())
                ->method('httpPost')
                ->with('/v2/users/password-reset', ['username' => 'person@example.com'])
                ->willReturn(['activation_token' => 'activate-token']);

            $this->urlHelper->expects($this->once())
                ->method('generate')
                ->with('register/confirm', ['token' => 'activate-token'])
                ->willReturn('/register/confirm/activate-token');

            $this->mailTransport->expects($this->once())
                ->method('send')
                ->with($this->callback(static function (MailParameters $mailParameters): bool {
                    return $mailParameters->getToAddresses() === ['person@example.com']
                        && $mailParameters->getTemplateRef() === UserDetails::EMAIL_ACCOUNT_ACTIVATE
                        && $mailParameters->getData() === [
                            'activateAccountUrl' => 'https://front.example/register/confirm/activate-token',
                        ];
                }));

            $this->assertTrue($this->service->requestPasswordResetEmail('person@example.com'));
        } finally {
            if ($originalHttps === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $originalHttps;
            }

            if ($originalHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $originalHost;
            }
        }
    }

    public function testRequestPasswordResetEmailSendsResetEmailWhenTokenReturned(): void
    {
        $originalHttps = $_SERVER['HTTPS'] ?? null;
        $originalHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'front.example';

        try {
            $this->apiClient->method('httpPost')
                ->willReturn(['token' => 'reset-token']);

            $this->urlHelper->expects($this->once())
                ->method('generate')
                ->with('forgot-password/callback', ['token' => 'reset-token'])
                ->willReturn('/forgot-password/callback/reset-token');

            $this->mailTransport->expects($this->once())
                ->method('send')
                ->with($this->callback(static function (MailParameters $mailParameters): bool {
                    return $mailParameters->getToAddresses() === ['person@example.com']
                        && $mailParameters->getTemplateRef() === UserDetails::EMAIL_PASSWORD_RESET
                        && $mailParameters->getData() === [
                            'forgotPasswordUrl' => 'https://front.example/forgot-password/callback/reset-token', // pragma: allowlist secret
                        ];
                }));

            $this->assertTrue($this->service->requestPasswordResetEmail('person@example.com'));
        } finally {
            if ($originalHttps === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $originalHttps;
            }

            if ($originalHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $originalHost;
            }
        }
    }

    public function testRequestPasswordResetEmailReturnsFailedSendingEmailWhenResetMailFails(): void
    {
        $this->apiClient->method('httpPost')
            ->willReturn(['token' => 'reset-token']);

        $this->urlHelper->method('generate')
            ->willReturn('/forgot-password/callback/reset-token');

        $this->mailTransport->method('send')
            ->willThrowException(new \RuntimeException('Mail failed'));

        $this->assertSame('failed-sending-email', $this->service->requestPasswordResetEmail('person@example.com'));
    }

    public function testRequestPasswordResetEmailReturnsTrueWhenNoAccountMailIsSent(): void
    {
        $originalHttps = $_SERVER['HTTPS'] ?? null;
        $originalHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'front.example';

        try {
            $this->apiClient->method('httpPost')
                ->willThrowException($this->makeApiException(404, 'not found'));

            $this->urlHelper->expects($this->once())
                ->method('generate')
                ->with('register', [])
                ->willReturn('/register');

            $this->mailTransport->expects($this->once())
                ->method('send')
                ->with($this->callback(static function (MailParameters $mailParameters): bool {
                    return $mailParameters->getToAddresses() === ['person@example.com']
                        && $mailParameters->getTemplateRef() === UserDetails::EMAIL_PASSWORD_RESET_NO_ACCOUNT
                        && $mailParameters->getData() === [
                            'signUpUrl' => 'https://front.example/register',
                        ];
                }));

            $this->assertTrue($this->service->requestPasswordResetEmail('person@example.com'));
        } finally {
            if ($originalHttps === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $originalHttps;
            }

            if ($originalHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $originalHost;
            }
        }
    }

    public function testRequestPasswordResetEmailReturnsFailedSendingEmailWhenNoAccountMailFails(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(404, 'not found'));

        $this->urlHelper->method('generate')
            ->willReturn('/register');

        $this->mailTransport->method('send')
            ->willThrowException(new \RuntimeException('Mail failed'));

        $this->assertSame('failed-sending-email', $this->service->requestPasswordResetEmail('person@example.com'));
    }

    public function testRequestPasswordResetEmailReturnsUnknownErrorForUnexpectedPayload(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['unexpected' => true]);

        $this->assertSame('unknown-error', $this->service->requestPasswordResetEmail('person@example.com'));
    }

    public function testRequestPasswordResetEmailReturnsFalseForNonNotFoundApiException(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(500, 'server error'));

        $this->assertFalse($this->service->requestPasswordResetEmail('person@example.com'));
    }

    public function testResendActivateEmailReturnsTrueWhenActivationEmailIsSent(): void
    {
        $originalHttps = $_SERVER['HTTPS'] ?? null;
        $originalHost = $_SERVER['HTTP_HOST'] ?? null;
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'front.example';

        try {
            $this->apiClient->expects($this->once())
                ->method('httpPost')
                ->with('/v2/users/password-reset', ['username' => 'person@example.com'])
                ->willReturn(['activation_token' => 'activate-token']);

            $this->urlHelper->expects($this->once())
                ->method('generate')
                ->with('register/confirm', ['token' => 'activate-token'])
                ->willReturn('/register/confirm/activate-token');

            $this->mailTransport->expects($this->once())
                ->method('send')
                ->with($this->callback(static function (MailParameters $mailParameters): bool {
                    return $mailParameters->getTemplateRef() === UserDetails::EMAIL_ACCOUNT_ACTIVATE
                        && $mailParameters->getData() === [
                            'activateAccountUrl' => 'https://front.example/register/confirm/activate-token',
                        ];
                }));

            $this->assertTrue($this->service->resendActivateEmail('person@example.com'));
        } finally {
            if ($originalHttps === null) {
                unset($_SERVER['HTTPS']);
            } else {
                $_SERVER['HTTPS'] = $originalHttps;
            }

            if ($originalHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $originalHost;
            }
        }
    }

    public function testResendActivateEmailReturnsFalseOnApiException(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(500, 'server error'));

        $this->assertFalse($this->service->resendActivateEmail('person@example.com'));
    }

    public function testRequestEmailUpdateReturnsUnknownErrorWhenApiDoesNotReturnToken(): void
    {
        $this->apiClient->method('updateToken');
        $this->apiClient->method('httpPost')->willReturn(['unexpected' => true]);

        $this->assertSame(
            'unknown-error',
            $this->service->requestEmailUpdate('new@example.com', 'old@example.com')
        );
    }

    public function testUpdatePasswordStillSucceedsWhenUserDetailsCannotBeReloaded(): void
    {
        $this->apiClient->expects($this->once())
            ->method('updateToken')
            ->with('valid-token');

        $this->apiClient->expects($this->once())
            ->method('httpPost')
            ->willReturn(['token' => 'refreshed-token']);

        $this->apiClient->expects($this->once())
            ->method('httpGet')
            ->willThrowException($this->makeApiException(500, 'lookup failed'));

        $this->mailTransport->expects($this->never())->method('send');
        $this->identity->expects($this->once())->method('setToken')->with('refreshed-token');
        $this->sessionStorage->expects($this->once())->method('write')->with($this->identity);

        $this->assertTrue($this->service->updatePassword('oldPass', 'newPass'));
    }

    public function testSetNewPasswordReturnsUnknownErrorWhenApiReturnsUnexpectedPayload(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['unexpected' => true]);

        $this->assertSame('unknown-error', $this->service->setNewPassword('token', 'NewPass@1'));
    }

    public function testRegisterAccountReturnsFailedSendingEmailWhenActivationMailFails(): void
    {
        $this->apiClient->method('httpPost')
            ->willReturn(['activation_token' => 'act-token-123']);

        $this->urlHelper->method('generate')->willReturn('/register/confirm/act-token-123');
        $this->mailTransport->method('send')
            ->willThrowException(new \RuntimeException('SMTP error'));

        $this->assertSame('failed-sending-email', $this->service->registerAccount('new@example.com', 'Pass@word1'));
    }

    public function testRegisterAccountReturnsFailedSendingWarningEmailWhenDuplicateWarningFails(): void
    {
        $this->apiClient->method('httpPost')
            ->willThrowException($this->makeApiException(409, 'username-already-exists'));

        $this->mailTransport->method('send')
            ->willThrowException(new \RuntimeException('SMTP error'));

        $this->assertSame(
            'failed-sending-warning-email',
            $this->service->registerAccount('existing@example.com', 'Pass@word1')
        );
    }

    public function testRegisterAccountReturnsUnknownErrorWhenNoActivationTokenIsReturned(): void
    {
        $this->apiClient->method('httpPost')->willReturn(['unexpected' => true]);

        $this->assertSame('unknown-error', $this->service->registerAccount('new@example.com', 'Pass@word1'));
    }

    public function testSetOneLoginSub(): void
    {
        $oneLoginSub = 'blahblah';

        $this->apiClient->method('updateToken')->with('valid-token');

        $this->apiClient
            ->method('httpPut')
            ->with('/v2/user/user-123', ['oneLoginSub' => $oneLoginSub]);

        $result = $this->service->setOneLoginSub($oneLoginSub);

        $this->assertTrue($result);
    }

    private function ensureLaminasHttpResponseClassExists(): void
    {
        if (!class_exists(\Laminas\Http\Response::class)) {
            eval('namespace Laminas\Http; class Response { public const STATUS_CODE_400 = 400; }');
        }
    }
}
