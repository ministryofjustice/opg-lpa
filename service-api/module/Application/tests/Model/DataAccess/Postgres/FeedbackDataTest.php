<?php
namespace ApplicationTest\Model\DataAccess\Postgres;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;

use Application\Model\DataAccess\Postgres\FeedbackData;
use Application\Model\DataAccess\Postgres\DbWrapper;
use DateTime;
use Laminas\Db\Adapter\Driver\Pdo\Result;
use Laminas\Db\Sql\Predicate\Operator;
use Laminas\Db\Sql\Insert;
use Laminas\Db\Sql\Sql;

use ApplicationTest\Helpers;


class FeedbackDataTest extends MockeryTestCase
{
    public function testInsertSuccess() : void
    {
        $expected = TRUE;

        $data = [
            'email' => 'boff@bip',
            'details' => 'Great site',
        ];

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $sqlMock = Mockery::Mock(Sql::class);
        $insertMock = Mockery::Mock(Insert::class);

        // expectations
        $dbWrapperMock->shouldReceive('createSql')
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('insert')
            ->with(FeedbackData::FEEDBACK_TABLE)
            ->andReturn($insertMock);

        $insertMock->shouldReceive('columns')
            ->with(['received', 'message']);
        $insertMock->shouldReceive('values')
            ->withArgs(function ($valuesArg) use ($data) {
                return $valuesArg['message'] == json_encode($data) &&
                    Helpers::isGmDateString($valuesArg['received']);
            });

        $sqlMock->shouldReceive('prepareStatementForSqlObject')
            ->with($insertMock)
            ->andReturn($sqlMock);

        $sqlMock->shouldReceive('execute')
            ->andReturn(TRUE);

        // test method
        $feedbackData = new FeedbackData($dbWrapperMock);
        $actual = $feedbackData->insert($data);

        // assertions
        $this->assertEquals($expected, $actual);
    }

    public function testGetForDateRange() : void
    {
        $expected = [
            'received' => '2021-08-01T01:02:00+00:00',
            'email' => 'mrfoo@somewhere',
            'details' => 'I love this',
            'rating' => 'very-satisfied',
        ];

        $from = new DateTime('2021-07-04');
        $to = new DateTime('2021-07-15');

        $criteria = [
            new Operator('received', Operator::OPERATOR_GREATER_THAN_OR_EQUAL_TO, '2021-07-04T00:00:00+00:00'),
            new Operator('received', Operator::OPERATOR_LESS_THAN_OR_EQUAL_TO, '2021-07-15T00:00:00+00:00'),
        ];

        $options = [
            'sort' => ['received' => 1]
        ];

        // mocks
        $dbWrapperMock = Mockery::Mock(DbWrapper::class);
        $resultMock = Helpers::makePdoResultMock([[
            'message' => '{"email":"mrfoo@somewhere", "details":"I love this", "rating":"very-satisfied"}',
            'received' => '2021-08-01T01:02:00.123',
        ]]);

        // expectations
        $dbWrapperMock->shouldReceive('select')
            ->with(
                FeedbackData::FEEDBACK_TABLE,
                $criteria,
                $options
            )
            ->andReturn($resultMock);

        // test method
        $feedbackData = new FeedbackData($dbWrapperMock);
        $feedback = iterator_to_array($feedbackData->getForDateRange($from, $to));

        // assertions
        $this->assertEquals($expected, $feedback[0]);
    }
}