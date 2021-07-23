<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\DbWrapper;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;


class DbWrapperTest extends MockeryTestCase
{
    public function testSelect() : void
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
        $resultMock = Mockery::Mock(Result::class);

        // expectations
        $sqlMock->shouldReceive('select')
            ->with($tableName)
            ->andReturn($selectMock);

        // confirm the expression passed to where() has the escaped string
        $selectMock->shouldReceive('where')
            ->with(Mockery::on(function ($args) {
                return $args[0]->getExpression() === "search ~* 'o''connor'";
            }))
            ->once();

        // confirm additional criteria are appended to where
        $selectMock->shouldReceive('where')
            ->with(['user' => '2']);

        // additional LIMIT, OFFSET, SORT and COLUMNS settings
        $selectMock->shouldReceive('offset')
            ->with(10);
        $selectMock->shouldReceive('limit')
            ->with(5);
        $selectMock->shouldReceive('order')
            ->with('age ASC');
        $selectMock->shouldReceive('columns')
            ->with(['name', 'age']);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($selectMock)
            ->andReturn($sqlMock);
        $sqlMock->shouldReceive('execute')
            ->andReturn($resultMock);

        // patch the createSql() and quoteValue() methods of class under test;
        // $dbWrapper is a partial mock which defers all methods except the patched ones
        // to real methods; the array passed here contains constructor arguments
        $dbWrapper = Mockery::Mock(DbWrapper::class, array($adapterMock))->makePartial();
        $dbWrapper->shouldReceive('createSql')
            ->andReturn($sqlMock);
        $dbWrapper->shouldReceive('quoteValue')
            ->with("o'connor")
            ->andReturn("'o''connor'");

        $count = $dbWrapper->select($tableName, $criteria, $options);
    }
}