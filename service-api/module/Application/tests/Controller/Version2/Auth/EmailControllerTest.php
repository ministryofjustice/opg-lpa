<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\EmailController;
use Application\Model\DataAccess\Repository\User\UpdateEmailUsingTokenResponse;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Application\Model\Service\Email\Service;
use Zend\Http\Header\HeaderInterface;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use Mockery;

class EmailControllerTest extends AbstractAuthControllerTest
{
    public function setUp()
    {
        $this->service = Mockery::mock(Service::class);

        parent::setUp();
    }

    public function testChangeAction()
    {
        $userId = 'abcdef123456';
        $newEmail = 'new@email.com';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $tokenReturnData = [
            'token'     => 'QWERTYUI',
            'expiresIn' => 12345,
            'expiresAt' => 67890,
        ];

        $this->service->shouldReceive('generateToken')
            ->with($userId, $newEmail)
            ->andReturn($tokenReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully requested update email token', [
                'userId' => $userId,
            ]);

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'newEmail' => $newEmail,
        ]);

        /** @var JsonModel $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($tokenReturnData, $result->getVariables());
    }

    public function testChangeActionFailNoEmail()
    {
        $userId = 'abcdef123456';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();


        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Email address must be passed', $data['detail']);
    }

    public function testChangeActionFailInvalidToken()
    {
        $userId = 'abcdef123456';
        $newEmail = 'new@email.com';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('invalidtok', $userId, false);

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'newEmail' => $newEmail,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(401, $data['status']);
        $this->assertEquals('invalid-token', $data['detail']);
    }

    public function testChangeActionFailTokenGenFailureEmailAddress()
    {
        $userId = 'abcdef123456';
        $newEmail = 'new@email.com';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('generateToken')
            ->with($userId, $newEmail)
            ->andReturn('invalid-email')
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'newEmail' => $newEmail,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Invalid email address', $data['detail']);
    }

    public function testChangeActionFailTokenGenFailureUserNotFound()
    {
        $userId = 'abcdef123456';
        $newEmail = 'new@email.com';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('generateToken')
            ->with($userId, $newEmail)
            ->andReturn('user-not-found')
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'newEmail' => $newEmail,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(404, $data['status']);
        $this->assertEquals('User not found', $data['detail']);
    }

    public function testChangeActionFailTokenGenFailureUserExists()
    {
        $userId = 'abcdef123456';
        $newEmail = 'new@email.com';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('generateToken')
            ->with($userId, $newEmail)
            ->andReturn('username-already-exists')
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'newEmail' => $newEmail,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Email already exists for another user', $data['detail']);
    }

    public function testChangeActionFailTokenGenFailureUserUnchanged()
    {
        $userId = 'abcdef123456';
        $newEmail = 'new@email.com';

        //  Set up the user ID in the params plugin
        $this->params->shouldReceive('fromRoute')
            ->with('userId')
            ->andReturn($userId)
            ->once();

        $this->setToken('tokenval123', $userId);

        $this->service->shouldReceive('generateToken')
            ->with($userId, $newEmail)
            ->andReturn('username-same-as-current')
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'newEmail' => $newEmail,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->changeAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('User already has this email', $data['detail']);
    }

    public function testVerifyAction()
    {
        $userId = 'abcdef123456';
        $emailUpdateToken = 'emailUpdTok';

        $updateEmailUsingTokenResponse = $this->getUpdateEmailUsingTokenResponse($userId);

        $this->service->shouldReceive('updateEmailUsingToken')
            ->with($emailUpdateToken)
            ->andReturn($updateEmailUsingTokenResponse)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('User successfully update email with token', [
                'userId' => $userId,
            ]);

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'emailUpdateToken' => $emailUpdateToken,
        ]);

        /** @var JsonModel $result */
        $result = $controller->verifyAction();

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testVerifyActionFailNoToken()
    {
        $userId = 'abcdef123456';

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class);

        /** @var ApiProblem $result */
        $result = $controller->verifyAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Token must be passed', $data['detail']);
    }

    public function testVerifyActionFailInvalidToken()
    {
        $userId = 'abcdef123456';
        $emailUpdateToken = 'invalidTok';

        $updateEmailUsingTokenResponse = $this->getUpdateEmailUsingTokenResponse($userId, 'invalid-token');

        $this->service->shouldReceive('updateEmailUsingToken')
            ->with($emailUpdateToken)
            ->andReturn($updateEmailUsingTokenResponse)
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'emailUpdateToken' => $emailUpdateToken,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->verifyAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(404, $data['status']);
        $this->assertEquals('Invalid token', $data['detail']);
    }

    public function testVerifyActionFailUsernameExists()
    {
        $userId = 'abcdef123456';
        $emailUpdateToken = 'emailUpdTok';

        $updateEmailUsingTokenResponse = $this->getUpdateEmailUsingTokenResponse($userId, 'username-already-exists');

        $this->service->shouldReceive('updateEmailUsingToken')
            ->with($emailUpdateToken)
            ->andReturn($updateEmailUsingTokenResponse)
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'emailUpdateToken' => $emailUpdateToken,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->verifyAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Email already exists for another user', $data['detail']);
    }

    public function testVerifyActionFailOtherError()
    {
        $userId = 'abcdef123456';
        $emailUpdateToken = 'emailUpdTok';

        $updateEmailUsingTokenResponse = $this->getUpdateEmailUsingTokenResponse($userId, 'BIGBIGERROR');

        $this->service->shouldReceive('updateEmailUsingToken')
            ->with($emailUpdateToken)
            ->andReturn($updateEmailUsingTokenResponse)
            ->once();

        /** @var EmailController $controller */
        $controller = $this->getController(EmailController::class, [
            'emailUpdateToken' => $emailUpdateToken,
        ]);

        /** @var ApiProblem $result */
        $result = $controller->verifyAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(500, $data['status']);
        $this->assertEquals('Unable to update email address', $data['detail']);
    }

    private function setToken($token, $userId, $authSuccess = true)
    {
        $tokenHeader = Mockery::mock(HeaderInterface::class);
        $tokenHeader->shouldReceive('getFieldValue')
            ->andReturn($token)
            ->once();

        $this->request->shouldReceive('getHeader')
            ->with('Token')
            ->andReturn($tokenHeader)
            ->once();

        $this->authenticationService->shouldReceive('withToken')
            ->with($token, false)
            ->andReturn(($authSuccess ? [
                'userId' => $userId,
            ] : false))
            ->once();
    }

    private function getUpdateEmailUsingTokenResponse($userId, $errorMessage = null)
    {
        $updateEmailUsingTokenResponse = Mockery::mock(UpdateEmailUsingTokenResponse::class);

        $updateEmailUsingTokenResponse->shouldReceive('error')
            ->andReturn(!is_null($errorMessage))
            ->once();

        if (!is_null($errorMessage)) {
            $updateEmailUsingTokenResponse->shouldReceive('message')
                ->andReturn($errorMessage);
        } else {
            //  Successful response
            $user = Mockery::mock(UserInterface::class);
            $user->shouldReceive('id')
                ->andReturn($userId);

            $updateEmailUsingTokenResponse->shouldReceive('getUser')
                ->andReturn($user)
                ->once();
        }

        return $updateEmailUsingTokenResponse;
    }
}
