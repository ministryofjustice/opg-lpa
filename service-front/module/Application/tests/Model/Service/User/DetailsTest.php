<?php

namespace ApplicationTest\Model\Service\User;

use Application\Model\Service\ApiClient\Client;
use Application\Model\Service\ApiClient\Exception\ApiException;
use Application\Model\Service\User\Details;
use ApplicationTest\Model\Service\AbstractEmailServiceTest;
use ApplicationTest\Model\Service\ServiceTestHelper;
use Exception;
use Mockery;
use Mockery\MockInterface;
use Opg\Lpa\DataModel\User\User;
use OpgTest\Lpa\DataModel\FixturesData;
use RuntimeException;
use Zend\Session\Container;

class DetailsTest extends AbstractEmailServiceTest
{
    /**
     * @var $apiClient Client|MockInterface
     */
    private $apiClient;

    /**
     * @var $service Details
     */
    private $service;

    public function setUp() : void
    {
        parent::setUp();

        $this->apiClient = Mockery::mock(Client::class);

        $this->service = new Details($this->authenticationService, [], $this->twigEmailRenderer, $this->mailTransport);
        $this->service->setApiClient($this->apiClient);
    }

    public function setUpIdentity(
        int $getIdentityTimes = 1,
        int $idTimes = 1,
        int $toArrayTimes = 1,
        int $tokenTimes = 1,
        ?string $id = 'test-id',
        ?array $toArray = [],
        ?string $token = 'test-token'
    ) : MockInterface {
        $identity = Mockery::mock();
        $identity->shouldReceive('id')->times($idTimes)->andReturn($id);
        $identity->shouldReceive('toArray')->times($toArrayTimes)->andReturn($toArray);
        $identity->shouldReceive('token')->times($tokenTimes)->andReturn($token);

        $this->authenticationService->shouldReceive('getIdentity')->times($getIdentityTimes)->andReturn($identity);

        return $identity;
    }

    public function testGetUserDetails() : void
    {
        $this->setUpIdentity(1, 1, 0, 0);

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andReturn('{"test": "response"}');

        $result = $this->service->getUserDetails();

        $this->assertEquals(new User('{"test": "response"}'), $result);
    }

    public function testGetUserDetailsError() : void
    {
        $this->setUpIdentity(1, 1, 0, 0);

        $this->apiClient->shouldReceive('httpGet')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andThrow(Mockery::mock(ApiException::class));

        $result = $this->service->getUserDetails();

        $this->assertEquals(false, $result);
    }

