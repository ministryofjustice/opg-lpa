<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Csrf;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Laminas\Session\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\LoggerInterface;

final class CsrfTest extends MockeryTestCase
{
    #[DataProvider('dataProvider')]
    public function testIsValid(string $data, array $errors): void
    {
        $validator = new Csrf();
        $validator->setLogger(Mockery::spy(LoggerInterface::class));

        //  Set the session seed to skip the randomisation step, required to normalise the session
        // container specified due to how session info is managed
        $session = new Container('CsrfValidator');
        $session->token = 12345;

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public static function dataProvider(): array
    {
        return [
            [
                'data'   => '9a5e8ee1223945896d276a9279390cb619a18c208f828b0f5e4923a0769fa3f396cb699c9b1764a17e8d49980bceb90257747efdc9e64d6329bc43143a992037',
                'errors' => [],
            ],
            [
                'data' => 'invalidvalue',
                'errors' => [
                    'notSame' => 'Oops! Something went wrong with the information you entered. Please try again.',
                ],
            ],
        ];
    }
}
