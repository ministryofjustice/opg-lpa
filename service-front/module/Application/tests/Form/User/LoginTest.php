<?php

declare(strict_types=1);

namespace ApplicationTest\Form\User;

use Application\Form\User\Login as LoginForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class LoginTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new LoginForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\User\Login', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('login', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email'));
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'email'     => 'a@b.com',
            'password'  => 'P@55word',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'email'     => '',
            'password'  => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'email' => [
                0 => 'cannot-be-empty'
            ],
            'password' => [
                0 => 'cannot-be-empty'
            ],
        ], $this->form->getMessages());
    }
}
