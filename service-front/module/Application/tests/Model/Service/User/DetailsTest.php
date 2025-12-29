<?php

declare(strict_types=1);

namespace ApplicationTest\Model\Service\User;

use Hamcrest\MatcherAssert;
use Application\Model\Service\Mail\Exception\InvalidArgumentException;
use Application\Model\Service\Session\SessionUtility;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\AbstractEmailService;
use Application\Model\Service\Mail\MailParameters;
use Application\Model\Service\User\Details;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use ApplicationTest\Model\Service\ServiceTestHelper;
use MakeShared\DataModel\User\User;
use MakeSharedTest\DataModel\FixturesData;
use Hamcrest\Matchers;
use Mockery;
use Mockery\MockInterface;

final class DetailsTest extends AbstractEmailServiceTest
{
    private Client|MockInterface $apiClient;
    private LoggerInterface|MockInterface $logger;
    private Details $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(Client::class);
        $this->logger = Mockery::spy(LoggerInterface::class);

        $this->service = new Details(
            $this->authenticationService,
            $this->config,
            $this->mailTransport,
            $this->helperPluginManager
        );
        $this->service->setApiClient($this->apiClient);
        $this->service->setLogger($this->logger);
    }

    public function setUpIdentity(
        int $getIdentityTimes = 1,
        int $idTimes = 1,
        int $toArrayTimes = 1,
        int $tokenTimes = 1,
        ?string $id = 'test-id',
        ?array $toArray = [],
        ?string $token = 'test-token'
    ): MockInterface {
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->times($idTimes)->andReturn($id);
        $identity->shouldReceive('toArray')->times($toArrayTimes)->andReturn($toArray);
        $identity->shouldReceive('token')->times($tokenTimes)->andReturn($token);

        $this->authenticationService->shouldReceive('getIdentity')->times($getIdentityTimes)->andReturn($identity);

        return $identity;
    }

    public function testGetUserDetails(): void
    {
        $this->setUpIdentity(1, 1, 0, 0);

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andReturn('{"test": "response"}');

        $result = $this->service->getUserDetails();

        $this->assertEquals(new User('{"test": "response"}'), $result);
    }

    public function testGetUserDetailsError(): void
    {
        $this->setUpIdentity(2, 2, 0, 0);

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andThrow(Mockery::mock(ApiException::class));

        $result = $this->service->getUserDetails();

        $this->assertEquals(false, $result);
    }

    public function testUpdateAllDetailsUpdateAll(): void
    {
        $this->setUpIdentity(4, 3, 0, 0);

        $currentUserJson = FixturesData::getUserJson();

        $updatedUserData =
        [
            'createdAt' => '2017-01-30T13:11:32.695000+0000',
            'updatedAt' => '2017-01-30T13:11:35.580000+0000',
            'dob' => ['date' => '2000-02-01T00:00:00.000000+0000'],
            'id' => 'e551d8b14c408f7efb7358fb258f1b13',
            'name' => [
                'title' => 'Lord',
                'first' => 'Test',
                'last' => 'User'
            ],
            'address' => [
                'address1' => '1 A Road',
                'address2' => 'L2',
                'address3' => 'L3',
                'postcode' => 'T57 0NO'
            ],
            'email' => [
                'address' => 'test@email.com'
            ]
        ];

        $this->apiClient->shouldReceive('httpGet')->withArgs(['/v2/user/test-id'])->once()->andReturn($currentUserJson);
        $this->apiClient->shouldReceive('httpPut')
            ->withArgs([
                '/v2/user/test-id',
                $updatedUserData,
            ])
            ->once()->andReturn('test response');

        $result = $this->service->updateAllDetails([
            'createdAt' => '2017-01-30T13:11:32.695000+0000',
            'updatedAt' => '2017-01-30T13:11:35.580000+0000',
            'dob-date' => '2000-02-01T00:00:00.000000+0000',
            'id' => 'e551d8b14c408f7efb7358fb258f1b13',
            'name' => [
                'title' => 'Lord',
                'first' => 'Test',
                'last' => 'User'
            ],
            'address' => [
                'address1' => '1 A Road',
                'address2' => 'L2',
                'address3' => 'L3',
                'postcode' => 'T57 0NO'
            ],
            'email' => [
                'address' => 'test@email.com'
            ]
        ]);

        $this->assertEquals('test response', $result);
    }

    public function testUpdateAllDetailsEmptyAll(): void
    {
        $this->setUpIdentity(4, 3, 0, 0);

        $currentUserJson = FixturesData::getUserJson();

        $updatedUserData = json_decode($currentUserJson, true);

        $updatedUserData['createdAt'] = '2017-06-30T13:11:32.695000+0000';
        $updatedUserData['updatedAt'] = '2017-06-30T13:11:35.580000+0000';
        $updatedUserData['address'] = null;
        $updatedUserData['dob'] = null;

        $this->apiClient->shouldReceive('httpGet')->withArgs(['/v2/user/test-id'])->once()->andReturn($currentUserJson);
        $this->apiClient->shouldReceive('httpPut')->withArgs([
            '/v2/user/test-id',
            $updatedUserData,
        ])->once()->andReturn('test response');

        $result = $this->service->updateAllDetails(['address' => null]);

        $this->assertEquals('test response', $result);
    }

    public function testUpdateAllInvalidData(): void
    {
        $this->setUpIdentity(4, 3, 0, 0);

        $currentUserJson = FixturesData::getUserJson();

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andReturn($currentUserJson);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to save details');

        $result = $this->service->updateAllDetails(['id' => '123']);

        $this->assertEquals('test response', $result);
    }

    public function testRequestEmailUpdate(): void
    {
        $this->setUpIdentity(3, 2, 0, 1);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once()->andReturn();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        // Stub out the url() view helper
        $verifyEmailUrl = 'https://verify.email.url';
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function () use ($verifyEmailUrl): string {
                return $verifyEmailUrl;
            });

        $expectedOldMailParameters = new MailParameters(
            'old@email.address',
            AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_NOTIFY,
            ['newEmailAddress' => 'new@email.address']
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedOldMailParameters));

        $expectedNewMailParameters = new MailParameters(
            'new@email.address',
            AbstractEmailService::EMAIL_NEW_EMAIL_ADDRESS_VERIFY,
            ['changeEmailAddressUrl' => $verifyEmailUrl]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedNewMailParameters));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals(true, $result);
    }

    public function testRequestEmailUpdateNoToken(): void
    {
        $this->setUpIdentity(3, 2, 0, 1, 'test-id', [], null);

        $this->apiClient->shouldReceive('updateToken')->once()->andReturn(null);
        $this->apiClient->shouldReceive('httpPost')->once()->andReturn();

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('unknown-error', $result);
    }

    public function testRequestEmailUpdateErrorSendingNewEmailReceived(): void
    {
        $this->setUpIdentity(4, 3, 0, 1);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        // Stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function (): string {
                return 'https://foo.bar';
            });

        // Email to the old address fails
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParameters): bool {
                return $mailParameters->getToAddresses() === ['old@email.address'];
            }))
            ->once()
            ->andThrow(Mockery::mock(InvalidArgumentException::class));

        // Email to new address succeeds
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParameters): bool {
                return $mailParameters->getToAddresses() === ['new@email.address'];
            }))
            ->once();

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        // We treat this as a success, because confirmation arrived at new address
        $this->assertEquals(true, $result);
    }

    public function testRequestEmailUpdateErrorSendingVerifyEmail(): void
    {
        $this->setUpIdentity(4, 3, 0);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        // Stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function (): string {
                return 'https://verify.email.url/';
            });

        // Email to the old address succeeds
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParameters): bool {
                return $mailParameters->getToAddresses() === ['old@email.address'];
            }))
            ->once();

        // Email to new address fails
        $this->mailTransport->shouldReceive('send')
            ->with(Mockery::on(function ($mailParameters): bool {
                return $mailParameters->getToAddresses() === ['new@email.address'];
            }))
            ->once()
            ->andThrow(Mockery::mock(InvalidArgumentException::class));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        // Treat this as a failure, as new address didn't receive the email
        $this->assertEquals('failed-sending-email', $result);
    }

    public function testRequestEmailUpdateErrorEmailNotChanged(): void
    {
        $this->setUpIdentity(4, 3, 0);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('User already has this email'));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('user-already-has-email', $result);
    }

    public function testRequestEmailUpdateErrorEmailOfAnotherUser(): void
    {
        $this->setUpIdentity(4, 3, 0);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Email already exists for another user'));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('email-already-exists', $result);
    }

    public function testRequestEmailUpdateUnknownApiException(): void
    {
        $this->setUpIdentity(4, 3, 0);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('unknown-error', $result);
    }

    public function testUpdateEmailUsingToken(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/email', ['emailUpdateToken' => 'test-token']])
            ->once();

        $result = $this->service->updateEmailUsingToken('test-token');

        $this->assertEquals(true, $result);
    }

    public function testUpdateEmailUsingTokenApiError(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/email', ['emailUpdateToken' => 'test-token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->updateEmailUsingToken('test-token');

        $this->assertEquals(false, $result);
    }

    public function testUpdatePassword(): void
    {
        $identity = $this->setUpIdentity(3, 2, 0);
        $identity->shouldReceive('setToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs([
                '/v2/users/test-id/password',
                ['currentPassword' => 'old-password',
                    'newPassword' => 'new-password']
            ])->once()
            ->andReturn(['token' => 'test-token']);

        $user = (object)['email' => (object)['address' => 'test@email.com']];
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($user)
            ->byDefault();

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_PASSWORD_CHANGED,
            ['email' => 'test@email.com']
        );

        $this->service->setSessionUtility($sessionUtility);

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals(true, $result);
    }

    public function testUpdatePasswordTemplateError(): void
    {

        $identity = $this->setUpIdentity(4, 3, 0);
        $identity->shouldReceive('setToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs([
                '/v2/users/test-id/password',
                ['currentPassword' => 'old-password',
                    'newPassword' => 'new-password']
            ])->once()
            ->andReturn(['token' => 'test-token']);

        $user = (object)['email' => (object)['address' => 'test@email.com']];
        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility->shouldReceive('getFromMvc')
            ->withArgs(['UserDetails', 'user'])
            ->andReturn($user)
            ->byDefault();

        $this->service->setSessionUtility($sessionUtility);

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals(true, $result);
    }

    public function testUpdatePasswordNoToken(): void
    {
        $this->setUpIdentity(3, 2, 0);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->with(
                '/v2/users/test-id/password',
                [
                    'currentPassword' => 'old-password',
                    'newPassword' => 'new-password'
                ]
            )
            ->once()
            ->andReturn(null);

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testUpdatePasswordTemplateApiException(): void
    {
        $this->setUpIdentity(3, 2, 0);

        $this->apiClient->shouldReceive('updateToken')
            ->with('test-token')
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testGetTokenInfo(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['authToken' => 'test-token']])
            ->once()
            ->andReturn(['expiresIn' => 10000]);

        $result = $this->service->getTokenInfo('test-token');

        $this->assertEquals([
            'success' => true,
            'failureCode' => null,
            'expiresIn' => 10000,
        ], $result);
    }

    public function testGetTokenInfoApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['authToken' => 'test-token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->getTokenInfo('test-token');

        MatcherAssert::assertThat([
            'success' => false,
            'failureCode' => 500,
            'expiresIn' => null,
        ], Matchers::equalTo($result));
    }

    public function testDelete(): void
    {
        $this->setUpIdentity(2, 2, 0, 0);

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/test-id'])
            ->once();

        $result = $this->service->delete();

        $this->assertEquals(true, $result);
    }

    public function testDeleteApiException(): void
    {
        $this->setUpIdentity(3, 3, 0, 0);

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->delete();

        $this->assertEquals(false, $result);
    }

    public function testRequestPasswordResetEmail(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        // stub out the url() view helper
        $forgotPasswordUrl = 'https://forgot.password.url/';
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function () use ($forgotPasswordUrl): string {
                return $forgotPasswordUrl;
            });

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_PASSWORD_RESET,
            ['forgotPasswordUrl' => $forgotPasswordUrl]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testRequestPasswordResetEmailPostReturnsIncorrectType(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(null);

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals('unknown-error', $result);
    }

    public function testRequestPasswordResetEmailAccountNotActivated(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        // stub out the url() view helper
        $activateAccountUrl = 'https://activate.account.url/';
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function () use ($activateAccountUrl): string {
                return $activateAccountUrl;
            });

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE,
            ['activateAccountUrl' => $activateAccountUrl]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testRequestPasswordResetEmailApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(false, $result);
    }

    public function testRequestPasswordResetEmailNotFoundApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Not found', 404));

        // stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function (): string {
                return 'https://foo.bar/';
            });

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once();

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testRequestPasswordResetEmailApiExceptionCausesException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Not found', 404));

        // stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function (): string {
                return 'https://foo.bar/';
            });

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testRequestPasswordResetSendingResetSendApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        // stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function (): string {
                return 'https://foo.bar/';
            });

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testAccountActivateEmailSendApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        // stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function (): string {
                return 'https://foo.bar/';
            });

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testSetNewPassword(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once();

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals(true, $result);
    }

    public function testSetNewPasswordResponseNotEmpty(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once()
            ->andReturn([]);

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testSetNewPasswordApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals('Test error', $result);
    }

    public function testSetNewPasswordApiExceptionInvalidToken(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Invalid passwordToken'));

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals('invalid-token', $result);
    }

    public function testRegisterAccount(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $activateAccountUrl = 'https://activate.account.url/';

        // stub out the url() view helper
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function () use ($activateAccountUrl): string {
                return $activateAccountUrl;
            });

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE,
            ['activateAccountUrl' => $activateAccountUrl]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals(true, $result);
    }

    public function testRegisterAccountNoActivationToken(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([]);

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testRegisterAccountFailedSendingEmail(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        // stub out the url() view helper
        $activateAccountUrl = 'https://activate.account.url/';
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function () use ($activateAccountUrl): string {
                return $activateAccountUrl;
            });

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testRegisterAccountApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('api-error', $result);
    }

    public function testDuplicateRegisterAccountWarningEmailSend(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('username-already-exists'));

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once();

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('address-already-registered', $result);
    }

    public function testDuplicateRegisterAccountWarningEmailSendApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('username-already-exists'));

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::anInstanceOf(MailParameters::class))
            ->once()
            ->andThrow(new InvalidArgumentException());

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('failed-sending-warning-email', $result);
    }

    public function testResendActivationEmail(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        // stub out the url() view helper
        $activateAccountUrl = 'https://activate.account.url/';
        $this->helperPluginManager->shouldReceive('get')
            ->with('url')
            ->andReturn(function () use ($activateAccountUrl): string {
                return $activateAccountUrl;
            });

        $expectedMailParameters = new MailParameters(
            'test@email.com',
            AbstractEmailService::EMAIL_ACCOUNT_ACTIVATE,
            ['activateAccountUrl' => $activateAccountUrl]
        );

        $this->mailTransport->shouldReceive('send')
            ->with(Matchers::equalTo($expectedMailParameters))
            ->once();

        $result = $this->service->resendActivateEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testResendActivationEmailNoActivationToken(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn([]);

        $result = $this->service->resendActivateEmail('test@email.com');

        $this->assertEquals(false, $result);
    }

    public function testResendActivationEmailApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->resendActivateEmail('test@email.com');

        $this->assertEquals(false, $result);
    }

    public function testActivateAccount(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['activationToken' => 'test-token']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $result = $this->service->activateAccount('test-token');

        $this->assertEquals(true, $result);
    }

    public function testActivateAccountApiException(): void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['activationToken' => 'test-token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->activateAccount('test-token');

        $this->assertEquals(false, $result);
    }
}
