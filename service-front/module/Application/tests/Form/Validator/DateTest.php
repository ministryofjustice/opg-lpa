<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Date;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DateTest extends MockeryTestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testIsValid($data, array $errors)
    {
        $validator = new Date();

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public function dataProvider()
    {
        return [
            [
                'data' => [
                    'day'   => '2',
                    'month' => '7',
                    'year'  => '1980',
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'day'   => 3,
                    'month' => 3,
                    'year'  => 2004,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'day'   => '02',
                    'month' => '07',
                    'year'  => '1980',
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'day'   => '02',
                    'month' => '07',
                    'year'  => '1980',
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'day'   => 3,
                    'month' => '4',
                    'year'  => '1979',
                ],
                'errors' => [],
            ],
            [
                'data' => null,
                'errors' => [
                    'dateInvalid' => 'Invalid type given. String, integer, array or DateTime expected',
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => '32',
                    'month' => '2',
                    'year'  => '1988',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => '1',
                    'month' => '15',
                    'year'  => '1999',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => '1',
                    'month' => '2',
                    'year'  => '2',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => 'cd',
                    'month' => '2',
                    'year'  => '1965',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => '12',
                    'month' => '12',
                    'year'  => '999',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => '34',
                    'month' => '1',
                    'year'  => '2001',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => 3,
                    'month' => 3,
                    'year'  => 2004,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'day'   => true,
                    'month' => null,
                    'year'  => 1999,
                ],
                'errors' => [
                    'emptyDate' => 'Enter all the date fields',
                ],
            ],
            [
                'data' => [
                    'day'   => '32cd',
                    'month' => '2',
                    'year'  => '1965',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'data' => [
                    'day'   => '1cd',
                    'month' => '2',
                    'year'  => '1965',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
        ];
    }
}
