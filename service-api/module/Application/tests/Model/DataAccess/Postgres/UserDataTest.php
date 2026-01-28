<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Laminas\Db\Adapter\Driver\Pdo\Statement;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use DateTime;
use PDOException;
use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\UserData;
use Application\Model\DataAccess\Postgres\UserModel;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Repository\User\UpdateEmailUsingTokenResponse;
use Application\Model\DataAccess\Repository\User\UpdatePasswordUsingTokenError;
use Application\Model\DataAccess\Repository\User\UserInterface;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Adapter\Exception\RuntimeException as LaminasDbAdapterRuntimeException;
use Laminas\Db\Sql\Expression as SqlExpression;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\IsNotNull;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Update;
use MakeShared\DataModel\User\User as ProfileUserModel;
use ApplicationTest\Helpers;

class UserDataTest extends MockeryTestCase
{
    // create a PDO Result mock to test queries which use DbWrapper->select()
    // $isQueryResult: bool
    // $count: int
    // $current: array representing single user result
    private static function makeSelectResult($isQueryResult, $count, $current): Result|MockInterface
    {
        $result = Mockery::mock(Result::class);

        $result->allows([
            'isQueryResult' => $isQueryResult,
            'count' => $count,
            'current' => $current,
        ]);

        return $result;
    }

    // create a mock SQL update object from a dbWrapper mock;
    // expectations can then be set on the returned object to cover
    // what should happen in different circumstances;
    // this is mostly to reduce the gruntwork involved in testing updateRow(),
    // which could eventually be done by moving that method to DbWrapper itself
    private function makeUpdateMock($dbWrapperMock)
    {
        $sqlMock = Mockery::mock(Sql::class);
        $updateMock = Mockery::mock(Update::class);
        $statementMock = Mockery::mock(Statement::class);
        $resultMock = Mockery::mock(Result::class);

        // expectations
        $sqlMock->shouldReceive('update')->andReturn($updateMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $resultMock->shouldReceive('getAffectedRows')->andReturn(1);

        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        return $updateMock;
    }

    // data provider for getByUsername, providing the
    // possible result permutations
    public static function getByUsernameDataProvider(): array
    {
        return [
            [['isQueryResult' => false, 'count' => -1]],
            [['isQueryResult' => true, 'count' => 0]],
            [['isQueryResult' => true, 'count' => 1]]
        ];
    }

    #[DataProvider('getByUsernameDataProvider')]
    public function testGetByUsername($data): void
    {
        $username = 'VictorFrankenstein';

        $isQueryResult = $data['isQueryResult'];
        $count = $data['count'];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $resultMock = $this->makeSelectResult($isQueryResult, $count, []);

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
        } else {
            $this->assertInstanceOf($expected, $actual);
        }
    }

    public function testGetByUsernameDatabaseUnavailable(): void
    {
        $username = 'BShelley';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                UserData::USERS_TABLE,
                ['identity' => $username],
                ['limit' => 1]
            )
            ->andThrow(new LaminasDbAdapterRuntimeException('database connect failed'));

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->getByUsername($username);

