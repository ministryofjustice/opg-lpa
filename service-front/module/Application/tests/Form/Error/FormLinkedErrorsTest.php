<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Error;

use Application\Form\Error\FormLinkedErrors;
use Laminas\Form\Form;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class FormLinkedErrorsTest extends MockeryTestCase
{
    public function testFlattensFormMessagesIntoFieldMessagePairs(): void
    {
        $form = $this->createMock(Form::class);

        $form->method('getMessages')->willReturn([
            'email' => [
                'Email address is required',
            ],
            'password' => [
                ['Password must be at least 8 characters'],
            ],
            'postcode' => [
                'Invalid postcode',
                '',
                null,
            ],
        ]);

        $service = new FormLinkedErrors();

        $result = $service->fromForm($form);

        $this->assertSame([
            ['field' => 'email',    'message' => 'Email address is required'],
            ['field' => 'password', 'message' => 'Password must be at least 8 characters'],
            ['field' => 'postcode', 'message' => 'Invalid postcode'],
        ], $result);
    }

    public function testReturnsEmptyArrayWhenFormHasNoMessages(): void
    {
        $form = $this->createMock(Form::class);
        $form->method('getMessages')->willReturn([]);

        $service = new FormLinkedErrors();

        $this->assertSame([], $service->fromForm($form));
    }
}
