<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Password;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class PasswordTest extends MockeryTestCase
{
    /**
     * @dataProvider dataProvider
     */
    static public function testIsValid($data, array $errors)
    {
        $validator = new Password();

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public function dataProvider()
    {
        return [
            [
                'data' => 'P@55word',
                'errors' => [],
            ],
            [
                'data' => '?',
                'errors' => [
                    'mustIncludeDigit'     => 'must-include-digit',
                    'mustIncludeLowerCase' => 'must-include-lower-case',
                    'mustIncludeUpperCase' => 'must-include-upper-case',
                ],
            ],
            [
                'data' => 'password',
                'errors' => [
                    'mustIncludeDigit'     => 'must-include-digit',
                    'mustIncludeUpperCase' => 'must-include-upper-case',
                ],
            ],
            [
                'data' => 'PASSWORD',
                'errors' => [
                    'mustIncludeDigit'     => 'must-include-digit',
                    'mustIncludeLowerCase' => 'must-include-lower-case',
                ],
            ],
            [
                'data' => '12345678',
                'errors' => [
                    'mustIncludeLowerCase' => 'must-include-lower-case',
                    'mustIncludeUpperCase' => 'must-include-upper-case',
                ],
            ],
            [
                'data' => 'Password',
                'errors' => [
                    'mustIncludeDigit'     => 'must-include-digit',
                ],
            ],
            [
                'data' => 'password1',
                'errors' => [
                    'mustIncludeUpperCase' => 'must-include-upper-case',
                ],
            ],
            [
                'data' => 'PASSWORD1',
                'errors' => [
                    'mustIncludeLowerCase' => 'must-include-lower-case',
                ],
            ],
        ];
    }
}
