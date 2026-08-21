<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use ApplicationTest\Helpers;
use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Postgres\SharedSpaceData;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use Application\Model\Entity\MemberInvite;
use DateTime;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use MakeShared\DataModel\SharedSpace\SharedSpaceMember;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

class SharedSpaceDataTest extends MockeryTestCase
{
    public function testCreate(): void
    {
        $id = 'shared-space-1';

        $details = [
            'name'         => 'My Space',
            'created'      => new MillisecondDateTime(),
            'last_updated' => new MillisecondDateTime(),
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')
            ->with(SharedSpaceData::SHARED_SPACE)
            ->andReturn($insertMock);

        $insertMock->shouldReceive('values')
            ->with([
                'id'      => $id,
                'name'    => $details['name'],
                'created' => $details['created']->format(DbWrapper::TIME_FORMAT),
                'updated' => $details['last_updated']->format(DbWrapper::TIME_FORMAT),
            ])
            ->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute');

        // test method
        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->create($id, $details);

        // assertions
        $this->assertTrue($actual);
    }

    public function testCreateRethrowsInvalidQueryException(): void
    {
        $id = 'shared-space-1';

        $details = [
            'name'         => 'My Space',
            'created'      => new MillisecondDateTime(),
            'last_updated' => new MillisecondDateTime(),
        ];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')->andReturn($insertMock);
        $insertMock->shouldReceive('values')->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException(
                'something wrong',
                1,
                new PDOException('unique constraint violation', 23505),
            )
        );

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(InvalidQueryException::class);

        $sharedSpaceData->create($id, $details);
    }

    public function testAddMember(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $userId = 'user-1';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')
            ->with(SharedSpaceData::SHARED_SPACE_MEMBERS)
            ->andReturn($insertMock);

        $insertMock->shouldReceive('values')
            ->with(Mockery::on(function ($data) use ($sharedSpaceId, $userId) {
                return $data['sharedSpaceId'] === $sharedSpaceId
                    && $data['userId'] === $userId
                    && Helpers::isGmDateString($data['created']);
            }))
            ->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute');

        // test method
        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->addMember($sharedSpaceId, $userId);

        // assertions
        $this->assertTrue($actual);
    }

