<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\AbstractForm;
use App\Form\User\Registration;
use App\Form\User\SetPassword;
use Laminas\Form\Element\Checkbox;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Password;
use PHPUnit\Framework\TestCase;

final class RegistrationTest extends TestCase
{
    private Registration $form;

    protected function setUp(): void
    {
        $this->form = new Registration();
        $this->form->init();
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf(Registration::class, $this->form);
        $this->assertInstanceOf(SetPassword::class, $this->form);
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertEquals('registration', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf(Email::class, $this->form->get('email'));
        $this->assertInstanceOf(Email::class, $this->form->get('email_confirm'));
        $this->assertInstanceOf(Checkbox::class, $this->form->get('terms'));
        // From SetPassword
        $this->assertInstanceOf(Password::class, $this->form->get('password'));
        $this->assertInstanceOf(Password::class, $this->form->get('password_confirm'));
        $this->assertInstanceOf(Hidden::class, $this->form->get('skip_confirm_password'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'a@b.com',
            'terms'                 => '1',
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidDataWithHTMLTagsInPasswordIsValid(): void
    {
        $this->form->setData([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'a@b.com',
            'terms'                 => '1',
            'password'              => '<>P@55wordword', // pragma: allowlist secret
            'password_confirm'      => '<>P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidDataWithLeadingAndTrailingSpacesInPasswordIsValid(): void
    {
        $this->form->setData([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'a@b.com',
            'terms'                 => '1',
            'password'              => '  P@55word  ', // pragma: allowlist secret
            'password_confirm'      => '  P@55word  ', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testSkipConfirmPasswordBypassesConfirmValidation(): void
    {
        $this->form->setData([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'a@b.com',
            'terms'                 => '1',
            'password'              => '<>P@55wordword', // pragma: allowlist secret
            'password_confirm'      => '',
            'skip_confirm_password' => '1',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testAllEmptyFieldsAreInvalid(): void
    {
        $this->form->setData([
            'email'                 => '',
            'email_confirm'         => '',
            'terms'                 => '',
            'password'              => '',
            'password_confirm'      => '',
            'skip_confirm_password' => '',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertEquals('cannot-be-empty', $messages['email']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['email_confirm']['isEmpty']);
        $this->assertEquals('must-be-checked', $messages['terms'][0]);
        $this->assertEquals('cannot-be-empty', $messages['password']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['password_confirm']['isEmpty']);
    }

    public function testTermsNotCheckedIsInvalid(): void
    {
        $this->form->setData([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'a@b.com',
            'terms'                 => '0',
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('terms', $this->form->getMessages());
    }

    public function testMismatchedEmailsAreInvalid(): void
    {
        $this->form->setData([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'different@b.com',
            'terms'                 => '1',
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('email_confirm', $this->form->getMessages());
    }
}
