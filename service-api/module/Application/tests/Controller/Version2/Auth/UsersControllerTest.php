<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\UsersController;
use Application\Library\ApiProblem\ApiProblem;
use Application\Model\Service\Users\Service;
use Laminas\View\Model\JsonModel;
use Mockery;

class UsersControllerTest extends AbstractAuthControllerTestCase
{
    public function setUp(): void
    {
        $this->service = Mockery::mock(Service::class);

        parent::setUp();
    }

    public function testCreateActivateAccount()
    {
        $activationToken = 'ackToken';

        $this->service->shouldReceive('activate')
            ->with($activationToken)
            ->andReturnTrue()
            ->once();

        $this->logger->shouldReceive('info')
            ->with('New user account activated', [
                'activation_token' => $activationToken,
            ]);

        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var JsonModel $result */
        $result = $controller->create([
            'activationToken' => $activationToken,
        ]);

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testCreateActivateAccountFailedCantActivate()
    {
        $activationToken = 'ackToken';

        $this->service->shouldReceive('activate')
            ->with($activationToken)
            ->andReturn('Failure reason')
            ->once();

        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var ApiProblem $result */
        $result = $controller->create([
            'activationToken' => $activationToken,
        ]);

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Failure reason', $data['detail']);
    }

    public function testCreateNewAccount()
    {
        $username = 'user@name.com';
        $password = 'P@55word';

        $accountCreateReturnData = [
            'userId'           => 'qqwertyuiuyt23456789876',
            'activation_token' => 'ackToken',
        ];

        $this->service->shouldReceive('create')
            ->with($username, $password)
            ->andReturn($accountCreateReturnData)
            ->once();

        $this->logger->shouldReceive('info')
            ->with('New user account created', $accountCreateReturnData);

        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var JsonModel $result */
        $result = $controller->create([
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertInstanceOf(JsonModel::class, $result);
    }

    public function testCreateNewAccountFailed()
    {
        $username = 'user@name.com';
        $password = 'P@55word';

        $this->service->shouldReceive('create')
            ->with($username, $password)
            ->andReturn('Failure reason')
            ->once();

        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var ApiProblem $result */
        $result = $controller->create([
            'username' => $username,
            'password' => $password,
        ]);

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Failure reason', $data['detail']);
    }

    public function testCreateFailedNoData()
    {
        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var ApiProblem $result */
        $result = $controller->create([]);

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(400, $data['status']);
        $this->assertEquals('Either activationToken or username & password must be passed', $data['detail']);
    }
}
