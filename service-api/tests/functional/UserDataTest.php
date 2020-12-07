<?php
/**
 * Script for poking the database.
 */
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\UserData;

require __DIR__ . '/Helpers.php';


final class TestUserData extends MockeryTestCase
{
    private $userData;

    protected function setUp()
    {
        $this->userData = new UserData(Helpers::getDbAdapter(), []);
    }

    public function testMatchUsersBasicQuery()
    {
        $users = iterator_to_array($this->userData->matchUsers('justice'));
        $this->assertEquals(count($users), 1);
        $this->assertEquals($users[0]->numberOfLpas(), 13);
        $this->assertEquals($users[0]->username(), 'seeded_test_user@digital.justice.gov.uk');
    }
}
