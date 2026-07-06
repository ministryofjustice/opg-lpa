<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\AbstractForm;
use App\Form\User\ConfirmEmail;
use Laminas\Form\Element\Email;
use PHPUnit\Framework\TestCase;

final class ConfirmEmailTest extends TestCase
{
    private ConfirmEmail $form;

    protected function setUp(): void
    {
        $this->form = new ConfirmEmail();
        $this->form->init();
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf(ConfirmEmail::class, $this->form);
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertEquals('confirm-email', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf(Email::class, $this->form->get('email'));
        $this->assertInstanceOf(Email::class, $this->form->get('email_confirm'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData([
            'email'         => 'a@b.com',
            'email_confirm' => 'a@b.com',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testEmptyEmailIsInvalid(): void
    {
        $this->form->setData([
            'email'         => '',
            'email_confirm' => '',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertArrayHasKey('email', $messages);
        $this->assertArrayHasKey('email_confirm', $messages);
        $this->assertEquals('cannot-be-empty', $messages['email']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['email_confirm']['isEmpty']);
    }

    public function testMismatchedEmailsAreInvalid(): void
    {
        $this->form->setData([
            'email'         => 'a@b.com',
            'email_confirm' => 'different@b.com',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertArrayHasKey('email_confirm', $messages);
    }

    public function testInvalidEmailFormatIsRejected(): void
    {
        $this->form->setData([
            'email'         => 'not-an-email',
            'email_confirm' => 'not-an-email',
        ]);

        $this->assertFalse($this->form->isValid());
    }
}
