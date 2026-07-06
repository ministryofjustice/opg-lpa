<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\AbstractForm;
use App\Form\User\SetPassword;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Password;
use PHPUnit\Framework\TestCase;

final class SetPasswordTest extends TestCase
{
    private SetPassword $form;

    protected function setUp(): void
    {
        $this->form = new SetPassword();
        $this->form->init();
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf(SetPassword::class, $this->form);
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertEquals('set-password', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf(Password::class, $this->form->get('password'));
        $this->assertInstanceOf(Password::class, $this->form->get('password_confirm'));
        $this->assertInstanceOf(Hidden::class, $this->form->get('skip_confirm_password'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData([
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testEmptyPasswordsAreInvalid(): void
    {
        $this->form->setData([
            'password'              => '',
            'password_confirm'      => '',
            'skip_confirm_password' => '',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertEquals('cannot-be-empty', $messages['password']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['password_confirm']['isEmpty']);
    }

    public function testPasswordTooShortIsInvalid(): void
    {
        $this->form->setData([
            'password'              => 'Short1A', // pragma: allowlist secret
            'password_confirm'      => 'Short1A', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('password', $this->form->getMessages());
    }

    public function testMismatchedPasswordsAreInvalid(): void
    {
        $this->form->setData([
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'Different1A', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
    }

    public function testSkipConfirmPasswordBypasesConfirmValidation(): void
    {
        $this->form->setData([
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => '',
            'skip_confirm_password' => '1',
        ]);

        $this->assertTrue($this->form->isValid());
    }

    public function testPasswordMissingDigitIsInvalid(): void
    {
        $this->form->setData([
            'password'              => 'NoDigitsHere!A', // pragma: allowlist secret
            'password_confirm'      => 'NoDigitsHere!A', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
    }

    public function testPasswordMissingUpperCaseIsInvalid(): void
    {
        $this->form->setData([
            'password'              => 'p@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'p@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
    }

    public function testPasswordMissingLowerCaseIsInvalid(): void
    {
        $this->form->setData([
            'password'              => 'P@55WORDWORD', // pragma: allowlist secret
            'password_confirm'      => 'P@55WORDWORD', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
    }

    public function testPasswordWithHtmlTagsIsValid(): void
    {
        $this->form->setData([
            'password'              => '<>P@55wordword', // pragma: allowlist secret
            'password_confirm'      => '<>P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
    }
}
