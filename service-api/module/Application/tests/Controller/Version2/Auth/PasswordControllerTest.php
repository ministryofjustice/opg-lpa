<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\PasswordController;
use Application\Model\Service\Password\Service;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use Mockery;

class PasswordControllerTest extends AbstractAuthControllerTest
{
    public function setUp()
    {
        $this->service = Mockery::mock(Service::class);

        parent::setUp();
    }

    public function testChangeActionWithPassword()
    {
        $userId = 'abcdef123456';
        $currentPassword = 'P@55word';
        $newPassword = 'NewP@55word';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('changePassword')
            ->with($userId, $currentPassword, $newPassword)
            ->andReturn([])
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully change their password', [
                'userId' => 'abcdef123456',
            ]);

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'currentPassword' => $currentPassword,
            'newPassword'     => $newPassword,
        ]);

        /** @var JsonModel $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testChangeActionFailedNoNewPassword()
    {
        $userId = null;

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Missing New Password', $data['detail']);
    }

    public function testChangeActionWithPasswordFailedMissingCurrentPassword()
    {
        $userId = 'abcdef123456';
        $newPassword = 'NewP@55word';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'newPassword'     => $newPassword,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Missing Current Password', $data['detail']);
    }

    public function testChangeActionWithPasswordFailedAuthFail()
    {
        $userId = 'abcdef123456';
        $currentPassword = 'P@55word';
        $newPassword = 'NewP@55word';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId, false);

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'currentPassword' => $currentPassword,
            'newPassword'     => $newPassword,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(401, $data['status']);
        $this->assertEquals('invalid-token', $data['detail']);
    }

    public function testChangeActionWithPasswordFailedError()
    {
        $userId = 'abcdef123456';
        $currentPassword = 'P@55word';
        $newPassword = 'NewP@55word';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('changePassword')
            ->with($userId, $currentPassword, $newPassword)
            ->andReturn('Big big error')
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'currentPassword' => $currentPassword,
            'newPassword'     => $newPassword,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(401, $data['status']);
        $this->assertEquals('Big big error', $data['detail']);
    }

    public function testChangeActionWithToken()
    {
        $userId = null;
        $newPassword = 'NewP@55word';
        $passwordToken = 'qwertyuiopoiuytrewq';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->service->shouldReceive('updatePasswordUsingToken')
            ->with($passwordToken, $newPassword)
            ->andReturnNull()
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully change their password via a reset', [
                'passwordToken' => $passwordToken
            ]);

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'newPassword'     => $newPassword,
            'passwordToken'   => $passwordToken,
        ]);

        /** @var JsonModel $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testChangeActionWithTokenFailedNoPasswordToken()
    {
        $userId = null;
        $newPassword = 'P@55word';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'newPassword'     => $newPassword,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('token required', $data['detail']);
    }

    public function testChangeActionWithTokenFailedInvalidPasswordToken()
    {
        $userId = null;
        $newPassword = 'P@55word';
        $passwordToken = 'invalidPswdTok';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->service->shouldReceive('updatePasswordUsingToken')
            ->with($passwordToken, $newPassword)
            ->andReturn('invalid-token')
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'newPassword'     => $newPassword,
            'passwordToken'   => $passwordToken,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Invalid passwordToken', $data['detail']);
    }

    public function testChangeActionWithTokenFailedInvalidPassword()
    {
        $userId = null;
        $newPassword = 'InvalidP@55word';
        $passwordToken = 'qwertyuiopoiuytrewq';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->service->shouldReceive('updatePasswordUsingToken')
            ->with($passwordToken, $newPassword)
            ->andReturn('invalid-password')
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'newPassword'     => $newPassword,
            'passwordToken'   => $passwordToken,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Invalid password', $data['detail']);
    }

    public function testChangeActionWithTokenFailedUnknownError()
    {
        $userId = null;
        $newPassword = 'NewP@55word';
        $passwordToken = 'qwertyuiopoiuytrewq';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->service->shouldReceive('updatePasswordUsingToken')
            ->with($passwordToken, $newPassword)
            ->andReturn('Big error')
            ->once();

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'newPassword'     => $newPassword,
            'passwordToken'   => $passwordToken,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Unknown error: Big error', $data['detail']);
    }

    public function testResetActionActivationToken()
    {
        $username = 'user@name.com';

        $resetToken = 'resetTok';

        $resetReturnData = [
            'activation_token' => $resetToken
        ];

        $this->service->shouldReceive('generateToken')
            ->with($username)
            ->andReturn($resetReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Password reset token requested', [
                'token'    => $resetToken,
                'username' => $username
            ]);

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'username' => $username,
        ]);

        /** @var JsonModel $result */
        $result = $controller->resetAction();

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testResetActionNormalToken()
    {
        $username = 'user@name.com';

        $resetToken = 'resetTok';

        $resetReturnData = [
            'token' => $resetToken
        ];

        $this->service->shouldReceive('generateToken')
            ->with($username)
            ->andReturn($resetReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('Password reset token requested', [
                'token'    => $resetToken,
                'username' => $username
            ]);

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'username' => $username,
        ]);

        /** @var JsonModel $result */
        $result = $controller->resetAction();

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testResetActionFailedNoUsername()
    {
        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class);

        /** @var ApiProblem $result */
        $result = $controller->resetAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('username must be passed', $data['detail']);
    }

    public function testResetActionFailedUserNotFound()
    {
        $username = 'user@name.com';

        $this->service->shouldReceive('generateToken')
            ->with($username)
            ->andReturn('user-not-found')
            ->once();

        $this->logger->shouldReceive('notice')
            ->with('Password reset request for unknown user', [
                'username' => $username
            ]);

        /** @var PasswordController $controller */
        $controller = $this->getController(PasswordController::class, [
            'username' => $username,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->resetAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(404, $data['status']);
        $this->assertEquals('User not found', $data['detail']);
    }
}
