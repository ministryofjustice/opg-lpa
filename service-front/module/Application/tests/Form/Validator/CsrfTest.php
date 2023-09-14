<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Validator\Csrf;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Laminas\Session\Container;

class CsrfTest extends MockeryTestCase
{
    /**
     * @dataProvider dataProvider
     */
    static public function testIsValid($data, array $errors)
    {
        $validator = new Csrf();

        //  Set the session seed to skip the randomisation step, required to normalise the session
        // container specified due to how session info is managed
        $session = new Container('CsrfValidator');
        $session->token = 12345;

        $result = $validator->isValid($data);

        $this->assertSame(empty($errors), $result);
        $this->assertEquals($errors, $validator->getMessages());
    }

    public function dataProvider()
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
