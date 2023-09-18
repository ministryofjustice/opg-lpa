<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\EmailAddress;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class EmailAddressTest extends MockeryTestCase
{
    #[DataProvider('dataProvider')]
    static public function testIsValid($data, array $errors)
    {
        $validator = new EmailAddress();

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public function dataProvider()
    {
        return [
            [
                'data' => 'a@b.com',
                'errors' => [],
            ],
            [
                'data' => 'c@d..uk',
                'errors' => [
                    'invalidEmailAddress' => 'Enter a valid email address',
                ],
            ],
            [
                'data' => 'somethingverylongsomethingverylongsomethingvery' .
                    'longsomethingverylongsomethingverylong@somethingverylong.com',
                'errors' => [
                    'invalidEmailAddress' => 'Enter a valid email address',
                ],
            ],
            [
                'data' => 'invalid',
                'errors' => [
                    'invalidEmailAddress' => 'Enter a valid email address',
                ],
            ],
        ];
    }
}
