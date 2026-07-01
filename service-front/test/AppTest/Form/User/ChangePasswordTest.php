<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Authentication\AuthenticationService;
use App\Form\AbstractForm;
use App\Form\User\ChangePassword;
use App\Form\User\SetPassword;
use Laminas\Authentication\Exception\InvalidArgumentException;
use Laminas\Form\Element\Hidden;
use Laminas\Form\Element\Password;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangePasswordTest extends TestCase
{
    private AuthenticationService&MockObject $authService;
    private ChangePassword $form;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthenticationService::class);

        $this->form = new ChangePassword();
        $this->form->setAuthenticationService($this->authService);
        $this->form->init();
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf(ChangePassword::class, $this->form);
        $this->assertInstanceOf(SetPassword::class, $this->form);
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertEquals('change-password', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf(Password::class, $this->form->get('password_current'));
        // From SetPassword
        $this->assertInstanceOf(Password::class, $this->form->get('password'));
        $this->assertInstanceOf(Password::class, $this->form->get('password_confirm'));
        $this->assertInstanceOf(Hidden::class, $this->form->get('skip_confirm_password'));
    }

    public function testValidDataIsValid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(true);

        $this->form->setData([
            'password_current'      => 'P@55wordword', // pragma: allowlist secret
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidDataWithHTMLTagsIsValid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(true);

        $this->form->setData([
            'password_current'      => 'P@55wordword', // pragma: allowlist secret
            'password'              => '<>P@55wordword', // pragma: allowlist secret
            'password_confirm'      => '<>P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidDataWithLeadingAndTrailingSpacesIsValid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(true);

        $this->form->setData([
            'password_current'      => 'P@55word', // pragma: allowlist secret
            'password'              => '  P@55word  ', // pragma: allowlist secret
            'password_confirm'      => '  P@55word  ', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testEmptyFieldsAreInvalid(): void
    {
        $this->form->setData([
            'password_current'      => '',
            'password'              => '',
            'password_confirm'      => '',
            'skip_confirm_password' => '',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertEquals('cannot-be-empty', $messages['password_current']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['password']['isEmpty']);
        $this->assertEquals('cannot-be-empty', $messages['password_confirm']['isEmpty']);
    }

    public function testIncorrectCurrentPasswordIsInvalid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(false);

        $this->form->setData([
            'password_current'      => 'wrongpassword', // pragma: allowlist secret
            'password'              => 'P@55wordword', // pragma: allowlist secret
            'password_confirm'      => 'P@55wordword', // pragma: allowlist secret
            'skip_confirm_password' => '0',
        ]);

        $this->assertFalse($this->form->isValid());
        $this->assertArrayHasKey('password_current', $this->form->getMessages());
    }

    public function testValidatePasswordThrowsWhenAuthServiceNotSet(): void
    {
        $form = new ChangePassword();
        $form->init();

        $this->expectException(InvalidArgumentException::class);
        $form->validatePassword('any-password');
    }

    public function testValidatePasswordReturnsTrueWhenCredentialsValid(): void
    {
        $this->authService->expects($this->once())->method('setPassword')->with('correct-password');
        $this->authService->expects($this->once())->method('verify')->willReturn(true);

        $this->assertTrue($this->form->validatePassword('correct-password'));
    }

    public function testValidatePasswordReturnsFalseWhenCredentialsInvalid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(false);

        $this->assertFalse($this->form->validatePassword('wrong-password'));
    }
}
