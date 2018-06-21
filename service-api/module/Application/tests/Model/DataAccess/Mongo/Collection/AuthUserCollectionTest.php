<?php

namespace ApplicationTest\Model\DataAccess\Mongo\Collection;

use Application\Model\DataAccess\Mongo\Collection\User;
use Application\Model\DataAccess\Mongo\Collection\AuthUserCollection;
use DateTime;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\RuntimeException as MongoException;
use MongoDB\Driver\ReadPreference;
use MongoDB\InsertOneResult;
use MongoDB\UpdateResult;

class AuthUserCollectionTest extends MockeryTestCase
{
    /**
     * @var AuthUserCollection
     */
    private $authUserCollection;

    /**
     * @var MockInterface|Collection
     */
    private $mongoCollection;

    protected function setUp()
    {
        $this->mongoCollection = Mockery::mock(Collection::class);

        $this->authUserCollection = new AuthUserCollection($this->mongoCollection);
    }

    public function testGetByUsernameNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['identity' => 'unit@test.com']])
            ->once()->andReturn(null);

        $result = $this->authUserCollection->getByUsername('unit@test.com');

        $this->assertEquals(null, $result);
    }

    public function testGetByUsername()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['identity' => 'unit@test.com']])
            ->once()->andReturn(['_id' => 1]);

        /** @var User $result */
        $result = $this->authUserCollection->getByUsername('unit@test.com');

        $this->assertEquals(1, $result->id());
    }

    public function testGetByIdNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['_id' => 1]])
            ->once()->andReturn(null);

        $result = $this->authUserCollection->getById(1);

        $this->assertEquals(null, $result);
    }

    public function testGetById()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['_id' => 1]])
            ->once()->andReturn(['_id' => 1]);

        /** @var User $result */
        $result = $this->authUserCollection->getById(1);

        $this->assertEquals(1, $result->id());
    }

    public function testGetByAuthTokenNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['auth_token.token' => 'unit-test']])
            ->once()->andReturn(null);

        $result = $this->authUserCollection->getByAuthToken('unit-test');

        $this->assertEquals(null, $result);
    }

    public function testGetByAuthToken()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['auth_token.token' => 'unit-test']])
            ->once()->andReturn(['_id' => 1]);

        /** @var User $result */
        $result = $this->authUserCollection->getByAuthToken('unit-test');

        $this->assertEquals(1, $result->id());
    }

    public function testGetByResetTokenNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs(function ($filter) {
            $date = $filter['password_reset_token.expiresAt']['$gt'];
            return $filter['password_reset_token.token'] === 'unit-test' && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second');
        })->once()->andReturn(null);

        $result = $this->authUserCollection->getByResetToken('unit-test');

        $this->assertEquals(null, $result);
    }

    public function testGetByResetToken()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs(function ($filter) {
            $date = $filter['password_reset_token.expiresAt']['$gt'];
            return $filter['password_reset_token.token'] === 'unit-test' && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second');
        })->once()->andReturn(['_id' => 1]);

        /** @var User $result */
        $result = $this->authUserCollection->getByResetToken('unit-test');

        $this->assertEquals(1, $result->id());
    }

    public function testUpdateLastLoginTime()
    {
        $dbResult = Mockery::mock(UpdateResult::class);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $date = $update['$set']['last_login'];
            return $filter === ['_id' => 1] && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second')
                && $update['$unset']['inactivity_flags'] === true
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->updateLastLoginTime(1);

        $this->assertEquals($dbResult, $result);
    }

    public function testResetFailedLoginCounter()
    {
        $dbResult = Mockery::mock(UpdateResult::class);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs([
            ['_id' => 1],
            ['$set' => ['failed_login_attempts' => 0]],
            ['upsert' => false, 'multiple' => false]
        ])->once()->andReturn($dbResult);

        $result = $this->authUserCollection->resetFailedLoginCounter(1);

        $this->assertEquals($dbResult, $result);
    }

    public function testIncrementFailedLoginCounter()
    {
        $dbResult = Mockery::mock(UpdateResult::class);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $date = $update['$set']['last_failed_login'];
            return $filter === ['_id' => 1] && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second')
                && $update['$inc']['failed_login_attempts'] === 1
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->incrementFailedLoginCounter(1);

        $this->assertEquals($dbResult, $result);
    }

    public function testCreateMongoException()
    {
        $this->mongoCollection->shouldReceive('insertOne')->withArgs([['_id' => 1]])
            ->once()->andThrow(new MongoException());

        $result = $this->authUserCollection->create(1, []);

        $this->assertEquals(false, $result);
    }

    public function testCreate()
    {
        $dbResult = Mockery::mock(InsertOneResult::class);
        $dbResult->shouldReceive('getInsertedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('insertOne')->withArgs(function ($details) {
            $date = $details['created'];
            return $details['_id'] === 1 && $date instanceof UTCDateTime
                && $date->toDateTime() >= new DateTime('-1 second');
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->create(1, ['created' => new DateTime()]);

        $this->assertEquals(true, $result);
    }

    public function testDeleteNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['_id' => 1]])
            ->once()->andReturn(null);

        $result = $this->authUserCollection->delete(1);

        $this->assertEquals(null, $result);
    }

    public function testDelete()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['_id' => 1]])
            ->once()->andReturn(['_id' => 1]);

        $this->mongoCollection->shouldReceive('replaceOne')->withArgs(function ($filter, $replacement) {
            $date = $replacement['deletedAt'];
            return $filter['_id'] === 1 && $replacement['_id'] === 1 && $date instanceof UTCDateTime
                && $date->toDateTime() >= new DateTime('-1 second');
        })->once();

        $result = $this->authUserCollection->delete(1);

        $this->assertEquals(true, $result);
    }

    public function testActivateNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['activation_token' => 'unit-test']])
            ->once()->andReturn(null);

        $result = $this->authUserCollection->activate('unit-test');

        $this->assertEquals(null, $result);
    }

    public function testActivate()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs([['activation_token' => 'unit-test']])
            ->once()->andReturn(['_id' => 1]);

        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $dateActivated = $update['$set']['activated'];
            $lastUpdatedDate = $update['$set']['last_updated'];
            return $filter === ['_id' => 1]
                && $dateActivated instanceof UTCDateTime && $dateActivated->toDateTime() > new DateTime('-1 second')
                && $lastUpdatedDate instanceof UTCDateTime && $lastUpdatedDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['active'] === true
                && $update['$unset']['activation_token'] === true
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->activate('unit-test');

        $this->assertEquals(true, $result);
    }

    public function testSetNewPassword()
    {
        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $lastUpdatedDate = $update['$set']['last_updated'];
            return $filter === ['_id' => 1]
                && $lastUpdatedDate instanceof UTCDateTime && $lastUpdatedDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['password_hash'] === 'Password123'
                && $update['$unset']['auth_token'] === true
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->setNewPassword(1, 'Password123');

        $this->assertEquals(true, $result);
    }

    public function testModifyAuthTokenMongoException()
    {
        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $updatedAtDate = $update['$set']['auth_token.updatedAt'];
            $expiresAtDate = $update['$set']['auth_token.expiresAt'];
            return $filter === ['_id' => 1]
                && $updatedAtDate instanceof UTCDateTime && $updatedAtDate->toDateTime() > new DateTime('-1 second')
                && $expiresAtDate instanceof UTCDateTime
                && $expiresAtDate->toDateTime() > new DateTime('+1 minute -1 second')
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andThrow(new MongoException());

        $result = $this->authUserCollection->extendAuthToken(1, new DateTime('+1 minute'));

        $this->assertEquals(false, $result);
    }

    public function testModifyAuthToken()
    {
        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $updatedAtDate = $update['$set']['auth_token.updatedAt'];
            $expiresAtDate = $update['$set']['auth_token.expiresAt'];
            return $filter === ['_id' => 1]
                && $updatedAtDate instanceof UTCDateTime && $updatedAtDate->toDateTime() > new DateTime('-1 second')
                && $expiresAtDate instanceof UTCDateTime
                && $expiresAtDate->toDateTime() > new DateTime('+1 minute -1 second')
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->extendAuthToken(1, new DateTime('+1 minute'));

        $this->assertEquals(true, $result);
    }

    public function testSetAuthToken()
    {
        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $createdAtDate = $update['$set']['auth_token.createdAt'];
            $updatedAtDate = $update['$set']['auth_token.updatedAt'];
            $expiresAtDate = $update['$set']['auth_token.expiresAt'];
            return $filter === ['_id' => 1]
                && $createdAtDate instanceof UTCDateTime && $createdAtDate->toDateTime() > new DateTime('-1 second')
                && $updatedAtDate instanceof UTCDateTime && $updatedAtDate->toDateTime() > new DateTime('-1 second')
                && $expiresAtDate instanceof UTCDateTime
                && $expiresAtDate->toDateTime() > new DateTime('+1 minute -1 second')
                && $update['$set']['auth_token.token'] === 'unit-test'
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->setAuthToken(1, new DateTime('+1 minute'), 'unit-test');

        $this->assertEquals(true, $result);
    }

    public function testAddPasswordResetToken()
    {
        $dbResult = Mockery::mock(UpdateResult::class);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $createdAtDate = $update['$set']['password_reset_token']['createdAt'];
            return $filter === ['_id' => 1]
                && $createdAtDate instanceof UTCDateTime && $createdAtDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['password_reset_token']['token'] === 'unit-test'
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->addPasswordResetToken(1, [
            'token' => 'unit-test',
            'createdAt' => new DateTime()
        ]);

        $this->assertEquals($dbResult, $result);
    }

    public function testUpdatePasswordUsingTokenNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->once()->andReturn(null);

        $result = $this->authUserCollection->updatePasswordUsingToken('unit-test', 'Password123');

        $this->assertEquals('invalid-token', $result);
    }

    public function testUpdatePasswordUsingToken()
    {
        $this->mongoCollection->shouldReceive('findOne')->once()->andReturn(['_id' => 1]);

        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $lastUpdatedDate = $update['$set']['last_updated'];
            return $filter === ['_id' => 1]
                && $lastUpdatedDate instanceof UTCDateTime && $lastUpdatedDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['password_hash'] === 'Password123'
                && $update['$unset'] === ['password_reset_token' => true, 'auth_token' => true]
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->updatePasswordUsingToken('unit-test', 'Password123');

        $this->assertEquals(true, $result);
    }

    public function testAddEmailUpdateTokenAndNewEmail()
    {
        $dbResult = Mockery::mock(UpdateResult::class);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $createdAtDate = $update['$set']['email_update_request']['token']['createdAt'];
            return $filter === ['_id' => 1]
                && $createdAtDate instanceof UTCDateTime && $createdAtDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['email_update_request']['token']['token'] === 'unit-test'
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->addEmailUpdateTokenAndNewEmail(1, [
            'token' => 'unit-test',
            'createdAt' => new DateTime()
        ], 'unit@test.com');

        $this->assertEquals($dbResult, $result);
    }

    public function testUpdateEmailUsingTokenNotFound()
    {
        $this->mongoCollection->shouldReceive('findOne')->withArgs(function ($filter) {
            $date = $filter['email_update_request.token.expiresAt']['$gt'];
            return $filter['email_update_request.token.token'] === 'unit-test' && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second');
        })->once()->andReturn(null);

        $result = $this->authUserCollection->updateEmailUsingToken('unit-test');

        $this->assertEquals('invalid-token', $result);
    }

    public function testUpdateEmailUsingTokenUsernameAlreadyExists()
    {
        $this->mongoCollection->shouldReceive('findOne')
            ->withArgs([['identity' => 'unit@test.com']])->once()
            ->andReturn(['_id' => 2]);

        $this->mongoCollection->shouldReceive('findOne')->withArgs(function ($filter) {
            $date = $filter['email_update_request.token.expiresAt']['$gt'];
            return $filter['email_update_request.token.token'] === 'unit-test' && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second');
        })->once()->andReturn(['_id' => 1, 'email_update_request' => ['email' => 'unit@test.com']]);

        $result = $this->authUserCollection->updateEmailUsingToken('unit-test');

        $this->assertEquals('username-already-exists', $result);
    }

    public function testUpdateEmailUsingTokenUsernameFalse()
    {
        $this->mongoCollection->shouldReceive('findOne')
            ->withArgs([['identity' => 'unit@test.com']])->once()->andReturn(null);

        $this->mongoCollection->shouldReceive('findOne')->withArgs(function ($filter) {
            $date = $filter['email_update_request.token.expiresAt']['$gt'];
            return $filter['email_update_request.token.token'] === 'unit-test' && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second');
        })->once()->andReturn(['_id' => 1, 'email_update_request' => ['email' => 'unit@test.com']]);

        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(0);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $lastUpdatedDate = $update['$set']['last_updated'];
            return $filter === ['_id' => 1]
                && $lastUpdatedDate instanceof UTCDateTime && $lastUpdatedDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['identity'] === 'unit@test.com'
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->updateEmailUsingToken('unit-test');

        $this->assertEquals(false, $result);
    }

    public function testUpdateEmailUsingTokenUsername()
    {
        $this->mongoCollection->shouldReceive('findOne')
            ->withArgs([['identity' => 'unit@test.com']])->once()->andReturn(null);

        $this->mongoCollection->shouldReceive('findOne')->withArgs(function ($filter) {
            $date = $filter['email_update_request.token.expiresAt']['$gt'];
            return $filter['email_update_request.token.token'] === 'unit-test' && $date instanceof UTCDateTime
                && $date->toDateTime() > new DateTime('-1 second');
        })->once()->andReturn(['_id' => 1, 'email_update_request' => ['email' => 'unit@test.com']]);

        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('getModifiedCount')->once()->andReturn(1);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs(function ($filter, $update, $options) {
            $lastUpdatedDate = $update['$set']['last_updated'];
            return $filter === ['_id' => 1]
                && $lastUpdatedDate instanceof UTCDateTime && $lastUpdatedDate->toDateTime() > new DateTime('-1 second')
                && $update['$set']['identity'] === 'unit@test.com'
                && $options === ['upsert' => false, 'multiple' => false];
        })->once()->andReturn($dbResult);

        $result = $this->authUserCollection->updateEmailUsingToken('unit-test');

        $this->assertEquals(new User(['_id' => 1, 'email_update_request' => ['email' => 'unit@test.com']]), $result);
    }

    public function testGetAccountsInactiveSinceNoExcludeFlag()
    {
        $date = new DateTime();

        $this->mongoCollection->shouldReceive('find')->withArgs([[
            '$or' => [
                ['last_login' => ['$lt' => new UTCDateTime($date)]],
                ['last_login' => ['$lt' => $date->getTimestamp()]],
            ],
        ]])->once()->andReturn([['_id' => 1]]);

        $result = $this->authUserCollection->getAccountsInactiveSince($date);

        $this->assertEquals([new User(['_id' => 1])], iterator_to_array($result));
    }

    public function testGetAccountsInactiveSinceExcludeFlag()
    {
        $date = new DateTime();

        $this->mongoCollection->shouldReceive('find')->withArgs([[
            '$or' => [
                ['last_login' => ['$lt' => new UTCDateTime($date)]],
                ['last_login' => ['$lt' => $date->getTimestamp()]],
            ],
            'inactivity_flags' => ['$nin' => ['unit-test']]
        ]])->once()->andReturn([['_id' => 1]]);

        $result = $this->authUserCollection->getAccountsInactiveSince($date, 'unit-test');

        $this->assertEquals([new User(['_id' => 1])], iterator_to_array($result));
    }

    public function testSetInactivityFlag()
    {
        $dbResult = Mockery::mock(UpdateResult::class);
        $dbResult->shouldReceive('isAcknowledged')->once()->andReturn(true);

        $this->mongoCollection->shouldReceive('updateOne')->withArgs([
            ['_id' => 1],
            ['$addToSet' => ['inactivity_flags' => 'unit-test']],
            ['upsert' => false, 'multiple' => false]
        ])->once()->andReturn($dbResult);

        $result = $this->authUserCollection->setInactivityFlag(1, 'unit-test');

        $this->assertEquals(true, $result);
    }

    public function testGetAccountsUnactivatedOlderThan()
    {
        $date = new DateTime();

        $this->mongoCollection->shouldReceive('find')->withArgs([[
            'active' => ['$ne' => true],
            'created' => ['$lt' => new UTCDateTime($date)]
        ]])->once()->andReturn([['_id' => 1]]);

        $result = $this->authUserCollection->getAccountsUnactivatedOlderThan($date);

        $this->assertEquals([new User(['_id' => 1])], iterator_to_array($result));
    }

    public function testCountAccounts()
    {
        $this->mongoCollection->shouldReceive('count')->withArgs([
            ['identity' => ['$exists' => true]],
            ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]
        ])->once()->andReturn(1);

        $result = $this->authUserCollection->countAccounts();

        $this->assertEquals(1, $result);
    }

    public function testCountActivatedAccounts()
    {
        $this->mongoCollection->shouldReceive('count')->withArgs([
            [
                'identity' => ['$exists' => true],
                '$or' => [
                    ['active' => ['$eq' => true]],
                    ['active' => ['$eq' => 'Y']],
                ]
            ],
            ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]
        ])->once()->andReturn(1);

        $result = $this->authUserCollection->countActivatedAccounts();

        $this->assertEquals(1, $result);
    }

    public function testcountActivatedAccountsSince()
    {
        $date = new DateTime();

        $this->mongoCollection->shouldReceive('count')->withArgs([
            [
                'identity' => ['$exists' => true],
                '$or' => [
                    ['active' => ['$eq' => true]],
                    ['active' => ['$eq' => 'Y']],
                ],
                'activated' => ['$gte' => new UTCDateTime($date)]
            ],
            ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]
        ])->once()->andReturn(1);

        $result = $this->authUserCollection->countActivatedAccounts($date);

        $this->assertEquals(1, $result);
    }

    public function testCountDeletedAccounts()
    {
        $this->mongoCollection->shouldReceive('count')->withArgs([
            ['identity' => ['$exists' => false]],
            ['readPreference' => new ReadPreference(ReadPreference::RP_SECONDARY_PREFERRED)]
        ])->once()->andReturn(1);

        $result = $this->authUserCollection->countDeletedAccounts();

        $this->assertEquals(1, $result);
    }
}