        $this->assertEquals(null, $actual);
    }

    public function testMatchUsers(): void
    {
        $query = 'Alphonse';
        $offset = '20';
        $limit = '30';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $subselectMock = Mockery::mock(Select::class);
        $selectMock = Mockery::mock(Select::class);
        $statementMock = Mockery::mock(StatementInterface::class);
        $resultMock = $resultMock = Helpers::makePdoResultMock([
            [],
        ]);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $dbWrapperMock->shouldReceive('quoteValue')
            ->with("%{$query}%")
            ->andReturn("%{$query}%");

        $sqlMock->shouldReceive('select')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('from')
            ->with(['a' => ApplicationData::APPLICATIONS_TABLE])
            ->andReturn($subselectMock);

        $sqlMock->shouldReceive('select')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('from')
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

        // key test: is the ILIKE statement case insensitive?
        $selectMock->shouldReceive('where')
            ->with(Mockery::on(function ($expression) use ($query) {
                return $expression->getExpression() == "u.identity ILIKE %{$query}%";
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

    public function testCreate()
    {
        $expected = true;

        $id = '1234';

        $details = [
            'identity' => 'foo',
            'password_hash' => 'hash',
            'activation_token' => 'act',
            'active' => true,
            'created' => new DateTime(),
            'last_updated' => new DateTime(),
            'failed_login_attempts' => 0,
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')->andReturn($insertMock);

        $insertMock->shouldReceive('values')
            ->with(Mockery::on(function ($data) use ($id, $details) {
                if ($data['id'] !== $id) {
                    return false;
                }

                // check date times are formatted as strings;
                // note that the field name for 'last_updated' in the db is 'updated'...
                $dateFields = ['created', 'updated'];

                foreach ($dateFields as $dateField) {
                    if (!Helpers::isGmDateString($data[$dateField])) {
                        return false;
                    }
                }

                // check other field values (excluding dates) match the details passed in
                foreach (array_diff(array_keys($details), ['created', 'last_updated']) as $key) {
                    if ($details[$key] !== $data[$key]) {
                        return false;
                    }
                }

                return true;
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

    public function testCreatePDOException()
    {
        $id = '99999';

        $details = [
            'identity' => 'foo',
            'password_hash' => 'hash',
            'activation_token' => 'act',
            'active' => true,
            'created' => new DateTime(),
            'last_updated' => new DateTime(),
            'failed_login_attempts' => 0,
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')->andReturn($insertMock);

        // we checked expectations for this in the success test
        $insertMock->shouldReceive('values')->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException(
                'something wrong',
                1,
                new PDOException('not the exception we catch', 23505),
            )
        );

        // test method
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(false, $userData->create($id, $details));
    }

    public function testCreateUnexpectedException()
    {
        $id = '99999';

        $details = [
            'identity' => 'foo',
            'password_hash' => 'hash',
            'activation_token' => 'act',
            'active' => true,
            'created' => new DateTime(),
            'last_updated' => new DateTime(),
            'failed_login_attempts' => 0,
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')->andReturn($insertMock);

        // we checked expectations for this in the success test
        $insertMock->shouldReceive('values')->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException('unexpected')
        );

        $this->expectException(InvalidQueryException::class);

        // test
        $userData = new UserData($dbWrapperMock);
        $userData->create($id, $details);

        // ensure all expectations are met
        Mockery::close();
    }

    public function testUpdateEmailUsingToken(): void
    {
        $id = 'ddddddd';
        $token = '1234';
        $newEmail = 'mrfoo@uat.digital.justice.gov.uk';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $updateMock = $this->makeUpdateMock($dbWrapperMock);

        // query for token returns single user with matching token
        $getByFieldResult1 = $this->makeSelectResult(
            true,
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
        $getByFieldResult2 = $this->makeSelectResult(true, 0, null);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->andReturn($getByFieldResult1, $getByFieldResult2);

        $updateMock->shouldReceive('where')
            ->with(['id' => $id])
            ->andReturn($updateMock);

        $updateMock->shouldReceive('set')
            ->with(Mockery::on(function ($data) use ($newEmail) {
                return $data['identity'] === $newEmail &&
                    Helpers::isGmDateString($data['updated']) &&
                    is_null($data['email_update_request']);
            }))
            ->andReturn($updateMock);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->updateEmailUsingToken($token);

        // assertions
        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $actual);
    }

    public function testUpdateEmailUsingTokenFailNoUserToken(): void
    {
        $token = 'scopscoppy';

        // mocks
        $getByFieldResult = $this->makeSelectResult(true, 0, null);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        // query by token returns no user
        $dbWrapperMock->shouldReceive('select')->andReturn($getByFieldResult);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->updateEmailUsingToken($token);

        // assertions
        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $actual);
        $this->assertEquals('invalid-token', $actual->message());
    }

    public function testUpdateEmailUsingTokenFailExpiredToken(): void
    {
        $token = 'scopscoppy';

        // mocks
        $getByFieldResult = $this->makeSelectResult(true, 1, [
            'email_update_request' => json_encode([
                'token' => [
                    'expiresAt' => (new DateTime('-1 day'))->format(DbWrapper::TIME_FORMAT),
                ],
            ]),
        ]);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        // query by token returns no user
        $dbWrapperMock->shouldReceive('select')->andReturn($getByFieldResult);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->updateEmailUsingToken($token);

        // assertions
        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $actual);
        $this->assertEquals('invalid-token', $actual->message());
    }

    public function testUpdateEmailUsingTokenFailDuplicateEmail(): void
    {
        $id = 'addasddssss';
        $token = 'huuukkkksssss';
        $newEmail = 'mrbaz@uat.digital.justice.gov.uk';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // query for token returns single user with matching token
        $getByFieldResult1 = $this->makeSelectResult(
            true,
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

        // query by email returns user with same email
        $getByFieldResult2 = $this->makeSelectResult(true, 1, []);

        // expectations
        $dbWrapperMock->shouldReceive('select')->andReturn($getByFieldResult1, $getByFieldResult2);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->updateEmailUsingToken($token);

        // assertions
        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $actual);
        $this->assertEquals('username-already-exists', $actual->message());
    }

    public function testUpdateEmailUsingTokenNoUserUpdated(): void
    {
        $id = 'ddddddd';
        $token = '1234';
        $newEmail = 'mrfoo@uat.digital.justice.gov.uk';

        // mocks
        $sqlMock = Mockery::mock(Sql::class);
        $updateMock = Mockery::mock(Update::class);
        $statementMock = Mockery::mock(Statement::class);
        $resultMock = Mockery::mock(Result::class);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // query for token returns single user with matching token
        $getByFieldResult1 = $this->makeSelectResult(
            true,
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
        $getByFieldResult2 = $this->makeSelectResult(true, 0, null);

        // expectations
        $sqlMock->shouldReceive('update')->andReturn($updateMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $resultMock->shouldReceive('getAffectedRows')->andReturn(0);

        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $dbWrapperMock->shouldReceive('select')
            ->andReturn($getByFieldResult1, $getByFieldResult2);

        $updateMock->shouldReceive('where')
            ->with(['id' => $id])
            ->andReturn($updateMock);

        $updateMock->shouldReceive('set')
            ->with(Mockery::on(function ($data) use ($newEmail) {
                return $data['identity'] === $newEmail &&
                    Helpers::isGmDateString($data['updated']) &&
                    is_null($data['email_update_request']);
            }))
            ->andReturn($updateMock);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->updateEmailUsingToken($token);

        // assertions
        $this->assertInstanceOf(UpdateEmailUsingTokenResponse::class, $actual);
        $this->assertEquals('nothing-modified', $actual->message());
    }

    // $since: ?DateTime
    // $queryResult: mock Result
    // $expected: int; expected return value
    public static function countActivatedAccountsDataProvider(): array
    {
        $countResultWithFive = self::makeSelectResult(true, 1, ['count' => 5]);
        $countResultWithZero = self::makeSelectResult(true, 1, ['count' => 0]);
        $countResultNotQueryResult = self::makeSelectResult(false, -1, []);
        $countResultNoRecords = self::makeSelectResult(true, 0, []);

        return [
            // specify a datetime; 5 rows returned
            [new DateTime(), $countResultWithFive, 5],

            // no datetime; 5 rows returned
            [null, $countResultWithFive, 5],

            // specify a datetime; 0 rows returned
            [new DateTime(), $countResultWithZero, 0],

            // no datetime; 0 rows returned
            [null, $countResultWithZero, 0],

            // query return value was not a query result
            [null, $countResultNotQueryResult, 0],

            // query return value had no records in it
            [null, $countResultNoRecords, 0],
        ];
    }

    #[DataProvider('countActivatedAccountsDataProvider')]
    public function testCountActivatedAccounts($since, $resultMock, $expected)
    {
        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                UserData::USERS_TABLE,
                Mockery::on(function ($where) use ($since) {
                    // check WHERE argument based on whether we passed a datetime
                    // to countActivatedAccounts()
                    $expression = $where[0];

                    if (is_null($since)) {
                        return is_a($expression, IsNotNull::class) &&
                            $expression->getIdentifier() === 'activated';
                    } else {
                        return is_a($expression, Operator::class) &&
                            Helpers::isGmDateString($expression->getRight()) &&
                            $expression->getOperator() === '>=';
                    }
                }),
                Mockery::on(function ($options) {
                    return $options['columns']['count']->getExpression() === 'COUNT(*)';
                })
            )
            ->andReturn($resultMock);

        // test method
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->countActivatedAccounts($since);

        // assertions
        $this->assertEquals($expected, $actual);
    }

    public function testGetById(): void
    {
        $id = '12345';
        $expected = new UserModel(['id' => $id]);

        // mocks
        $resultMock = $this->makeSelectResult(true, 1, ['id' => $id]);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(UserData::USERS_TABLE, ['id' => $id], ['limit' => 1])
            ->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->getById($id);

        // assertions
        $this->assertEquals($expected, $actual);
        $this->assertEquals($id, $actual->id());
    }

    public function testGetByIdUserNotFound(): void
    {
        $id = '123451111111';

        // mocks - no records returned from query
        $resultMock = $this->makeSelectResult(true, 0, null);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(null, $userData->getById($id));
    }

    public function testGetByAuthToken(): void
    {
        $id = '111111';
        $token = 'strutsandfrets';
        $expression = new SqlExpression("auth_token ->> 'token' = ?", $token);
        $expected = new UserModel(['id' => $id]);

        // mocks
        $resultMock = $this->makeSelectResult(true, 1, ['id' => $id]);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                UserData::USERS_TABLE,
                Mockery::on(function ($where) use ($token) {
                    $expr = $where[0];

                    return $expr->getExpression() === "auth_token ->> 'token' = ?" &&
                        $expr->getParameters()[0] === $token;
                }),
                ['limit' => 1]
            )
            ->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->getByAuthToken($token);

        // assertions
        $this->assertEquals($expected, $actual);
        $this->assertEquals($id, $actual->id());
    }

    public function testGetByAuthTokenUserNotFound(): void
    {
        $token = 'alltheworldisastage';
        $expected = null;

        // mocks - no records returned from query
        $resultMock = $this->makeSelectResult(true, 0, null);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(null, $userData->getByAuthToken($token));
    }

    public function testGetByResetToken(): void
    {
        $id = '111111';
        $token = 'tobeornottobe';
        $expression = new SqlExpression("password_reset_token ->> 'token' = ?", $token);
        $expected = new UserModel(['id' => $id]);

        // mocks
        $resultMock = $this->makeSelectResult(true, 1, ['id' => $id]);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                UserData::USERS_TABLE,
                Mockery::on(function ($where) use ($token) {
                    $expr = $where[0];

                    return $expr->getExpression() === "password_reset_token ->> 'token' = ?" &&
                        $expr->getParameters()[0] === $token;
                }),
                ['limit' => 1]
            )
            ->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);
        $actual = $userData->getByResetToken($token);

        // assertions
        $this->assertEquals($expected, $actual);
        $this->assertEquals($id, $actual->id());
    }

    public function testGetByResetTokenUserNotFound(): void
    {
        $token = 'verilyisaynay';
        $expected = null;

        // mocks - no records returned from query
        $resultMock = $this->makeSelectResult(true, 0, null);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(null, $userData->getByResetToken($token));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testUpdateLastLoginTime(): void
    {
        $id = '12345612121';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) {
            return Helpers::isGmDateString($set['last_login']) &&
                is_null($set['inactivity_flags']);
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->updateLastLoginTime($id);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testResetFailedLoginCounter(): void
    {
        $id = '12345612121';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(['failed_login_attempts' => 0]);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->resetFailedLoginCounter($id);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testIncrementFailedLoginCounter(): void
    {
        $id = '123456121212';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) {
            return Helpers::isGmDateString($set['last_failed_login']) &&
                $set['failed_login_attempts']->getExpression() === 'failed_login_attempts + 1';
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->incrementFailedLoginCounter($id);
    }

    public function testDelete(): void
    {
        $id = '12345613333';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) {
            $ok = true;

            foreach ($set as $key => $value) {
                // all values except 'deleted' are null
                if ($key === 'deleted') {
                    $ok = $ok && Helpers::isGmDateString($value);
                } else {
                    $ok = $ok && is_null($value);
                }

                if (!$ok) {
                    break;
                }
            }

            return $ok;
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(true, $userData->delete($id));
    }

    public function testActivate(): void
    {
        $token = 'umpetsteakripple';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['activation_token' => $token]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) {
            return Helpers::isGmDateString($set['activated']) &&
                Helpers::isGmDateString($set['updated']) &&
                $set['active'] &&
                is_null($set['activation_token']);
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(true, $userData->activate($token));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetNewPassword(): void
    {
        $id = '912345613333';
        $newPassword = 'cringe';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($newPassword) {
            return Helpers::isGmDateString($set['updated']) &&
                $set['password_hash'] === $newPassword &&
                $set['auth_token'] === null;
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->setNewPassword($id, $newPassword);
    }

    public function testSetAuthToken(): void
    {
        $id = '912345613333';
        $expires = new DateTime();
        $token = 'thereisalwayssomething';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($expires, $token) {
            $data = json_decode($set['auth_token'], true);

            return $data['token'] === $token &&
                Helpers::isGmDateString($data['updatedAt']) &&
                Helpers::isGmDateString($data['createdAt']) &&
                $data['expiresAt'] === $expires->format(DbWrapper::TIME_FORMAT);
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(true, $userData->setAuthToken($id, $expires, $token));
    }

    public function testUpdateAuthTokenExpiry(): void
    {
        $id = '91234567777';
        $expires = new DateTime();

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($expires) {
            $data = json_decode($set['auth_token']->getParameters()[0], true);
            return $data['expiresAt'] === $expires->format(DbWrapper::TIME_FORMAT) &&
                Helpers::isGmDateString($data['updatedAt']);
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(true, $userData->updateAuthTokenExpiry($id, $expires));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddPasswordResetToken(): void
    {
        $id = '95555567777';
        $token = [
            'token' => 'howlateitis',
            'expiresIn' => 86400,
            'expiresAt' => new DateTime('2023-04-12T17:18+0000'),
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($token) {
            $data = json_decode($set['password_reset_token'], true);

            return $data['token'] === $token['token'] &&
                $data['expiresIn'] === 86400 &&
                $data['expiresAt'] === '2023-04-12T17:18:00.000000+0000';
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->addPasswordResetToken($id, $token);
    }

    public function testUpdatePasswordUsingToken(): void
    {
        $token = 'hermanthefriendlyseagull';
        $newPassword = 'youcannotguessthis';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);

        $updateMock->shouldReceive('where')->with(Mockery::on(function ($where) use ($token) {
            $expr = $where[0];
            return $expr->getExpression() === "password_reset_token ->> 'token' = ?" &&
                $expr->getParameters()[0] === $token;
        }));

        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($newPassword) {
            return $set['password_reset_token'] === null &&
                $set['auth_token'] === null &&
                $set['password_hash'] === $newPassword &&
                Helpers::isGmDateString($set['updated']);
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(null, $userData->updatePasswordUsingToken($token, $newPassword));
    }

    public function testUpdatePasswordUsingTokenFails(): void
    {
        $token = 'lutomanjar';
        $newPassword = 'unbelievablepassword';

        // mocks
        $sqlMock = Mockery::mock(Sql::class);
        $updateMock = Mockery::mock(Update::class);
        $statementMock = Mockery::mock(Statement::class);
        $resultMock = Mockery::mock(Result::class);
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        // expectations
        $updateMock->shouldReceive('where');
        $updateMock->shouldReceive('set');

        $resultMock->shouldReceive('getAffectedRows')->andReturn(0);

        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $sqlMock->shouldReceive('update')->andReturn($updateMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertInstanceOf(
            UpdatePasswordUsingTokenError::class,
            $userData->updatePasswordUsingToken($token, $newPassword)
        );
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testAddEmailUpdateTokenAndNewEmail(): void
    {
        $id = 'yolaaaa';
        $token = [
            'token' => 'spoon',
            'expiresIn' => 86400,
            'expiresAt' => new DateTime('2023-04-13T16:48+0000'),
        ];
        $newEmail = 'toughpassword';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);

        $updateMock->shouldReceive('where')->with(['id' => $id]);

        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($token, $newEmail) {
            $data = json_decode($set['email_update_request'], true);
            return Helpers::isGmDateString($data['token']['expiresAt']) &&
                $data['token']['token'] === $token['token'] &&
                $data['token']['expiresIn'] === $token['expiresIn'] &&
                $data['email'] === $newEmail;
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->addEmailUpdateTokenAndNewEmail($id, $token, $newEmail);
    }

    public function testGetAccountsInactiveSince(): void
    {
        $since = new DateTime();
        $excludeFlag = '1-month-notice';

        // mocks
        $results = [
            ['id' => 'user1'],
            ['id' => 'user2'],
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('rewind');
        $resultMock->shouldReceive('valid')->andReturn(true, true, false);
        $resultMock->shouldReceive('current')->andReturn($results[0], $results[1]);
        $resultMock->shouldReceive('next')->andReturn($results[0], $results[1]);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->withArgs(function ($tableName, $where) use ($since, $excludeFlag) {
                $lastLoginClause = $where[0];
                $excludeClause = $where[1];

                return $tableName === UserData::USERS_TABLE &&
                    $lastLoginClause->getLeft() === 'last_login' &&
                    $lastLoginClause->getOperator() === Operator::OPERATOR_LESS_THAN &&
                    $lastLoginClause->getRight() === $since->format('c') &&
                    $excludeClause->getExpression() === "inactivity_flags -> '$excludeFlag' IS NULL";
            })
            ->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);
        $accounts = iterator_to_array(
            $userData->getAccountsInactiveSince($since, $excludeFlag)
        );

        // assertions
        $this->assertEquals(2, count($accounts));
        $this->assertInstanceOf(UserModel::class, $accounts[0]);
        $this->assertEquals('user1', $accounts[0]->id());
        $this->assertInstanceOf(UserModel::class, $accounts[1]);
        $this->assertEquals('user2', $accounts[1]->id());

        Mockery::close();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSetInactivityFlag(): void
    {
        $id = 'qwqwwqwwqqwq';
        $flag = '1-month-notice';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);

        $updateMock->shouldReceive('where')->with(['id' => $id]);

        $updateMock->shouldReceive('set')->with(Mockery::on(function ($set) use ($flag) {
            $expression = $set['inactivity_flags'];

            return $expression->getExpression() ===
                "(CASE WHEN inactivity_flags IS NULL THEN '{}'::JSONB ELSE inactivity_flags END) || ?" &&
                $expression->getParameters()[0] === "{\"$flag\":true}";
        }));

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->setInactivityFlag($id, $flag);
    }

    public function testGetAccountsUnactivatedOlderThan(): void
    {
        $olderThan = new DateTime();

        // mocks
        $results = [
            ['id' => 'user1'],
            ['id' => 'user2'],
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('rewind');
        $resultMock->shouldReceive('valid')->andReturn(true, true, false);
        $resultMock->shouldReceive('current')->andReturn($results[0], $results[1]);
        $resultMock->shouldReceive('next')->andReturn($results[0], $results[1]);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->withArgs(function ($tableName, $where) use ($olderThan) {
                $olderThanClause = $where[0];

                return $tableName === UserData::USERS_TABLE &&
                    $olderThanClause->getLeft() === 'created' &&
                    $olderThanClause->getOperator() === Operator::OPERATOR_LESS_THAN &&
                    $olderThanClause->getRight() === $olderThan->format('c');
            })
            ->andReturn($resultMock);

        // test
        $userData = new UserData($dbWrapperMock);
        $accounts = iterator_to_array(
            $userData->getAccountsUnactivatedOlderThan($olderThan)
        );

        // assertions
        $this->assertEquals(2, count($accounts));
        $this->assertInstanceOf(UserModel::class, $accounts[0]);
        $this->assertEquals('user1', $accounts[0]->id());
        $this->assertInstanceOf(UserModel::class, $accounts[1]);
        $this->assertEquals('user2', $accounts[1]->id());

        Mockery::close();
    }

    public function testCountAccounts(): void
    {
        $result = $this->makeSelectResult(true, 1, ['count' => 5]);

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->withArgs(function ($tableName, $where, $options) {
                $isNullClause = $where[0];
                $columnsExpression = $options['columns']['count']->getExpression();

                return $tableName === UserData::USERS_TABLE &&
                    $isNullClause->getSpecification() === '%1$s IS NOT NULL' &&
                    $isNullClause->getIdentifier() === 'identity' &&
                    $columnsExpression === 'COUNT(*)';
            })
            ->andReturn($result);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(5, $userData->countAccounts());
    }

    public function testCountDeletedAccounts(): void
    {
        $result = $this->makeSelectResult(true, 1, ['count' => 7]);

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->withArgs(function ($tableName, $where, $options) {
                $isNullClause = $where[0];
                $columnsExpression = $options['columns']['count']->getExpression();

                return $tableName === UserData::USERS_TABLE &&
                    $isNullClause->getSpecification() === '%1$s IS NULL' &&
                    $isNullClause->getIdentifier() === 'identity' &&
                    $columnsExpression === 'COUNT(*)';
            })
            ->andReturn($result);

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $this->assertEquals(7, $userData->countDeletedAccounts());
    }

    public function testGetProfileException(): void
    {
        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->andThrow(new LaminasDbAdapterRuntimeException());

        // assertions
        $this->assertNull((new UserData($dbWrapperMock))->getProfile(''));
    }

    public function testGetProfileNoUser(): void
    {
        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->andReturn($this->makeSelectResult(true, 0, []));

        // assertions
        $this->assertNull((new UserData($dbWrapperMock))->getProfile(''));
    }

    public function testGetProfile(): void
    {
        $id = 'barrrraaaaa';

        $user = [
            'id' => $id,
            'created' => new DateTime(),
            'updated' => new DateTime(),
            'profile' => '{"name":{"title":"Prof","first":"Barr","last":"Rrrraaaaa"}}',
            'last_login' => new DateTime(),
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->andReturn($this->makeSelectResult(true, 1, $user));

        // test
        $userData = new UserData($dbWrapperMock);
        $userProfile = $userData->getProfile($id);
        $name = $userProfile->getName();

        // assertions
        $this->assertInstanceOf(ProfileUserModel::class, $userProfile);
        $this->assertEquals($id, $userProfile->getId());
        $this->assertEquals($user['created'], $userProfile->getCreatedAt());
        $this->assertEquals($user['updated'], $userProfile->getUpdatedAt());
        $this->assertEquals($user['last_login'], $userProfile->getLastLoginAt());
        $this->assertEquals('Prof', $name->getTitle());
        $this->assertEquals('Barr', $name->getFirst());
        $this->assertEquals('Rrrraaaaa', $name->getLast());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testSaveProfile(): void
    {
        $id = 'vansant';

        $profileUserModel = new ProfileUserModel([
            'id' => $id,
            'createdAt' => new DateTime(),
            'updatedAt' => new DateTime(),
            'email' => ['address' => 'vansant@nowhere'],
        ]);

        $expectedProfileJson = json_encode($profileUserModel->toArray());

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);

        $updateMock = $this->makeUpdateMock($dbWrapperMock);
        $updateMock->shouldReceive('where')->with(['id' => $id]);
        $updateMock->shouldReceive('set')->with(
            ['profile' => '{"name":null,"address":null,"dob":null,"email":{"address":"vansant@nowhere"},"lastLoginAt":null,"numberOfLpas":null}']
        );

        // test
        $userData = new UserData($dbWrapperMock);

        // assertions
        $userData->saveProfile($profileUserModel);
    }
}
