<?php

namespace ApplicationTest\Model\Service\DataAccess\Mongo;

use Application\Model\Service\DataAccess\Mongo\User;
use DateTime;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\UTCDateTime;

class UserTest extends MockeryTestCase
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

    public function testReturnDateFieldUTCDateTime()
    {
        $date = new UTCDateTime();

        $user = new User(['created' => $date]);

        $this->assertEquals($date->toDateTime(), $user->createdAt());
    }

    public function testReturnDateFieldString()
    {
        $date = new DateTime();

        $user = new User(['created' => $date->format('U')]);

        $this->assertEquals(DateTime::createFromFormat('U', $date->format('U')), $user->createdAt());
    }

    public function testGets()
    {
        $date = new DateTime();

        $user = new User([
            '_id' => '1',
            'identity' => 'unit@test.com',
            'active' => true,
            'password_hash' => 'Password123',
            'created' => $date,
            'last_updated' => $date,
            'deleteAt' => $date,
            'last_login' => $date,
            'activated' => $date,
            'last_failed_login' => $date,
            'failed_login_attempts' => 2,
            'activation_token' => 'activation-token',
            'auth_token' => ['token' => 'auth-token']
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