    public function testAddMemberRethrowsInvalidQueryException(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $userId = 'user-1';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $insertMock = Mockery::mock(Insert::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('insert')->andReturn($insertMock);
        $insertMock->shouldReceive('values')->andReturn($insertMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException(
                'something wrong',
                1,
                new PDOException('unique constraint violation', 23505),
            )
        );

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(InvalidQueryException::class);

        $sharedSpaceData->addMember($sharedSpaceId, $userId);
    }

    public function testGetSharedSpace(): void
    {
        $sharedSpaceId = 'shared-space-1';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn(true);
        $resultMock->shouldReceive('count')->andReturn(1);
        $resultMock->shouldReceive('current')->andReturn([
            'id' => $sharedSpaceId,
            'name' => 'My space',
        ]);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->with(
                SharedSpaceData::SHARED_SPACE,
                ['id' => $sharedSpaceId],
                ['limit' => 1]
            )
            ->andReturn($resultMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getSharedSpace($sharedSpaceId);

        $this->assertEquals('My space', $actual);
    }

    public static function noResultsProvider(): array
    {
        return [
            [false, -1],
            [true, 0],
        ];
    }

    #[DataProvider('noResultsProvider')]
    public function testGetSharedSpaceWhenNoResults(bool $isQueryResult, int $count): void
    {
        $sharedSpaceId = 'shared-space-1';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('count')->andReturn($count);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->with(
                SharedSpaceData::SHARED_SPACE,
                ['id' => $sharedSpaceId],
                ['limit' => 1]
            )
            ->andReturn($resultMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getSharedSpace($sharedSpaceId);

        $this->assertNull($actual);
    }

    public static function getSharedSpaceIdForUserDataProvider(): array
    {
        return [
            [['isQueryResult' => false, 'count' => -1]],
            [['isQueryResult' => true, 'count' => 0]],
            [['isQueryResult' => true, 'count' => 1]],
        ];
    }

    #[DataProvider('getSharedSpaceIdForUserDataProvider')]
    public function testGetSharedSpaceIdForUser($data): void
    {
        $userId = 'user-1';
        $sharedSpaceId = 'shared-space-1';

        $isQueryResult = $data['isQueryResult'];
        $count = $data['count'];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $resultMock = Mockery::mock(Result::class);

        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('count')->andReturn($count);

        if ($isQueryResult && $count === 1) {
            $resultMock->shouldReceive('current')->andReturn(['sharedSpaceId' => $sharedSpaceId]);
        }

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                SharedSpaceData::SHARED_SPACE_MEMBERS,
                ['userId' => $userId],
                ['columns' => ['sharedSpaceId'], 'limit' => 1]
            )
            ->andReturn($resultMock);

        // test method
        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getSharedSpaceIdForUser($userId);

        // assertions
        $expected = ($isQueryResult && $count === 1) ? $sharedSpaceId : null;
        $this->assertSame($expected, $actual);
    }

    public function testGetMember(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $memberUserId = 'user-1';

        $row = [
            'name'            => 'My Space',
            'userId'          => $memberUserId,
            'first_name'      => 'Jane',
            'last_name'       => 'Doe',
            'title'           => 'Mrs',
            'isAdmin'         => true,
            'isActive'        => true,
            'created'         => '2024-01-01T00:00:00.000Z',
            'last_login'      => '2024-01-02T00:00:00.000Z',
            'one_login_email' => 'jane@example.com',
            'identity'        => 'jane@example.com',
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn(true);
        $resultMock->shouldReceive('count')->andReturn(1);
        $resultMock->shouldReceive('current')->andReturn($row);

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')->with(['members' => SharedSpaceData::SHARED_SPACE_MEMBERS])->andReturn($selectMock);
        $selectMock->shouldReceive('join')->andReturn($selectMock);
        $selectMock->shouldReceive('where')->with(['sharedSpaceId' => $sharedSpaceId, 'userId' => $memberUserId])->andReturn($selectMock);
        $selectMock->shouldReceive('columns')->with(['id', 'userId', 'isAdmin', 'isActive', 'created'])->andReturn($selectMock);
        $selectMock->shouldReceive('limit')->with(1)->andReturn($selectMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('getSql')->andReturn('SQL');
        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->with($selectMock)->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getMember($sharedSpaceId, $memberUserId);

        $expected = new SharedSpaceMember([
            'sharedSpaceName' => $row['name'],
            'sharedSpaceId'   => $sharedSpaceId,
            'userId'          => $row['userId'],
            'name'            => ['first' => 'Jane', 'last' => 'Doe', 'title' => 'Mrs'],
            'isAdmin'         => true,
            'isActive'        => true,
            'createdAt'       => $row['created'],
            'lastLoginAt'     => $row['last_login'],
            'email'           => $row['one_login_email'],
        ]);

        $this->assertEquals($expected, $actual);
    }

    #[DataProvider('noResultsProvider')]
    public function testGetMemberWhenNoResults(bool $isQueryResult, int $count): void
    {
        $sharedSpaceId = 'shared-space-1';
        $memberUserId = 'user-1';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('count')->andReturn($count);

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')->andReturn($selectMock);
        $selectMock->shouldReceive('join')->andReturn($selectMock);
        $selectMock->shouldReceive('where')->andReturn($selectMock);
        $selectMock->shouldReceive('columns')->andReturn($selectMock);
        $selectMock->shouldReceive('limit')->andReturn($selectMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('getSql')->andReturn('SQL');
        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getMember($sharedSpaceId, $memberUserId);

        $this->assertNull($actual);
    }

    public function testGetMemberRethrowsInvalidQueryException(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $memberUserId = 'user-1';

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')->andReturn($selectMock);
        $selectMock->shouldReceive('join')->andReturn($selectMock);
        $selectMock->shouldReceive('where')->andReturn($selectMock);
        $selectMock->shouldReceive('columns')->andReturn($selectMock);
        $selectMock->shouldReceive('limit')->andReturn($selectMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('getSql')->andReturn('SQL');
        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException('something wrong', 1, new PDOException('unique constraint violation', 23505))
        );

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(InvalidQueryException::class);

        $sharedSpaceData->getMember($sharedSpaceId, $memberUserId);
    }

    public function testGetMembers(): void
    {
        $sharedSpaceId = 'shared-space-1';

        $row = [
            'name'            => 'My Space',
            'userId'          => 'user-1',
            'first_name'      => 'Jane',
            'last_name'       => 'Doe',
            'title'           => 'Mrs',
            'isAdmin'         => false,
            'isActive'        => true,
            'created'         => '2024-01-01T00:00:00.000Z',
            'last_login'      => '2024-01-02T00:00:00.000Z',
            'one_login_email' => 'jane@example.com',
            'identity'        => 'jane@example.com',
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('rewind');
        $resultMock->shouldReceive('valid')->andReturnValues([true, false]);
        $resultMock->shouldReceive('current')->andReturn($row);
        $resultMock->shouldReceive('key')->andReturn(0);
        $resultMock->shouldReceive('next');

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')->with(['members' => SharedSpaceData::SHARED_SPACE_MEMBERS])->andReturn($selectMock);
        $selectMock->shouldReceive('join')->andReturn($selectMock);
        $selectMock->shouldReceive('where')->with(['sharedSpaceId' => $sharedSpaceId])->andReturn($selectMock);
        $selectMock->shouldReceive('columns')->with(['id', 'userId', 'isAdmin', 'isActive', 'created'])->andReturn($selectMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('getSql')->andReturn('SQL');
        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->with($selectMock)->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getMembers($sharedSpaceId);

        $expected = new SharedSpaceMember([
            'sharedSpaceName' => $row['name'],
            'sharedSpaceId'   => $sharedSpaceId,
            'userId'          => $row['userId'],
            'name'            => ['first' => 'Jane', 'last' => 'Doe', 'title' => 'Mrs'],
            'isAdmin'         => false,
            'isActive'        => true,
            'createdAt'       => $row['created'],
            'lastLoginAt'     => $row['last_login'],
            'email'           => $row['one_login_email'],
        ]);

        $this->assertEquals([$expected], $actual);
    }

    public function testGetMembersWhenNoResults(): void
    {
        $sharedSpaceId = 'shared-space-1';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('rewind');
        $resultMock->shouldReceive('valid')->andReturn(false);

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')->andReturn($selectMock);
        $selectMock->shouldReceive('join')->andReturn($selectMock);
        $selectMock->shouldReceive('where')->andReturn($selectMock);
        $selectMock->shouldReceive('columns')->andReturn($selectMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('getSql')->andReturn('SQL');
        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getMembers($sharedSpaceId);

        $this->assertEmpty($actual);
    }

    public function testGetMembersRethrowsInvalidQueryException(): void
    {
        $sharedSpaceId = 'shared-space-1';

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')->andReturn($selectMock);
        $selectMock->shouldReceive('join')->andReturn($selectMock);
        $selectMock->shouldReceive('where')->andReturn($selectMock);
        $selectMock->shouldReceive('columns')->andReturn($selectMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('getSql')->andReturn('SQL');
        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException('something wrong', 1, new PDOException('unique constraint violation', 23505))
        );

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(InvalidQueryException::class);

        $sharedSpaceData->getMembers($sharedSpaceId);
    }

    public static function isAdminDataProvider(): array
    {
        return [
            [['isQueryResult' => false, 'count' => -1, 'isAdmin' => null]],
            [['isQueryResult' => true, 'count' => 0, 'isAdmin' => null]],
            [['isQueryResult' => true, 'count' => 1, 'isAdmin' => true]],
            [['isQueryResult' => true, 'count' => 1, 'isAdmin' => false]],
        ];
    }

    #[DataProvider('isAdminDataProvider')]
    public function testIsAdmin($data): void
    {
        $userId = 'user-1';
        $sharedSpaceId = 'shared-space-1';

        $isQueryResult = $data['isQueryResult'];
        $count = $data['count'];
        $isAdmin = $data['isAdmin'];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $resultMock = Mockery::mock(Result::class);

        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('count')->andReturn($count);

        if ($isQueryResult && $count === 1) {
            $resultMock->shouldReceive('current')->andReturn(['isAdmin' => $isAdmin]);
        }

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                SharedSpaceData::SHARED_SPACE_MEMBERS,
                ['sharedSpaceId' => $sharedSpaceId, 'userId' => $userId],
                ['columns' => ['isAdmin'], 'limit' => 1]
            )
            ->andReturn($resultMock);

        // test method
        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->isAdmin($sharedSpaceId, $userId);

        // assertions
        $expected = ($isQueryResult && $count === 1) ? $isAdmin : false;
        $this->assertSame($expected, $actual);
    }

    #[DoesNotPerformAssertions]
    public function testUpdateMemberIsAdmin(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $userId = 'user-1';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $updateMock = Mockery::mock(Update::class);
        $statementMock = Mockery::mock(StatementInterface::class);
        $resultMock = Mockery::mock(Result::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('update')
            ->with(SharedSpaceData::SHARED_SPACE_MEMBERS)
            ->andReturn($updateMock);

        $updateMock->shouldReceive('where')
            ->with(['sharedSpaceId' => $sharedSpaceId, 'userId' => $userId])
            ->andReturn($updateMock);

        $updateMock->shouldReceive('set')
            ->with(['isAdmin' => true, 'isActive' => true])
            ->andReturn($updateMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($updateMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andReturn($resultMock);
        $resultMock->shouldReceive('getAffectedRows')->andReturn(1);

        // test method
        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $sharedSpaceData->updateMember($sharedSpaceId, $userId, true, true);
    }

    public function testUpdateMemberIsAdminThrowsWhenNoMatchingRow(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $userId = 'user-1';

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $updateMock = Mockery::mock(Update::class);
        $statementMock = Mockery::mock(StatementInterface::class);
        $resultMock = Mockery::mock(Result::class);

        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('update')->andReturn($updateMock);
        $updateMock->shouldReceive('where')->andReturn($updateMock);
        $updateMock->shouldReceive('set')->andReturn($updateMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);
        $statementMock->shouldReceive('execute')->andReturn($resultMock);
        $resultMock->shouldReceive('getAffectedRows')->andReturn(0);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(MemberNotInSharedSpaceException::class);

        $sharedSpaceData->updateMember($sharedSpaceId, $userId, true, true);
    }

    public function testUpdateMemberIsAdminRethrowsInvalidQueryException(): void
    {
        $sharedSpaceId = 'shared-space-1';
        $userId = 'user-1';

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $sqlMock = Mockery::mock(Sql::class);
        $updateMock = Mockery::mock(Update::class);
        $statementMock = Mockery::mock(StatementInterface::class);

        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('update')->andReturn($updateMock);
        $updateMock->shouldReceive('where')->andReturn($updateMock);
        $updateMock->shouldReceive('set')->andReturn($updateMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andThrow(
            new InvalidQueryException(
                'something wrong',
                1,
                new PDOException('unique constraint violation', 23505),
            )
        );

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(InvalidQueryException::class);

        $sharedSpaceData->updateMember($sharedSpaceId, $userId, true, true);
    }

    #[DoesNotPerformAssertions]
    public function testDeleteMember(): void
    {
        $sharedSpaceId = 'space-id';
        $userId = 'user-id';

        $deleteMock = Mockery::mock(Delete::class);
        $deleteMock->shouldReceive('where')
            ->with(['sharedSpaceId' => $sharedSpaceId, 'userId' => $userId])
            ->andReturn($deleteMock);

        $resultMock = Mockery::mock(ResultInterface::class);
        $resultMock->shouldReceive('getAffectedRows')
            ->andReturn(1);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('delete')
            ->with(SharedSpaceData::SHARED_SPACE_MEMBERS)
            ->andReturn($deleteMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($deleteMock)
            ->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $sharedSpaceData->deleteMember($sharedSpaceId, $userId);
    }

    public function testDeleteMemberWhenNothingDeleted(): void
    {
        $sharedSpaceId = 'space-id';
        $userId = 'user-id';

        $deleteMock = Mockery::mock(Delete::class);
        $deleteMock->shouldReceive('where')
            ->andReturn($deleteMock);

        $resultMock = Mockery::mock(ResultInterface::class);
        $resultMock->shouldReceive('getAffectedRows')
            ->andReturn(0);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('delete')
            ->andReturn($deleteMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);

        $this->expectException(MemberNotInSharedSpaceException::class);
        $sharedSpaceData->deleteMember($sharedSpaceId, $userId);
    }

    public function testGetInviteByCodeAndSharedSpaceName(): void
    {
        $sharedSpaceName = 'My Space';
        $invite = new MemberInvite(
            1,
            'my user',
            'my space',
            'first',
            'last',
            'email@example.com',
            true,
            '12341234',
            new DateTime(),
            new DateTime('+7 days'),
        );

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn(true);
        $resultMock->shouldReceive('count')->andReturn(1);
        $resultMock->shouldReceive('current')->andReturn([
            'id' => 1,
            'invitedBy' => $invite->userId,
            'sharedSpaceId' => $invite->sharedSpaceId,
            'firstNames' => $invite->firstNames,
            'lastName' => $invite->lastName,
            'email' => $invite->email,
            'isAdmin' => $invite->isAdmin,
            'code' => $invite->code,
            'created' => $invite->created->format(DbWrapper::TIME_FORMAT),
            'expires' => $invite->expires->format(DbWrapper::TIME_FORMAT),
        ]);

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')
            ->with(['invite' => SharedSpaceData::SHARED_SPACE_INVITES])
            ->andReturn($selectMock);
        $selectMock->shouldReceive('join')
            ->with(['space' => SharedSpaceData::SHARED_SPACE], 'invite.sharedSpaceId = space.id', ['name'])
            ->andReturn($selectMock);
        $selectMock->shouldReceive('where')
            ->with(['invite.code' => $invite->code, 'space.name' => $sharedSpaceName])
            ->andReturn($selectMock);
        $selectMock->shouldReceive('limit')
            ->with(1)
            ->andReturn($selectMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')
            ->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($selectMock)
            ->andReturn($sqlMock);
        $sqlMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getInviteByCodeAndSharedSpaceName($invite->code, $sharedSpaceName);

        $this->assertEquals($invite, $actual);
    }

    #[DataProvider('noResultsProvider')]
    public function testGetInviteByCodeAndSharedSpaceNameWhenNoResults(bool $isQueryResult, int $count): void
    {
        $sharedSpaceName = 'My Space';
        $code = '1243';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('count')->andReturn($count);

        $selectMock = Mockery::mock(Select::class);
        $selectMock->shouldReceive('from')
            ->andReturn($selectMock);
        $selectMock->shouldReceive('join')
            ->andReturn($selectMock);
        $selectMock->shouldReceive('where')
            ->andReturn($selectMock);
        $selectMock->shouldReceive('limit')
            ->andReturn($selectMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('select')
            ->andReturn($selectMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->andReturn($sqlMock);
        $sqlMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getInviteByCodeAndSharedSpaceName($code, $sharedSpaceName);

        $this->assertNull($actual);
    }

    public function testGetInvites(): void
    {
        $invite = new MemberInvite(
            1,
            'my user',
            'my space',
            'first',
            'last',
            'email@example.com',
            true,
            '12341234',
            new DateTime(),
            new DateTime('+7 days'),
        );

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn(true);
        $resultMock->shouldReceive('rewind');
        $resultMock->shouldReceive('valid')->andReturnValues([true, false]);
        $resultMock->shouldReceive('current')->andReturn([
            'id' => 1,
            'invitedBy' => $invite->userId,
            'sharedSpaceId' => $invite->sharedSpaceId,
            'firstNames' => $invite->firstNames,
            'lastName' => $invite->lastName,
            'email' => $invite->email,
            'isAdmin' => $invite->isAdmin,
            'code' => $invite->code,
            'created' => $invite->created->format(DbWrapper::TIME_FORMAT),
            'expires' => $invite->expires->format(DbWrapper::TIME_FORMAT),
        ]);
        $resultMock->shouldReceive('key')->andReturn(0);
        $resultMock->shouldReceive('next');

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->with(SharedSpaceData::SHARED_SPACE_INVITES, ['sharedSpaceId' => $invite->sharedSpaceId])
            ->andReturn($resultMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getInvites($invite->sharedSpaceId);

        $this->assertEquals([$invite], $actual);
    }

    #[DataProvider('noResultsProvider')]
    public function testGetInvitesWhenNoResults(bool $isQueryResult, int $count): void
    {
        $sharedSpaceId = 'shared-space-1';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('isQueryResult')->andReturn($isQueryResult);
        $resultMock->shouldReceive('rewind');
        $resultMock->shouldReceive('valid')->andReturn(false);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('select')
            ->with(SharedSpaceData::SHARED_SPACE_INVITES, ['sharedSpaceId' => $sharedSpaceId])
            ->andReturn($resultMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $actual = $sharedSpaceData->getInvites($sharedSpaceId);

        $this->assertEmpty($actual);
    }

    public function testCreateInvite(): void
    {
        $invite = new MemberInvite(
            1,
            'my user',
            'my space',
            'first',
            'last',
            'email@example.com',
            true,
            '12341234',
            new DateTime(),
            new DateTime('+7 days'),
        );

        $insertMock = Mockery::mock(Insert::class);
        $insertMock->shouldReceive('values')
            ->with([
                'sharedSpaceId' => $invite->sharedSpaceId,
                'invitedBy' => $invite->userId,
                'firstNames' => $invite->firstNames,
                'lastName' => $invite->lastName,
                'email' => $invite->email,
                'isAdmin' => $invite->isAdmin,
                'code' => $invite->code,
                'created' => $invite->created->format(DbWrapper::TIME_FORMAT),
                'expires' => $invite->expires->format(DbWrapper::TIME_FORMAT),
            ])
            ->andReturn($insertMock);

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('current')->andReturn(4);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('execute')->andReturn($resultMock);
        $statementMock->shouldReceive('getSql')->andReturn('SOMETHING');
        $statementMock->shouldReceive('setSql')->with('SOMETHING RETURNING "id"')->andReturn($statementMock);

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('insert')
            ->with(SharedSpaceData::SHARED_SPACE_INVITES)
            ->andReturn($insertMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $id = $sharedSpaceData->createInvite($invite);

        $this->assertEquals(4, $id);
    }

    #[DoesNotPerformAssertions]
    public function testDeleteInvite(): void
    {
        $inviteId = 5;
        $deleteMock = Mockery::mock(Delete::class);
        $deleteMock->shouldReceive('where')
            ->with(['id' => $inviteId])
            ->andReturn($deleteMock);

        $statementMock = Mockery::mock(StatementInterface::class);
        $statementMock->shouldReceive('execute');

        $sqlMock = Mockery::mock(Sql::class);
        $sqlMock->shouldReceive('delete')
            ->with(SharedSpaceData::SHARED_SPACE_INVITES)
            ->andReturn($deleteMock);
        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($deleteMock)
            ->andReturn($statementMock);

        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);

        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $sharedSpaceData->deleteInvite($inviteId);
    }
}
