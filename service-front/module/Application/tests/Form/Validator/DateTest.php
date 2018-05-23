<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Date;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\TestCase;

class DateTest extends MockeryTestCase
{
    /**
     * @dataProvider getTestDates
     */
    public function testIsValid($date, array $errors)
    {
        $validator = new Date();

        $result = $validator->isValid($date);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public function getTestDates()
    {
        return [
            [
                'date' => [
                    'day'   => '2',
                    'month' => '7',
                    'year'  => '1980',
                ],
                'errors' => [],
            ],
            [
                'date' => [
                    'day'   => 3,
                    'month' => 3,
                    'year'  => 2004,
                ],
                'errors' => [],
            ],
            [
                'date' => [
                    'day'   => '02',
                    'month' => '07',
                    'year'  => '1980',
                ],
                'errors' => [],
            ],
            [
                'date' => [
                    'day'   => '02',
                    'month' => '07',
                    'year'  => '1980',
                ],
                'errors' => [],
            ],
            [
                'date' => [
                    'day'   => 3,
                    'month' => '4',
                    'year'  => '1979',
                ],
                'errors' => [],
            ],
            [
                'date' => null,
                'errors' => [
                    'dateInvalid' => 'Invalid type given. String, integer, array or DateTime expected',
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => '32',
                    'month' => '2',
                    'year'  => '1988',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => '1',
                    'month' => '15',
                    'year'  => '1999',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => '1',
                    'month' => '2',
                    'year'  => '2',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => 'cd',
                    'month' => '2',
                    'year'  => '1965',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => '12',
                    'month' => '12',
                    'year'  => '999',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => '34',
                    'month' => '1',
                    'year'  => '2001',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
                    'day'   => 3,
                    'month' => 3,
                    'year'  => 2004,
                ],
                'errors' => [],
            ],
            [
                'date' => [
                    'day'   => true,
                    'month' => null,
                    'year'  => 1999,
                ],
                'errors' => [
                    'emptyDate' => 'Please enter all the date fields',
                ],
            ],
            [
                'date' => [
                    'day'   => '32cd',
                    'month' => '2',
                    'year'  => '1965',
                ],
                'errors' => [
                    'dateInvalidDate' => 'The input does not appear to be a valid date',
                ],
            ],
            [
                'date' => [
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
