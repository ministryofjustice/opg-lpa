<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;

class DbWrapperTest extends MockeryTestCase
{
    public function testSelect(): void
    {
        $tableName = 'foo';

        $criteria = [
            'search' => "o'connor",
            'user' => '2',
        ];

        $options = [
            'skip' => 10,
            'limit' => 5,
            'columns' => ['name', 'age'],
            'sort' => ['age' => 1],
        ];

        // mocks
        $adapterMock = Mockery::Mock(Adapter::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $selectMock = Mockery::Mock(Select::class);
        $statementMock = Mockery::Mock(StatementInterface::class);
        $resultMock = Mockery::Mock(Result::class);

        // expectations
        $sqlMock->shouldReceive('select')
            ->with($tableName)
            ->once()
            ->andReturn($selectMock);

        // confirm additional criteria are appended to where
        $selectMock->shouldReceive('where')
            ->with(['user' => '2', "search ~* 'o''connor'"])
            ->once();

        // additional LIMIT, OFFSET, SORT and COLUMNS settings
        $selectMock->shouldReceive('offset')
            ->with(10)
            ->once();
        $selectMock->shouldReceive('limit')
            ->with(5)
            ->once();
        $selectMock->shouldReceive('order')
            ->with('age ASC')
            ->once();
        $selectMock->shouldReceive('columns')
            ->with(['name', 'age'])
            ->once();

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($selectMock)
            ->once()
            ->andReturn($statementMock);
        $statementMock->shouldReceive('execute')
            ->once()
            ->andReturn($resultMock);

        // patch the createSql() and quoteValue() methods of class under test;
        // $dbWrapper is a partial mock which defers all methods except the patched ones
        // to real methods; the array passed here contains constructor arguments
        $dbWrapper = Mockery::Mock(DbWrapper::class, array($adapterMock))->makePartial();

        $dbWrapper->shouldReceive('createSql')
            ->once()
            ->andReturn($sqlMock);

        $dbWrapper->shouldReceive('quoteValue')
            ->with("o'connor")
            ->once()
            ->andReturn("'o''connor'");

        $dbWrapper->select($tableName, $criteria, $options);
    }
}
