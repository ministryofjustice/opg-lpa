<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\EmailAddress;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class EmailAddressTest extends MockeryTestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testIsValid($data, array $errors)
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
                    0 => 'Enter a valid email address',
                ],
            ],
            [
                'data' => 'somethingverylongsomethingverylongsomethingverylongsomethingverylongsomethingverylong@somethingverylong.com',
                'errors' => [
                    0 => 'Enter a valid email address',
                ],
            ],
            [
                'data' => 'invalid',
                'errors' => [
                    0 => 'Enter a valid email address',
                ],
            ],
        ];
    }
}
