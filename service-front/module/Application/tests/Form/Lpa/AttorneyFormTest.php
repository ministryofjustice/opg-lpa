<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\AttorneyForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class AttorneyFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp(): void
    {
        $this->setUpForm(new AttorneyForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\AttorneyForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractActorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-attorney', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-title'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-first'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-last'));
        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $this->form->get('dob-date'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email-address'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address1'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address2'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address3'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-postcode'));
        $this->assertInstanceOf('Laminas\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'name-title'       => 'Mr',
            'name-first'       => 'first',
            'name-last'        => 'last',
            'email-address'    => '',
            'address-address1' => 'add1',
            'address-postcode' => 'postcode',
            'dob-date'         => [
                'year'  => '1984',
                'month' => '05',
                'day'   => '20'
            ],
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'name-title'       => '',
            'name-first'       => '',
            'name-last'        => '',
            'address-address1' => 'add1',
            'email-address'    => 'inv@lid@mail.address',
            'dob-date'         => [
                'year'  => '1984',
                'month' => '05',
                'day'   => '20'
            ],
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'name-first' => [
                0 => 'cannot-be-blank'
            ],
            'name-last' => [
                0 => 'cannot-be-blank'
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
            'name-title' => [
                0 => 'cannot-be-blank'
            ],
        ], $this->form->getMessages());
    }
}
