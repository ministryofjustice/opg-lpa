<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Sql\Predicate\In as InPredicate;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\PredicateSet;
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

    public function getByIdsAndUserOrSharedSpaceWithUser(): void
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

        $expectedOwnerPredicate = $applicationData->ownerPredicate($userId);

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
        $lpas = iterator_to_array($applicationData->getByIdsAndUserOrSharedSpace($lpaIds, $userId));

        // assertions
        $this->assertEquals(1, count($lpas));
        $this->assertEquals([
            'document' => ["a" => 1],
            'metadata' => ["b" => 2],
            'payment' => null,
            'sharedSpaceId' => null,
        ], $lpas[0]);
    }

    public function getByIdsAndUserOrSharedSpaceWithSharedSpaceAndUser(): void
    {
        $userId = '10';
        $sharedSpaceId = '1';
        $lpaIds = ['90', '91', '92'];

        // mocks
        $dbWrapperMock = Mockery::mock(DbWrapper::class);
        $resultMock = Helpers::makePdoResultMock([[
            'document' => '{"a":1}',
            'metadata' => '{"b":2}',
            'payment' => null,
            'sharedSpaceId' => $sharedSpaceId,
        ]]);

        $applicationData = new ApplicationData($dbWrapperMock, []);

        $expectedOwnerPredicate = $applicationData->sharedSpacePredicate($sharedSpaceId);

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
        $lpas = iterator_to_array(
            $applicationData->getByIdsAndUserOrSharedSpace($lpaIds, $userId, $sharedSpaceId)
        );

        // assertions
        $this->assertEquals(1, count($lpas));
        $this->assertEquals([
            'document' => ["a" => 1],
            'metadata' => ["b" => 2],
            'payment' => null,
            'sharedSpaceId' => $sharedSpaceId,
        ], $lpas[0]);
    }

    public function testSharedSpacePredicate(): void
    {
        $applicationData = new ApplicationData(Mockery::mock(DbWrapper::class), []);

        $predicate = $applicationData->sharedSpacePredicate('space-1');

        // A user in a shared space should only see LPAs owned by that
        // shared space - their own individually-owned LPAs (if any) must
        // NOT be included.
        $this->assertEquals(
            new Operator('sharedSpaceId', Operator::OPERATOR_EQUAL_TO, 'space-1'),
            $predicate
        );
    }

    public function testOwnerPredicateForIndividualOwner(): void
    {
        $applicationData = new ApplicationData(Mockery::mock(DbWrapper::class), []);

        $predicate = $applicationData->ownerPredicate('user-1');

        // A user not in a shared space should only see LPAs they own
        // directly and which haven't been moved into a shared space.
        $this->assertEquals(
            new PredicateSet([
                new Operator('user', Operator::OPERATOR_EQUAL_TO, 'user-1'),
                new IsNull('sharedSpaceId'),
            ], PredicateSet::COMBINED_BY_AND),
            $predicate
        );
    }
}
