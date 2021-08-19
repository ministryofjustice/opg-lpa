<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use DateTime;
use PDOException;
use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\UserData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Repository\User\UpdateEmailUsingTokenResponse;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Sql\Expression as SqlExpression;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Statement;
use Laminas\Db\Sql\Update;

use ApplicationTest\Helpers;


class UserDataTest extends MockeryTestCase
{
    const DATE_PATTERN = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.\d{6}\+0000$/';

    // create a PDO Result mock to test queries which use getByField
    // $isQueryResult: bool
    // $count: int
    // $current: array representing single user result
    private function makeGetByFieldResult($isQueryResult, $count, $current)
    {
        $result = Mockery::Mock(Result::class);

        $result->allows([
            'isQueryResult' => $isQueryResult,
            'count' => $count,
            'current' => $current,
        ]);

        return $result;
    }

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
        $resultMock = $this->makeGetByFieldResult($isQueryResult, $count, []);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                UserData::USERS_TABLE,
                ['identity' => $username],
                ['limit' => 1]
            )
            ->andReturn($resultMock);

        // expect null back if result is empty; otherwise expect
        // a call to the result which returns a UserInterface implementation
        $expected = null;
        if ($isQueryResult && $count > 0) {
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

    // TODO failure path once exception handling has been refactored out (see LPAL-487)
    public function testCreate()
    {
        $expected = TRUE;

        $id = '1234';

        $details = [
            'identity' => 'foo',
            'password_hash' => 'hash',
            'activation_token' => 'act',
            'active' => TRUE,
            'created' => new DateTime(),
            'last_updated' => new DateTime(),
            'failed_login_attempts' => 0,
        ];

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $insertMock = Mockery::Mock(Insert::class);
        $statementMock = Mockery::Mock(Statement::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')->andReturn($insertMock);

        $insertMock->shouldReceive('values')
            ->with(Mockery::on(function ($data) use ($id, $details) {
                if ($data['id'] !== $id) {
                    return FALSE;
                }

                // check date times are formatted as strings;
                // note that the field name for 'last_updated' in the db is 'updated'...
                $dateFields = ['created', 'updated'];

                foreach ($dateFields as $dateField) {
                    if (!preg_match(self::DATE_PATTERN, $data[$dateField])) {
                        return FALSE;
                    }
                }

                // check other field values (excluding dates) match the details passed in
                foreach (array_diff(array_keys($details), ['created', 'last_updated']) as $key) {
                    if ($details[$key] !== $data[$key]) {
                        return FALSE;
                    }
                }

                return TRUE;
            }))
            ->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute');

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->create($id, $details);

        // assertions
        $this->assertEquals($expected, $actual);
    }

    public function testUpdateEmailUsingToken() : void
    {
        $id = 'ddddddd';
        $token = '1234';
        $newEmail = 'mrfoo@uat.digital.justice.gov.uk';

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $updateMock = Mockery::Mock(Update::class);
        $statementMock = Mockery::Mock(Statement::class);
        $updateResultsMock = Mockery::Mock(Result::class);

        // query for token returns single user with matching token
        $getByFieldResult1 = $this->makeGetByFieldResult(
            TRUE,
            1,
            [
                'id' => $id,
                'email_update_request' => json_encode([
                    'token' => [
                        'expiresAt' => '9999-01-01T00:00:00.00000+0000'
                    ],
                    'email' => $newEmail,
                ])
            ]
        );

        // query by email returns no matching user
        $getByFieldResult2 = $this->makeGetByFieldResult(TRUE, 0, null);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->andReturn($getByFieldResult1, $getByFieldResult2);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sqlMock->shouldReceive('update')->andReturn($updateMock);

        $updateMock->shouldReceive('where')
            ->with(['id' => $id])
            ->andReturn($updateMock);

        $updateMock->shouldReceive('set')
            ->with(Mockery::on(function ($data) use ($newEmail) {
                return $data['identity'] === $newEmail &&
                    preg_match(self::DATE_PATTERN, $data['updated']) &&
                    is_null($data['email_update_request']);
            }))
            ->andReturn($updateMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')
            ->andReturn($updateResultsMock);

        $updateResultsMock->shouldReceive('getAffectedRows')->andReturn(1);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->updateEmailUsingToken($token);

        // assertions
        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $actual);
    }
}