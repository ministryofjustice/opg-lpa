<?php

declare(strict_types=1);

namespace ApplicationTest\Form\User;

use Application\Form\User\ConfirmEmail as ConfirmEmailForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class ConfirmEmailTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new ConfirmEmailForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\User\ConfirmEmail', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('confirm-email', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email_confirm'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'email'         => 'a@b.com',
            'email_confirm' => 'a@b.com',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'email'         => '',
            'email_confirm' => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'email' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'email_confirm' => [
                'isEmpty' => 'cannot-be-empty'
            ],
        ], $this->form->getMessages());
    }
}
