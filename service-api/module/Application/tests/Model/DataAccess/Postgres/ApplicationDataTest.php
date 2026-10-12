<?php

namespace ApplicationTest\Model\DataAccess\Postgres;

use ApplicationTest\Helpers;
use Application\Model\DataAccess\Postgres\ApplicationData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use Application\Model\DataAccess\Postgres\UserData;
use Application\Model\DataAccess\Repository\Application\ConflictException;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Sql\Predicate\In as InPredicate;
use Laminas\Db\Sql\Predicate\IsNull;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Predicate\PredicateSet;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Update;
use Laminas\Db\Sql\Sql;
use MakeShared\DataModel\Lpa\Lpa;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;

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

    #[DoesNotPerformAssertions]
    public function testUpdate(): void
    {
        $updatedAt = new \DateTime();
        $lpa = new Lpa([
            'id' => 123,
            'version' => 4,
            'document' => [],
            'updatedAt' => $updatedAt,
        ]);

        $dbWrapper = Mockery::mock(DbWrapper::class);

        // getForUpdateById(...)
        $selectStatement = Mockery::mock(StatementInterface::class);
        $selectSql = Mockery::mock(Sql::class);
        $select = Mockery::mock(Select::class);
        $selectResult = Mockery::mock(ResultInterface::class);

        $dbLpaData = [
            'id' => 123,
            'updatedBy' => '456',
            'version' => 4,
            'document' => null,
            'payment' => null,
            'metadata' => null,
        ];

        $selectSql->shouldReceive('select')->with(ApplicationData::APPLICATIONS_TABLE)->andReturn($select);
        $selectSql->shouldReceive('prepareStatementForSqlObject')->with($select)->andReturn($selectStatement);

        $select->shouldReceive('where')->with(['id' => 123])->andReturn($select);
        $select->shouldReceive('limit')->with(1)->andReturn($select);

        $selectStatement->shouldReceive('getSql')->andReturn('blah');
        $selectStatement->shouldReceive('setSql')->with('blah FOR UPDATE');
        $selectStatement->shouldReceive('execute')->andReturn($selectResult);

        $selectResult->shouldReceive('isQueryResult')->andReturn(true);
        $selectResult->shouldReceive('count')->andReturn(1);
        $selectResult->shouldReceive('current')->andReturn($dbLpaData);

        // update(...)
        $updateStatement = Mockery::mock(StatementInterface::class);
        $updateSql = Mockery::mock(Sql::class);
        $update = Mockery::mock(Update::class);
        $updateResult = Mockery::mock(ResultInterface::class);

        $updateSql->shouldReceive('update')->with(ApplicationData::APPLICATIONS_TABLE)->andReturn($update);
        $updateSql->shouldReceive('prepareStatementForSqlObject')->with($update)->andReturn($updateStatement);

        $update->shouldReceive('where')->with([
            'id' => 123,
            'updatedAt' => $updatedAt->format(DbWrapper::TIME_FORMAT),
            'version' => 4,
        ]);
        $update->shouldReceive('set');

        $updateStatement->shouldReceive('execute')->andReturn($updateResult);

        $updateResult->shouldReceive('getAffectedRows')->andReturn(1);

        $dbWrapper->shouldReceive('beginTransaction');
        $dbWrapper->shouldReceive('commit');
        $dbWrapper->shouldReceive('createSql')->andReturn($selectSql, $updateSql);

        $applicationData = new ApplicationData($dbWrapper, []);

        $applicationData->update($lpa);
    }

    public function testUpdateConflict(): void
    {
        $lpa = new Lpa(['id' => 123, 'version' => 4]);

        $dbWrapper = Mockery::mock(DbWrapper::class);

        // getForUpdateById(...)
        $lpaStatement = Mockery::mock(StatementInterface::class);
        $lpaSql = Mockery::mock(Sql::class);
        $lpaSelect = Mockery::mock(Select::class);
        $lpaResult = Mockery::mock(ResultInterface::class);

        $dbLpaData = ['id' => 123, 'updatedBy' => '456', 'version' => 5, 'document' => null, 'payment' => null, 'metadata' => null];

        $dbWrapper->shouldReceive('createSql')->andReturn($lpaSql);

        $lpaSql->shouldReceive('select')->with(ApplicationData::APPLICATIONS_TABLE)->andReturn($lpaSelect);
        $lpaSql->shouldReceive('prepareStatementForSqlObject')->with($lpaSelect)->andReturn($lpaStatement);

        $lpaSelect->shouldReceive('where')->with(['id' => 123])->andReturn($lpaSelect);
        $lpaSelect->shouldReceive('limit')->with(1)->andReturn($lpaSelect);

        $lpaStatement->shouldReceive('getSql')->andReturn('blah');
        $lpaStatement->shouldReceive('setSql')->with('blah FOR UPDATE');
        $lpaStatement->shouldReceive('execute')->andReturn($lpaResult);

        $lpaResult->shouldReceive('isQueryResult')->andReturn(true);
        $lpaResult->shouldReceive('count')->andReturn(1);
        $lpaResult->shouldReceive('current')->andReturn($dbLpaData);

        // update(...)
        $userResult = Mockery::mock(ResultInterface::class);

        $userData = ['profile' => '{"name": {"first": "a", "last": "b"}}'];

        $dbWrapper->shouldReceive('beginTransaction');
        $dbWrapper->shouldReceive('rollback');

        $dbWrapper->shouldReceive('select')
            ->with(UserData::USERS_TABLE, ['id' => 456], ['limit' => 1])
            ->andReturn($userResult);

        $userResult->shouldReceive('isQueryResult')->andReturn(true);
        $userResult->shouldReceive('count')->andReturn(1);
        $userResult->shouldReceive('current')->andReturn($userData);

        $applicationData = new ApplicationData($dbWrapper, []);

        $this->expectExceptionObject(new ConflictException('a b'));
        $applicationData->update($lpa);
    }

    public function testUpdateConflictUnknownUser(): void
    {
        $lpa = new Lpa(['id' => 123, 'version' => 4]);

        $dbWrapper = Mockery::mock(DbWrapper::class);

        // getForUpdateById(...)
        $lpaStatement = Mockery::mock(StatementInterface::class);
        $lpaSql = Mockery::mock(Sql::class);
        $lpaSelect = Mockery::mock(Select::class);
        $lpaResult = Mockery::mock(ResultInterface::class);

        $dbLpaData = ['id' => 123, 'updatedBy' => '456', 'version' => 5, 'document' => null, 'payment' => null, 'metadata' => null];

        $dbWrapper->shouldReceive('createSql')->andReturn($lpaSql);

        $lpaSql->shouldReceive('select')->with(ApplicationData::APPLICATIONS_TABLE)->andReturn($lpaSelect);
        $lpaSql->shouldReceive('prepareStatementForSqlObject')->with($lpaSelect)->andReturn($lpaStatement);

        $lpaSelect->shouldReceive('where')->with(['id' => 123])->andReturn($lpaSelect);
        $lpaSelect->shouldReceive('limit')->with(1)->andReturn($lpaSelect);

        $lpaStatement->shouldReceive('getSql')->andReturn('blah');
        $lpaStatement->shouldReceive('setSql')->with('blah FOR UPDATE');
        $lpaStatement->shouldReceive('execute')->andReturn($lpaResult);

        $lpaResult->shouldReceive('isQueryResult')->andReturn(true);
        $lpaResult->shouldReceive('count')->andReturn(1);
        $lpaResult->shouldReceive('current')->andReturn($dbLpaData);

        // update(...)
        $userResult = Mockery::mock(ResultInterface::class);

        $dbWrapper->shouldReceive('beginTransaction');
        $dbWrapper->shouldReceive('rollback');

        $dbWrapper->shouldReceive('select')
            ->with(UserData::USERS_TABLE, ['id' => 456], ['limit' => 1])
            ->andReturn($userResult);

        $userResult->shouldReceive('isQueryResult')->andReturn(true);
        $userResult->shouldReceive('count')->andReturn(0);

        $applicationData = new ApplicationData($dbWrapper, []);

        $this->expectExceptionObject(new ConflictException('Unknown user 456'));
        $applicationData->update($lpa);
    }
}
