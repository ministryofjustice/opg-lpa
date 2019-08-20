<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use DateTime;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\DataAccess\Postgres\UserModel as User;

class UserModelTest extends MockeryTestCase
{

    public function testReturnDateFieldKeyNotFound()
    {
        $user = new User([]);

        $this->assertEquals(false, $user->createdAt());
    }

    public function testReturnDateFieldDateTime()
    {
        $date = new DateTime();

        $user = new User(['created' => $date]);

        $this->assertEquals($date, $user->createdAt());
    }

    public function testReturnDateFieldString()
    {
        $date = new DateTime();

        $user = new User(['created' => $date->format('c')]);

        // We check the timestamps to avoid issues comparing milliseconds.
        $this->assertEquals($date->getTimestamp(), $user->createdAt()->getTimestamp());
    }

    public function testGets()
    {
        $date = new DateTime();

        $user = new User([
            'id' => '1',
            'identity' => 'unit@test.com',
            'active' => true,
            'password_hash' => 'Password123',
            'created' => $date,
            'updated' => $date,
            'deleted' => $date,
            'last_login' => $date,
            'activated' => $date,
            'last_failed_login' => $date,
            'failed_login_attempts' => 2,
            'activation_token' => 'activation-token',
            'auth_token' => json_encode(['token' => 'auth-token'])
        ]);

        $this->assertEquals('1', $user->id());
        $this->assertEquals('unit@test.com', $user->username());
        $this->assertEquals(true, $user->isActive());
        $this->assertEquals('Password123', $user->password());
        $this->assertEquals($date, $user->createdAt());
        $this->assertEquals($date, $user->updatedAt());
        $this->assertEquals($date, $user->deleteAt());
        $this->assertEquals($date, $user->lastLoginAt());
        $this->assertEquals($date, $user->activatedAt());
        $this->assertEquals($date, $user->lastFailedLoginAttemptAt());
        $this->assertEquals(2, $user->failedLoginAttempts());
        $this->assertEquals('activation-token', $user->activationToken());
        $this->assertEquals('auth-token', $user->authToken()->id());
    }

}