    public function testUpdateAllDetailsUpdateAll() : void
    {
        $this->setUpIdentity(3, 2, 1, 0);

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

    public function testUpdateAllDetailsEmptyAll() : void
    {
        $this->setUpIdentity(3, 2, 1, 0);

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

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unable to save details
     */
    public function testUpdateAllInvalidData() : void
    {
        $this->setUpIdentity(2, 1, 1, 0);

        $currentUserJson = FixturesData::getUserJson();

        $this->apiClient->shouldReceive('httpGet')->withArgs(['/v2/user/test-id'])->once()->andReturn($currentUserJson);

        $result = $this->service->updateAllDetails(['id' => '123']);

        $this->assertEquals('test response', $result);
    }

    public function testRequestEmailUpdate() : void
    {
        $this->setUpIdentity(2, 1, 1, 1);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once()->andReturn();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                'old@email.address',
                'email-new-email-address-notify',
                ['newEmailAddress' => 'new@email.address']
            ])->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                'new@email.address',
                'email-new-email-address-verify',
                ['token' => 'test-token']
            ])->once();

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals(true, $result);
    }

    public function testRequestEmailUpdateNoToken() : void
    {
        $this->setUpIdentity(2, 1, 1, 1, 'test-id', [], null);

        $this->apiClient->shouldReceive('updateToken')->once()->andReturn(null);
        $this->apiClient->shouldReceive('httpPost')->once()->andReturn();

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('unknown-error', $result);
    }

    public function testRequestEmailUpdateErrorSendingNewEmailReceived() : void
    {
        $this->setUpIdentity(2, 1, 1, 1);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                'old@email.address',
                'email-new-email-address-notify',
                ['newEmailAddress' => 'new@email.address']
            ])->once()
            ->andThrow(Mockery::mock(Exception::class));
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                'new@email.address',
                'email-new-email-address-verify',
                ['token' => 'test-token']
            ])->once();

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals(true, $result);
    }

    public function testRequestEmailUpdateErrorSendingVerifyEmail() : void
    {
        $this->setUpIdentity(2);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                'old@email.address',
                'email-new-email-address-notify',
                ['newEmailAddress' => 'new@email.address']
            ])->once();
        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs([
                'new@email.address',
                'email-new-email-address-verify',
                ['token' => 'test-token']
            ])->once()
            ->andThrow(Mockery::mock(Exception::class));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testRequestEmailUpdateErrorEmailNotChanged() : void
    {
        $this->setUpIdentity(2);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('User already has this email'));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('user-already-has-email', $result);
    }

    public function testRequestEmailUpdateErrorEmailOfAnotherUser() : void
    {
        $this->setUpIdentity(2);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Email already exists for another user'));

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('email-already-exists', $result);
    }

    public function testRequestEmailUpdateUnknownApiException() : void
    {
        $this->setUpIdentity(2);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/test-id/email', ['newEmail' => 'new@email.address']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->requestEmailUpdate('new@email.address', 'old@email.address');

        $this->assertEquals('unknown-error', $result);
    }

    public function testUpdateEmailUsingToken() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/email', ['emailUpdateToken' => 'test-token']])
            ->once();

        $result = $this->service->updateEmailUsingToken('test-token');

        $this->assertEquals(true, $result);
    }

    public function testUpdateEmailUsingTokenApiError() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/email', ['emailUpdateToken' => 'test-token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->updateEmailUsingToken('test-token');

        $this->assertEquals(false, $result);
    }

    public function testUpdatePassword() : void
    {
        $identity = $this->setUpIdentity(2);
        $identity->shouldReceive('setToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs([
                '/v2/users/test-id/password',
                ['currentPassword' => 'old-password',
                    'newPassword' => 'new-password']
            ])->once()
            ->andReturn(['token' => 'test-token']);

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-password-changed', ['email' => 'test@email.com']])
            ->once();

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals(true, $result);
    }

    public function testUpdatePasswordTemplateError() : void
    {
        $identity = $this->setUpIdentity(2);
        $identity->shouldReceive('setToken')->withArgs(['test-token'])->once();

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs([
                '/v2/users/test-id/password',
                ['currentPassword' => 'old-password',
                    'newPassword' => 'new-password']
            ])->once()
            ->andReturn(['token' => 'test-token']);

        $userDetailSession = new Container();
        $userDetailSession["user"] = json_decode('{"email":{"address":"test@email.com"}}');

        $this->service->setUserDetailsSession($userDetailSession);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-password-changed', ['email' => 'test@email.com']])
            ->once()
            ->andThrow(new Exception());

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals(true, $result);
    }

    public function testUpdatePasswordNoToken() : void
    {
        $this->setUpIdentity(2);

        $this->apiClient->shouldReceive('updateToken')->withArgs(['test-token'])->once();
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs([
                '/v2/users/test-id/password',
                ['currentPassword' => 'old-password',
                    'newPassword' => 'new-password']
            ])->once()
            ->andReturn(null);

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testUpdatePasswordTemplateApiException() : void
    {
        $this->setUpIdentity(1, 0);

        $this->apiClient->shouldReceive('updateToken')
            ->withArgs(['test-token'])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->updatePassword('old-password', 'new-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testGetTokenInfo() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['authToken' => 'test-token']])
            ->once()
            ->andReturn(['test' => 'response']);

        $result = $this->service->getTokenInfo('test-token');

        $this->assertEquals(['test' => 'response'], $result);
    }

    public function testGetTokenInfoApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/authenticate', ['authToken' => 'test-token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->getTokenInfo('test-token');

        $this->assertEquals(false, $result);
    }

    public function testDelete() : void
    {
        $this->setUpIdentity(2, 1, 1, 0);

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/test-id'])
            ->once();

        $result = $this->service->delete();

        $this->assertEquals(true, $result);
    }

    public function testDeleteApiException() : void
    {
        $this->setUpIdentity(2, 1, 1, 0);

        $this->apiClient->shouldReceive('httpDelete')
            ->withArgs(['/v2/user/test-id'])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->delete();

        $this->assertEquals(false, $result);
    }

    public function testRequestPasswordResetEmail() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-password-reset', ['token' => 'test-token']])
            ->once();

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testRequestPasswordResetEmailPostReturnsIncorrectType() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(null);

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals('unknown-error', $result);
    }

    public function testRequestPasswordResetEmailAccountNotActivated() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-account-activate', ['token' => 'test-token']])
            ->once();

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testRequestPasswordResetEmailApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(false, $result);
    }

    public function testRequestPasswordResetEmailNotFoundApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Not found', 404));

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-password-reset-no-account'])
            ->once();

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testRequestPasswordResetEmailApiExceptionCausesException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Not found', 404));

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-password-reset-no-account'])
            ->once()
            ->andThrow(new Exception());

        $result = $this->service->requestPasswordResetEmail('test@email.com');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testSetNewPassword() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once();

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals(true, $result);
    }

    public function testSetNewPasswordResponseNotEmpty() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once()
            ->andReturn([]);

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testSetNewPasswordApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals('Test error', $result);
    }

    public function testSetNewPasswordApiExceptionInvalidToken() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password', ['passwordToken' => 'test-token', 'newPassword' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('Invalid passwordToken'));

        $result = $this->service->setNewPassword('test-token', 'test-password');

        $this->assertEquals('invalid-token', $result);
    }

    public function testRegisterAccount() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-account-activate', ['token' => 'test-token']])
            ->once();

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals(true, $result);
    }

    public function testRegisterAccountNoActivationToken() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn([]);

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('unknown-error', $result);
    }

    public function testRegisterAccountFailedSendingEmail() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-account-activate', ['token' => 'test-token']])
            ->once()
            ->andThrow(new Exception());

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('failed-sending-email', $result);
    }

    public function testRegisterAccountApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('Test error', $result);
    }

    public function testRegisterAccountApiExceptionAlreadyRegistered() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['username' => 'test@email.com', 'password' => 'test-password']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException('username-already-exists'));

        $result = $this->service->registerAccount('test@email.com', 'test-password');

        $this->assertEquals('address-already-registered', $result);
    }

    public function testResendActivationEmail() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $this->mailTransport->shouldReceive('sendMessageFromTemplate')
            ->withArgs(['test@email.com', 'email-account-activate', ['token' => 'test-token']])
            ->once();

        $result = $this->service->resendActivateEmail('test@email.com');

        $this->assertEquals(true, $result);
    }

    public function testResendActivationEmailNoActivationToken() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andReturn([]);

        $result = $this->service->resendActivateEmail('test@email.com');

        $this->assertEquals(false, $result);
    }

    public function testResendActivationEmailApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users/password-reset', ['username' => 'test@email.com']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->resendActivateEmail('test@email.com');

        $this->assertEquals(false, $result);
    }

    public function testActivateAccount() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['activationToken' => 'test-token']])
            ->once()
            ->andReturn(['activation_token' => 'test-token']);

        $result = $this->service->activateAccount('test-token');

        $this->assertEquals(true, $result);
    }

    public function testActivateAccountApiException() : void
    {
        $this->apiClient->shouldReceive('httpPost')
            ->withArgs(['/v2/users', ['activationToken' => 'test-token']])
            ->once()
            ->andThrow(ServiceTestHelper::createApiException());

        $result = $this->service->activateAccount('test-token');

        $this->assertEquals(false, $result);
    }
}
