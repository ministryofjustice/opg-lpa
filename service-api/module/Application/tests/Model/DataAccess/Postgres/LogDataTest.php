<?php

declare(strict_types=1);

namespace ApplicationTest\Model\DataAccess\Postgres;

use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Postgres\LogData;
use DateTime;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Sql\Delete;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LogDataTest extends MockeryTestCase
{
    public function testAddLog(): void
    {
        $loggedAt = new DateTime('2021-07-04 10:11:12');

        $details = [
            'identity_hash' => 'abc123',
            'type' => 'user-deleted',
            'reason' => 'user-initiated',
            'loggedAt' => $loggedAt,
        ];

        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $insertMock = Mockery::Mock(Insert::class);
        $statementMock = Mockery::Mock(StatementInterface::class);

        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('insert')
            ->with(LogData::DELETION_LOG_TABLE)
            ->andReturn($insertMock);

        $insertMock->shouldReceive('columns')
            ->once()
            ->with(['identity_hash', 'type', 'reason', 'loggedAt']);

        $insertMock->shouldReceive('values')
            ->once()
            ->with([
                'identity_hash' => 'abc123',
                'type' => 'user-deleted',
                'reason' => 'user-initiated',
                'loggedAt' => $loggedAt->format(DbWrapper::TIME_FORMAT),
            ]);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')
            ->once();

        $logData = new LogData($dbWrapperMock);
        $logData->addLog($details);
    }

    public function testGetLogByIdentityHash(): void
    {
        $row = [
            'identity_hash' => 'abc123',
            'type' => 'user-deleted',
            'reason' => 'user-initiated',
            'loggedAt' => '2021-07-04 10:11:12',
        ];

        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $selectMock = Mockery::Mock(Select::class);
        $statementMock = Mockery::Mock(StatementInterface::class);
        $resultMock = Mockery::Mock(Result::class);

        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('select')
            ->with(LogData::DELETION_LOG_TABLE)
            ->andReturn($selectMock);

        $selectMock->shouldReceive('where')
            ->once()
            ->with(['identity_hash' => 'abc123']);
        $selectMock->shouldReceive('order')
            ->once()
            ->with('loggedAt DESC');
        $selectMock->shouldReceive('limit')
            ->once()
            ->with(1);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($selectMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $resultMock->shouldReceive('isQueryResult')->andReturn(true);
        $resultMock->shouldReceive('count')->andReturn(1);
        $resultMock->shouldReceive('current')->andReturn($row);

        $logData = new LogData($dbWrapperMock);
        $actual = $logData->getLogByIdentityHash('abc123');

        $this->assertEquals(new DateTime('2021-07-04 10:11:12'), $actual['loggedAt']);
        $this->assertSame('abc123', $actual['identity_hash']);
        $this->assertSame('user-deleted', $actual['type']);
        $this->assertSame('user-initiated', $actual['reason']);
    }

    public static function noLogResultDataProvider(): array
    {
        return [
            [['isQueryResult' => false, 'count' => -1]],
            [['isQueryResult' => true, 'count' => 0]],
            [['isQueryResult' => true, 'count' => 2]],
        ];
    }

    #[DataProvider('noLogResultDataProvider')]
    public function testGetLogByIdentityHashNoLog(array $resultData): void
    {
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $selectMock = Mockery::Mock(Select::class);
        $statementMock = Mockery::Mock(StatementInterface::class);
        $resultMock = Mockery::Mock(Result::class);

        $dbWrapperMock->shouldReceive('createSql')->andReturn($sqlMock);
        $sqlMock->shouldReceive('select')->andReturn($selectMock);
        $selectMock->shouldReceive('where');
        $selectMock->shouldReceive('order');
        $selectMock->shouldReceive('limit');
        $sqlMock->shouldReceive('prepareStatementForSqlObject')->andReturn($statementMock);
        $statementMock->shouldReceive('execute')->andReturn($resultMock);

        $resultMock->allows($resultData);

        $logData = new LogData($dbWrapperMock);
        $actual = $logData->getLogByIdentityHash('abc123');

        $this->assertNull($actual);
    }

    public function testDeleteExpiredDeletionLogs(): void
    {
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $deleteMock = Mockery::Mock(Delete::class);
        $statementMock = Mockery::Mock(StatementInterface::class);
        $resultMock = Mockery::Mock(Result::class);

        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('delete')
            ->with(LogData::DELETION_LOG_TABLE)
            ->andReturn($deleteMock);

        $deleteMock->shouldReceive('where')
            ->once()
            ->withArgs(function ($where) {
                $operator = $where[0];
                $expected = (new DateTime())->modify('-3 years');
                $actual = new DateTime($operator->getRight());

                return $operator instanceof Operator
                    && $operator->getLeft() === 'loggedAt'
                    && $operator->getOperator() === Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO
                    && abs($actual->getTimestamp() - $expected->getTimestamp()) < 5;
            });

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($deleteMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $resultMock->shouldReceive('getAffectedRows')
            ->andReturn(2);

        $logData = new LogData($dbWrapperMock);
        $actualCount = $logData->deleteExpiredDeletionLogs();

        $this->assertEquals(2, $actualCount);
    }

    public function testDeleteLogsByIdentityHash(): void
    {
        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $deleteMock = Mockery::Mock(Delete::class);
        $statementMock = Mockery::Mock(StatementInterface::class);
        $resultMock = Mockery::Mock(Result::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('delete')
            ->with(LogData::DELETION_LOG_TABLE)
            ->andReturn($deleteMock);

        $deleteMock->shouldReceive('where')
            ->once()
            ->with(['identity_hash' => 'abc123']);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($deleteMock)
            ->andReturn($statementMock);

        $statementMock->shouldReceive('execute')
            ->andReturn($resultMock);

        $resultMock->shouldReceive('getAffectedRows')
            ->andReturn(1);

        // test method
        $logData = new LogData($dbWrapperMock);
        $actualCount = $logData->deleteLogsByIdentityHash('abc123');

        // assertions
        $this->assertEquals(1, $actualCount);
    }
}
