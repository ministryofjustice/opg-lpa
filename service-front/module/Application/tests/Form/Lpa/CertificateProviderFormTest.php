<?php

declare(strict_types=1);

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\CertificateProviderForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class CertificateProviderFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp() : void
    {
        $this->setUpForm(new CertificateProviderForm());
    }

    public function testNameAndInstances(): void
    {
        $this->assertInstanceOf('Application\Form\Lpa\CertificateProviderForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractActorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-certificate-provider', $this->form->getName());
    }

    public function testElements(): void
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-title'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-first'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-last'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address1'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address2'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address3'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-postcode'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK(): void
    {
        $this->form->setData(array_merge([
            'name-title'       => 'Mr',
            'name-first'       => 'first',
            'name-last'        => 'last',
            'address-address1' => 'add1',
            'address-postcode' => 'postcode',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid(): void
    {
        $this->form->setData(array_merge([
            'name-title'       => '',
            'name-first'       => '',
            'name-last'        => '',
            'address-address1' => 'add1',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'name-first' => [
                0 => 'cannot-be-blank'
            ],
            'name-last' => [
                0 => 'cannot-be-blank'
            ],
            'address-address2' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'address-postcode' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'name-title' => [
                0 => 'cannot-be-blank'
            ],
        ], $this->form->getMessages());
    }
}
