<?php

namespace AuthTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\User;
use Auth\Model\Service\RegistrationService;
use DateTime;

class RegistrationServiceTest extends ServiceTestCase
{
    /**
     * @var RegistrationService
     */
    private $service;

    /**
     * @var string
     */
    private $tokenDetails;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new RegistrationService($this->authUserCollection);
    }

    public function testCreateInvalidUsername()
    {
        $result = $this->service->create('invalid', 'Password123');

        $this->assertEquals('invalid-username', $result);
    }

    public function testCreateUsernameAlreadyExists()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([]));

        $result = $this->service->create('unit@test.com', 'Password123');

        $this->assertEquals('username-already-exists', $result);
    }

    public function testCreateUsernameInvalidPassword()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $result = $this->service->create('unit@test.com', 'invalid');

        $this->assertEquals('invalid-password', $result);
    }

    public function testCreateUsernameSuccess()
    {
        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->authUserCollection->shouldReceive('create')
            ->withArgs(function ($id, $details) {
                //Store generated token details for later validation
                $this->tokenDetails = [
                    'userId' => $id,
                    'activation_token' => $details['activation_token'],
                ];

                return strlen($id) > 20
                    && $details['identity'] === 'unit@test.com'
                    && $details['active'] === false
                    && strlen($details['activation_token']) > 20
                    && password_verify('Password123', $details['password_hash'])
                    && $details['created'] <= new DateTime()
                    && $details['last_updated'] <= new DateTime()
                    && $details['failed_login_attempts'] === 0;
            })->twice()->andReturn(false, true);

        $result = $this->service->create('unit@test.com', 'Password123');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testActivateAccountNotFound()
    {
        $this->authUserCollection->shouldReceive('activate')
            ->withArgs(['activation_token'])->once()
            ->andReturn(null);

        $result = $this->service->activate('activation_token');

        $this->assertEquals('account-not-found', $result);
    }

    public function testActivateSuccessful()
    {
        $this->authUserCollection->shouldReceive('activate')
            ->withArgs(['activation_token'])->once()
            ->andReturn(true);

        $result = $this->service->activate('activation_token');

        $this->assertEquals(true, $result);
    }
}
