<?php

namespace ApplicationTest\Form\User;

use Application\Form\User\ChangePassword as ChangePasswordForm;
use Application\Model\Service\Authentication\AuthenticationService;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class ChangePasswordTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $form = new ChangePasswordForm();

        $authenticationService = m::mock(AuthenticationService::class);
        $authenticationService->shouldReceive('setPassword');
        $authenticationService->shouldReceive('verify')->andReturnTrue();

        /** @var AuthenticationService $authenticationService */
        $form->setAuthenticationService($authenticationService);

        $this->setUpForm($form);
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\User\ChangePassword', $this->form);
        $this->assertInstanceOf('Application\Form\User\SetPassword', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('change-password', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password_current'));
        //  From SetPassword
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password'));
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password_confirm'));
        $this->assertInstanceOf('Laminas\Form\Element\Hidden', $this->form->get('skip_confirm_password'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'password_current'      => 'P@55wordword',
            'password'              => 'P@55wordword',
            'password_confirm'      => 'P@55wordword',
            'skip_confirm_password' => '0',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelOKWithHTMLTags()
    {
        $this->form->setData(array_merge([
            'password_current'      => 'P@55wordword',
            'password'              => '<>P@55wordword',
            'password_confirm'      => '<>P@55wordword',
            'skip_confirm_password' => '0',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelOKWithLeadingAndTrailingSpaces()
    {
        $this->form->setData(array_merge([
            'password_current'      => 'P@55word',
            'password'              => '  P@55word  ',
            'password_confirm'      => '  P@55word  ',
            'skip_confirm_password' => '0',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'password_current'      => '',
            'password'              => '',
            'password_confirm'      => '',
            'skip_confirm_password' => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'password_current' => [
                'isEmpty' => 'cannot-be-empty'
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
