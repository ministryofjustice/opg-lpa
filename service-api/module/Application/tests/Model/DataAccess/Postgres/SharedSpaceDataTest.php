<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use Application\Library\MillisecondDateTime;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Postgres\SharedSpaceData;
use Application\Model\Service\SharedSpace\MemberNotInSharedSpaceException;
use ApplicationTest\Helpers;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\Exception\InvalidQueryException;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Update;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;

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
            ->with(['isAdmin' => true])
            ->andReturn($updateMock);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($updateMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')->andReturn($resultMock);
        $resultMock->shouldReceive('getAffectedRows')->andReturn(1);

        // test method
        $sharedSpaceData = new SharedSpaceData($dbWrapperMock, []);
        $sharedSpaceData->updateMemberIsAdmin($sharedSpaceId, $userId, true);

        // no exception thrown means the row was updated successfully
        $this->addToAssertionCount(1);
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

        $sharedSpaceData->updateMemberIsAdmin($sharedSpaceId, $userId, true);
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

        $sharedSpaceData->updateMemberIsAdmin($sharedSpaceId, $userId, true);
    }
}
