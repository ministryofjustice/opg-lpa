<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\UserData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;

use ApplicationTest\Helpers;


class UserDataTest extends MockeryTestCase
{
    // data provider for getByUsername, providing the
    // possible result permutations
    public function getByUsernameDataProvider()
    {
        return [
            [['isQueryResult' => FALSE, 'count' => -1]],
            [['isQueryResult' => TRUE, 'count' => 0]],
            [['isQueryResult' => TRUE, 'count' => 1]]
        ];
    }

    /**
     * @dataProvider getByUsernameDataProvider
     */
    public function testGetByUsername($data) : void
    {
        $username = 'VictorFrankenstein';

        $isQueryResult = $data['isQueryResult'];
        $count = $data['count'];

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $resultMock = Mockery::Mock(Result::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                UserData::USERS_TABLE,
                ['identity' => $username],
                ['limit' => 1]
            )
            ->andReturn($resultMock);

        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('count')->andReturn($count);

        // expect null back if result is empty; otherwise expect
        // a call to the result which returns a UserInterface implementation
        $expected = null;
        if ($isQueryResult && $count > 0) {
            $resultMock->shouldReceive('current')->andReturn([]);
            $expected = UserInterface::class;
        }

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->getByUsername($username);

        // assertions
        if ($expected === null) {
            $this->assertEquals($expected, $actual);
        }
        else {
            $this->assertInstanceOf($expected, $actual);
        }
    }
}