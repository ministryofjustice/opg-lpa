<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Sql\Predicate\In as InPredicate;
use ApplicationTest\Helpers;

class ApplicationDataTest extends MockeryTestCase
{
    public function testCount(): void
    {
        $expectedCount = 10;
        $criteria = [
            'search' => "o'connor",
            'user' => 1,
        ];

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $resultMock = Mockery::Mock(Result::class);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                ApplicationData::APPLICATIONS_TABLE,
                $criteria,
                Mockery::on(function ($options) {
                    $countExpression = $options['columns']['count'];
                    return ($countExpression->getExpression() === 'count(*)');
                })
            )
            ->andReturn($resultMock);

        $resultMock->shouldReceive('isQueryResult')
            ->andReturn(true);
        $resultMock->shouldReceive('count')
            ->andReturn(1);
        $resultMock->shouldReceive('current')
            ->andReturn(['count' => $expectedCount]);

        // test method
        $applicationData = new ApplicationData($dbWrapperMock, []);
        $count = $applicationData->count($criteria);

        // assertions
        $this->assertEquals($expectedCount, $count);
    }

    public function testGetByIdsAndUser(): void
    {
        $userId = '2';
        $lpaIds = ['90', '91', '92'];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $resultMock = Helpers::makePdoResultMock([[
            'document' => '{"a":1}',
            'metadata' => '{"b":2}',
            'payment' => null,
            'sharedSpaceId' => null,
        ]]);

        $applicationData = new ApplicationData($dbWrapperMock, []);

        $expectedOwnerPredicate = $applicationData->ownerPredicate($userId, null);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                ApplicationData::APPLICATIONS_TABLE,
                Mockery::on(function ($criteriaArg) use ($lpaIds, $expectedOwnerPredicate) {
                    return $criteriaArg[0] == new InPredicate('id', $lpaIds) &&
                        $criteriaArg[1] == $expectedOwnerPredicate;
                }),
                [],
            )
            ->andReturn($resultMock);

        // important to call iterator_to_array() to ensure that all the
        // items yielded are gathered
        $lpas = iterator_to_array($applicationData->getByIdsAndUser($lpaIds, $userId));

        // assertions
        $this->assertEquals(1, count($lpas));
        $this->assertEquals([
            'document' => ["a" => 1],
            'metadata' => ["b" => 2],
            'payment' => null,
            'sharedSpaceId' => null,
        ], $lpas[0]);
    }
}
