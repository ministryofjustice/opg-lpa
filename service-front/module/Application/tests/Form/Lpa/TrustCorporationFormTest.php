<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\TrustCorporationForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class TrustCorporationFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new TrustCorporationForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\Lpa\TrustCorporationForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractActorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-trust-corporation', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('number'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email-address'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address1'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address2'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address3'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-postcode'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'name'             => 'Some Inc.',
            'number'           => '12345678',
            'email-address'    => '',
            'address-address1' => 'add1',
            'address-postcode' => 'postcode',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'name'             => '',
            'number'           => '',
            'address-address1' => 'add1',
            'email-address'    => 'inv@lid@mail.address',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'name' => [
                0 => 'cannot-be-blank',
            ],
            'number' => [
                0 => 'cannot-be-blank',
            ],
            'email-address' => [
                0 => 'invalid-email-address'
            ],
            'address-address2' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'address-postcode' => [
                0 => 'linked-1-cannot-be-null'
            ],
        ], $this->form->getMessages());
    }
}
