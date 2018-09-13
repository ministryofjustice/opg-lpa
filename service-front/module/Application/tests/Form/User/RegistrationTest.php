<?php

namespace ApplicationTest\Form\User;

use Application\Form\User\Registration as RegistrationForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class RegistrationTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpCsrfForm(new RegistrationForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\User\Registration', $this->form);
        $this->assertInstanceOf('Application\Form\User\SetPassword', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('registration', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Email', $this->form->get('email'));
        $this->assertInstanceOf('Zend\Form\Element\Email', $this->form->get('email_confirm'));
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $this->form->get('terms'));
        //  From SetPassword
        $this->assertInstanceOf('Zend\Form\Element\Password', $this->form->get('password'));
        $this->assertInstanceOf('Zend\Form\Element\Password', $this->form->get('password_confirm'));
        $this->assertInstanceOf('Zend\Form\Element\Hidden', $this->form->get('skip_confirm_password'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'email'                 => 'a@b.com',
            'email_confirm'         => 'a@b.com',
            'terms'                 => '1',
            'password'              => 'P@55word',
            'password_confirm'      => 'P@55word',
            'skip_confirm_password' => '0',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'email'                 => '',
            'email_confirm'         => '',
            'terms'                 => '',
            'password'              => '',
            'password_confirm'      => '',
            'skip_confirm_password' => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'email' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'email_confirm' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'terms' => [
                0 => 'must-be-checked'
            ],
            'password' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'password_confirm' => [
                'isEmpty' => 'cannot-be-empty'
            ],
        ], $this->form->getMessages());
    }
}
