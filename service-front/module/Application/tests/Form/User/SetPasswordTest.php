<?php

declare(strict_types=1);

namespace ApplicationTest\Form\User;

use Application\Form\User\SetPassword as SetPasswordForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class SetPasswordTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new SetPasswordForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\User\SetPassword', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('set-password', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password'));
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password_confirm'));
        $this->assertInstanceOf('Laminas\Form\Element\Hidden', $this->form->get('skip_confirm_password'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'password'              => 'P@55wordword',
            'password_confirm'      => 'P@55wordword',
            'skip_confirm_password' => '0',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'password'              => '',
            'password_confirm'      => '',
            'skip_confirm_password' => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'password' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'password_confirm' => [
                'isEmpty' => 'cannot-be-empty'
            ],
        ], $this->form->getMessages());
    }
}
