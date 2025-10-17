<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Correspondence;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CorrespondenceTest extends MockeryTestCase
{
    #[DataProvider('dataProvider')]
    public function testIsValid(array $data, array $errors): void
    {
        $validator = new Correspondence();

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public static function dataProvider(): array
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
