<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Authentication\AuthenticationService;
use App\Form\User\ChangePassword;
use Laminas\Authentication\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class ChangePasswordTest extends TestCase
{
    private AuthenticationService&MockObject $authService;
    private ChangePassword $form;

    protected function setUp(): void
    {
        $this->authService = $this->createMock(AuthenticationService::class);

        // Construct without calling init() — we only test the App\-specific logic
        $this->form = new ChangePassword();
        $this->form->setAuthenticationService($this->authService);
    }

    public function testSetNameIsChangePassword(): void
    {
        // init() sets the name
        $this->form->init();
        $this->assertSame('change-password', $this->form->getName());
    }

    public function testValidatePasswordReturnsTrueWhenCredentialsValid(): void
    {
        $this->authService->expects($this->once())
            ->method('setPassword')
            ->with('correct-password');

        $this->authService->expects($this->once())
            ->method('verify')
            ->willReturn(true);

        $this->assertTrue($this->form->validatePassword('correct-password'));
    }

    public function testValidatePasswordReturnsFalseWhenCredentialsInvalid(): void
    {
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(false);

        $this->assertFalse($this->form->validatePassword('wrong-password'));
    }

    public function testValidatePasswordThrowsWhenAuthServiceNotSet(): void
    {
        $form = new ChangePassword();
        // No setAuthenticationService() call

        $this->expectException(InvalidArgumentException::class);

        $form->validatePassword('any-password');
    }

    public function testSetAuthenticationServiceAcceptsAppAuthService(): void
    {
        // Already set in setUp; verify we can call validatePassword without error
        $this->authService->method('setPassword');
        $this->authService->method('verify')->willReturn(true);

        $this->assertTrue($this->form->validatePassword('password'));
    }
}
