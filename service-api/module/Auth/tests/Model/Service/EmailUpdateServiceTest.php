<?php

namespace AuthTest\Model\Service;

use Application\Model\DataAccess\Mongo\Collection\User;
use Auth\Model\Service\EmailUpdateService;
use DateTime;

class EmailUpdateServiceTest extends ServiceTestCase
{
    /**
     * @var EmailUpdateService
     */
    private $service;

    /**
     * @var array
     */
    private $tokenDetails;

    protected function setUp()
    {
        parent::setUp();

        $this->service = new EmailUpdateService($this->authUserCollection);
    }

    public function testGenerateTokenInvalidEmail()
    {
        $result = $this->service->generateToken(1, 'invalid');

        $this->assertEquals('invalid-email', $result);
    }

    public function testGenerateTokenUsernameSameAsCurrent()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['_id' => 1, 'identity' => 'unit@test.com']));

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            '_id' => 1,
            'identity' => 'unit@test.com'
        ]));

        $result = $this->service->generateToken(1, 'unit@test.com');

        $this->assertEquals('username-same-as-current', $result);
    }

    public function testGenerateTokenUsernameAlreadyExists()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['_id' => 1, 'identity' => 'old@test.com']));

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', new User([
            '_id' => 2,
            'identity' => 'unit@test.com'
        ]));

        $result = $this->service->generateToken(1, 'unit@test.com');

        $this->assertEquals('username-already-exists', $result);
    }

    public function testGenerateTokenUserNotFound()
    {
        $this->setUserDataSourceGetByIdExpectation(1, null);

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $result = $this->service->generateToken(1, 'unit@test.com');

        $this->assertEquals('user-not-found', $result);
    }

    public function testGenerateTokenSuccess()
    {
        $this->setUserDataSourceGetByIdExpectation(1, new User(['_id' => 1, 'identity' => 'old@test.com']));

        $this->setUserDataSourceGetByUsernameExpectation('unit@test.com', null);

        $this->authUserCollection->shouldReceive('addEmailUpdateTokenAndNewEmail')
            ->withArgs(function ($id, $token, $newEmail) {
                //Store generated token details for later validation
                $this->tokenDetails = $token;

                $expectedExpires = new DateTime('+' . (EmailUpdateService::TOKEN_TTL - 1) . ' seconds');

                return $id === 1 && strlen($token['token']) > 20
                    && $token['expiresIn'] === EmailUpdateService::TOKEN_TTL && $token['expiresAt'] > $expectedExpires
                    && $newEmail === 'unit@test.com';
            })->once();

        $result = $this->service->generateToken(1, 'unit@test.com');

        $this->assertEquals($this->tokenDetails, $result);
    }

    public function testUpdateEmailUsingToken()
    {
        $this->authUserCollection->shouldReceive('updateEmailUsingToken')->withArgs(['token'])->once();

        $result = $this->service->updateEmailUsingToken('token');

        // Method doesn't return anything
        $this->assertNull($result);
    }
}
