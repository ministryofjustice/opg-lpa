<?php

namespace ApplicationTest\Controller\Version2\Auth;

use Application\Controller\Version2\Auth\UsersController;
use Application\Model\Service\Users\Service;
use Zend\View\Model\JsonModel;
use ZF\ApiProblem\ApiProblem;
use Mockery;

class UsersControllerTest extends AbstractAuthControllerTest
{
    public function setUp()
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

    public function testSearchAction()
    {
        $emailAddress = 'user@name.com';

        //  Set up the data in the params plugin
        $this->params->shouldReceive('fromQuery')
            ->andReturn([
                'email' => $emailAddress,
            ])
            ->once();

        $userSearchReturnData = [
            'email' => $emailAddress,
            'user'  => 'ertyu34565456ytyg',
        ];

        $this->service->shouldReceive('searchByUsername')
            ->with($emailAddress)
            ->andReturn($userSearchReturnData)
            ->once();

        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var JsonModel $result */
        $result = $controller->searchAction();

        $this->assertInstanceOf(JsonModel::class, $result);
        $this->assertEquals($userSearchReturnData, $result->getVariables());
    }

    public function testSearchActionFailed()
    {
        $emailAddress = 'user@name.com';

        //  Set up the data in the params plugin
        $this->params->shouldReceive('fromQuery')
            ->andReturn([
                'email' => $emailAddress,
            ])
            ->once();

        $this->service->shouldReceive('searchByUsername')
            ->with($emailAddress)
            ->andReturnFalse()
            ->once();

        /** @var UsersController $controller */
        $controller = $this->getController(UsersController::class);

        /** @var ApiProblem $result */
        $result = $controller->searchAction();

        $this->assertInstanceOf(ApiProblem::class, $result);

        $data = $result->toArray();

        $this->assertEquals(404, $data['status']);
        $this->assertEquals('No user found with supplied email address', $data['detail']);
    }
}
