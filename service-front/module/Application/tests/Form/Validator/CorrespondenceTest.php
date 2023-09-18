<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Correspondence;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class CorrespondenceTest extends MockeryTestCase
{
    #[DataProvider('dataProvider')]
    public function testIsValid($data, array $errors)
    {
        $validator = new Correspondence();

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    static public function dataProvider()
    {
        return [
            [
                'data' => [
                    'contactByPost'  => true,
                    'contactByPhone' => false,
                    'contactByEmail' => false,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'contactByPost'  => true,
                    'contactByPhone' => true,
                    'contactByEmail' => false,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'contactByPost'  => true,
                    'contactByPhone' => true,
                    'contactByEmail' => true,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'contactByPost'  => false,
                    'contactByPhone' => true,
                    'contactByEmail' => false,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'contactByPost'  => false,
                    'contactByPhone' => false,
                    'contactByEmail' => true,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'contactByPost'  => false,
                    'contactByPhone' => true,
                    'contactByEmail' => true,
                ],
                'errors' => [],
            ],
            [
                'data' => [
                    'contactByPost'  => false,
                    'contactByPhone' => false,
                    'contactByEmail' => false,
                ],
                'errors' => [
                    'at-least-one-option-needs-to-be-selected' => 'at-least-one-option-needs-to-be-selected',
                ],
            ],
        ];
    }
}
