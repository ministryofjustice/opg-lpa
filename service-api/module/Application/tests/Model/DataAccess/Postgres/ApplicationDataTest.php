<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Laminas\Db\ResultSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;


class ApplicationDataTest extends MockeryTestCase
{
    public function testCount() : void
    {
        $expectedCount = 10;

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $selectMock = Mockery::Mock(Select::class);
        $resultSetMock = Mockery::Mock(ResultSet::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);
        $sqlMock->shouldReceive('select')
            ->andReturn($selectMock);
        $selectMock->shouldReceive('columns')
            ->andReturn($selectMock);
        $dbWrapperMock->shouldReceive('quoteValue')
            ->with("o'connor")
            ->andReturn("'o''connor'");

        // confirm the expression passed to where() has the escaped string
        $selectMock->shouldReceive('where')
            ->with(Mockery::on(function ($args) {
                return $args[0]->getExpression() === "search ~* 'o''connor'";
            }))
            ->once();

        // confirm additional criteria are appended to where
        $selectMock->shouldReceive('where')
            ->with(['user' => 1]);

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($selectMock)
            ->andReturn($sqlMock);
        $sqlMock->shouldReceive('execute')
            ->andReturn($resultSetMock);
        $resultSetMock->shouldReceive('isQueryResult')
            ->andReturn(True);
        $resultSetMock->shouldReceive('count')
            ->andReturn(1);
        $resultSetMock->shouldReceive('current')
            ->andReturn(['count' => $expectedCount]);

        // test method
        $applicationData = new ApplicationData($dbWrapperMock, []);
        $count = $applicationData->count([
            'search' => "o'connor",
            'user' => 1,
        ]);

        // assertions
        $this->assertEquals($expectedCount, $count);
    }
}