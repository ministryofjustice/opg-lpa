<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\UserData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Sql\Expression as SqlExpression;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Statement;

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

    public function testMatchUsers() : void
    {
        $query = 'Alphonse';
        $offset = '20';
        $limit = '30';

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $subselectMock = Mockery::Mock(Select::class);
        $selectMock = Mockery::Mock(Select::class);
        $statementMock = Mockery::Mock(Statement::class);
        $resultMock = $resultMock = Helpers::makePdoResultMock([[]]);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('select')
            ->with(['a' => ApplicationData::APPLICATIONS_TABLE])
            ->andReturn($subselectMock);

        $sqlMock->shouldReceive('select')
            ->with(['u' => UserData::USERS_TABLE])
            ->andReturn($selectMock);

        $subselectMock->shouldReceive('columns')
            ->with(Mockery::on(function ($columns) {
                $countExpression = $columns['numberOfLpas'];

                return in_array('user', $columns) &&
                    is_a($countExpression, SqlExpression::class) &&
                    $countExpression->getExpression() === 'COUNT(*)';
            }))
            ->andReturn($subselectMock);

        $subselectMock->shouldReceive('group')
            ->with(['user'])
            ->andReturn($subselectMock);

        $selectMock->shouldReceive('join')
            ->with(
                ['a' => $subselectMock],
                'u.id = a.user',
                ['numberOfLpas'],
                'FULL'
            )
            ->andReturn($selectMock);

        // key test: is the LIKE statement case insensitive?
        $selectMock->shouldReceive('where')
            ->with(Mockery::on(function ($likes) use ($query) {
                // check that lowercase and uppercase versions of $query
                // are covered by the predicates
                $expected = ["%${query}%", '%' . strtolower($query) . '%'];

                foreach ($likes->getPredicates() as $predicate) {
                    // check both predicates have an OR
                    if ($predicate[0] !== 'OR') {
                        return FALSE;
                    }

                    if ($predicate[1]->getIdentifier() !== 'u.identity') {
                        return FALSE;
                    }

                    // remove the LIKE clause for this predicate;
                    // we expect $expected to be empty by the end of this loop
                    $key = array_search($predicate[1]->getLike(), $expected);
                    if ($key !== false) {
                        unset($expected[$key]);
                    }
                }

                return empty($expected);
            }))
            ->andReturn($selectMock);

        $selectMock->shouldReceive('order')
            ->with('identity ASC')
            ->andReturn($selectMock);

        $selectMock->shouldReceive('offset')
            ->with(intval($offset))
            ->andReturn($selectMock);

        $selectMock->shouldReceive('limit')
            ->with(intval($limit))
            ->andReturn($selectMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')
            ->andReturn($resultMock);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = iterator_to_array($userData->matchUsers($query, ['offset' => $offset, 'limit' => $limit]));

        $this->assertEquals(1, count($actual));
    }
}