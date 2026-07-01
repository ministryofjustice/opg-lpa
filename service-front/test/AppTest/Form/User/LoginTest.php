<?php

declare(strict_types=1);

namespace AppTest\Form\User;

use App\Form\AbstractForm;
use App\Form\User\Login;
use Laminas\Form\Element\Email;
use Laminas\Form\Element\Password;
use PHPUnit\Framework\TestCase;

final class LoginTest extends TestCase
{
    private Login $form;

    protected function setUp(): void
    {
        $this->form = new Login();
        $this->form->init();
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf(Login::class, $this->form);
        $this->assertInstanceOf(AbstractForm::class, $this->form);
        $this->assertEquals('login', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf(Email::class, $this->form->get('email'));
        $this->assertInstanceOf(Password::class, $this->form->get('password'));
    }

    public function testValidDataIsValid(): void
    {
        $this->form->setData([
            'email'    => 'a@b.com',
            'password' => 'P@55word', // pragma: allowlist secret
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testEmptyDataIsInvalid(): void
    {
        $this->form->setData([
            'email'    => '',
            'password' => '',
        ]);

        $this->assertFalse($this->form->isValid());

        $messages = $this->form->getMessages();
        $this->assertEquals(['email' => [0 => 'cannot-be-empty']], array_intersect_key($messages, ['email' => true]));
        $this->assertEquals(['password' => [0 => 'cannot-be-empty']], array_intersect_key($messages, ['password' => true]));
    }

    public function testEmailIsTrimmedAndLowercased(): void
    {
        $this->form->setData([
            'email'    => '  USER@EXAMPLE.COM  ',
            'password' => 'P@55word', // pragma: allowlist secret
        ]);

        $this->form->isValid();
        $data = $this->form->getData();
        $this->assertEquals('user@example.com', $data['email']);
    }
}
