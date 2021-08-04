<?php
namespace ApplicationTest;

use Mockery;

use Laminas\Db\Adapter\Driver\Pdo\Result;


class Helpers
{
    /**
     * Create a mock for a PDO Result object which responds correctly
     * to foreach calls. This manages expectations for internal
     * methods called when a Result is iterated so that test code
     * can concentrate on specifying the expected records to be returned.
     *
     * @param array $records An array of records to be returned by the mock.
     * The first element in the array is returned first, then the second etc.
     * Each record is an associate array.
     * @return Result Mock PDO Result object
     */
    public static function makePdoResultMock($records)
    {
        $resultMock = Mockery::Mock(Result::class);

        $numRecords = count($records);

        // called when the traversal starts
        $resultMock->shouldReceive('rewind')->once();

        // valid() is called once each time round the loop;
        // we return TRUE for this method call until we run out of records,
        // then return FALSE to stop traversal
        $returnValuesForValid = array_pad([], $numRecords, TRUE);
        $returnValuesForValid[] = FALSE;
        $resultMock->shouldReceive('valid')
            ->times(count($returnValuesForValid))
            ->andReturnValues($returnValuesForValid);

        $resultMock->shouldReceive('current')
            ->times($numRecords)
            ->andReturnValues($records);

        // 'next' will be called a number of times equal to the number of records
        $resultMock->shouldReceive('next')->times($numRecords);

        return $resultMock;
    }
}