<?php
/**
 * Integration tests for UserData.
 * This touch the Postgres database and are dependent on seeding being
 * run first.
 */
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\UserData;

require __DIR__ . '/Helpers.php';

final class TestUserData extends MockeryTestCase
{
    private $userData;

    protected function setUp(): void
    {
        $this->userData = new UserData(Helpers::getDbAdapter(), []);
    }

    public function testGetByUsername()
    {
        $testUser = 'seeded_test_user@digital.justice.gov.uk';
        $user = $this->userData->getByUsername($testUser);
        $this->assertEquals($user->username(), $testUser);
        $this->assertEquals($user->numberOfLpas(), -1,
            'numberOfLpas was not set on UserModel; should default to -1');
    }

    public function testMatchUsersBasicQuery()
    {
        $users = iterator_to_array($this->userData->matchUsers('seeded_test_user'));
        $this->assertEquals(count($users), 1);
        $this->assertEquals($users[0]->numberOfLpas(), 13);
        $this->assertEquals($users[0]->username(), 'seeded_test_user@digital.justice.gov.uk');
    }

    public function testMatchUsersPaging()
    {
        $users = iterator_to_array($this->userData->matchUsers('madeup'));
        $this->assertEquals(count($users), 2);

        // return first user from table
        $options = ['limit' => 1];
        $users = iterator_to_array($this->userData->matchUsers('madeup', $options));
        $this->assertEquals(count($users), 1);
        $this->assertEquals($users[0]->username(), 'barmadeup@digital.justice.gov.uk');
        $this->assertEquals($users[0]->numberOfLpas(), 0);

        // return second user from table
        $options = ['offset' => 1, 'limit' => 1];
        $users = iterator_to_array($this->userData->matchUsers('madeup', $options));
        $this->assertEquals(count($users), 1);
        $this->assertEquals($users[0]->username(), 'foo@madeup.digital.justice.gov.uk');
        $this->assertEquals($users[0]->numberOfLpas(), 2);
    }

}
