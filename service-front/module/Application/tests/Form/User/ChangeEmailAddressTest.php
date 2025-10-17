<?php

declare(strict_types=1);

namespace ApplicationTest\Form\User;

use Application\Form\User\ChangeEmailAddress;
use Application\Model\Service\Authentication\AuthenticationService;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

final class ChangeEmailAddressTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $form = new ChangeEmailAddress();

        $authenticationService = m::mock(AuthenticationService::class);
        $authenticationService->shouldReceive('setPassword');
        $authenticationService->shouldReceive('verify')->andReturnTrue();

        /** @var AuthenticationService $authenticationService */
        $form->setAuthenticationService($authenticationService);

        $this->setUpForm($form);
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\User\ChangeEmailAddress', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('change-email-address', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Password', $this->form->get('password_current'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email_confirm'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'password_current'  => 'thecurrentpassword',
            'email'             => 'a@b.com',
            'email_confirm'     => 'a@b.com',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());

        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'password_current'  => '',
            'email'             => '',
            'email_current'     => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'password_current' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'email' => [
                'isEmpty' => 'cannot-be-empty'
            ],
            'email_confirm' => [
                'isEmpty' => 'cannot-be-empty'
            ],
        ], $this->form->getMessages());
    }
}
