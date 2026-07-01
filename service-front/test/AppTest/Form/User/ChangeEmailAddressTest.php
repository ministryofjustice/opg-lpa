<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Authentication\AuthenticationService;
use App\Form\AbstractForm;
use App\Form\User\ChangeEmailAddress;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangeEmailAddressTest extends TestCase
{
    private AuthenticationService&MockObject $authService;
    private ChangeEmailAddress $form;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthenticationService::class);

        $this->form = new ChangeEmailAddress();
        $this->form->setAuthenticationService($this->authService);
        $this->form->init();
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf(ChangeEmailAddress::class, $this->form);
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertEquals('change-email-address', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf(Password::class, $this->form->get('password_current'));
        $this->assertInstanceOf(Email::class, $this->form->get('email'));
        $this->assertInstanceOf(Email::class, $this->form->get('email_confirm'));
    }

    public function testValidDataIsValid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(true);

        $this->form->setData([
            'password_current' => 'thecurrentpassword', // pragma: allowlist secret
            'email'            => 'a@b.com',
            'email_confirm'    => 'a@b.com',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testEmptyFieldsAreInvalid(): void
    {
        $this->form->setData([
            'password_current' => '',
            'email'            => '',
            'email_confirm'    => '',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertEquals('cannot-be-empty', $messages['password_current']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['email']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['email_confirm']['isEmpty']);
    }

    public function testMismatchedEmailsAreInvalid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(true);

        $this->form->setData([
            'password_current' => 'thecurrentpassword', // pragma: allowlist secret
            'email'            => 'a@b.com',
            'email_confirm'    => 'different@b.com',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('email_confirm', $this->form->getMessages());
    }

    public function testIncorrectPasswordIsInvalid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(false);

        $this->form->setData([
            'password_current' => 'wrongpassword', // pragma: allowlist secret
            'email'            => 'a@b.com',
            'email_confirm'    => 'a@b.com',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('password_current', $this->form->getMessages());
    }

    public function testValidatePasswordThrowsWhenAuthServiceNotSet(): void
    {
        $form = new ChangeEmailAddress();
        $form->init();

        $this->expectException(InvalidArgumentException::class);
        $form->validatePassword('any-password');
    }
}
