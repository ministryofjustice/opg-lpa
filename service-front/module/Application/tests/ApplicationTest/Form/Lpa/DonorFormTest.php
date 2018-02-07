<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\DonorForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DonorFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpForm(new DonorForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\DonorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractActorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertEquals('form-donor', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('name-title'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('name-first'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('name-last'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('otherNames'));
        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $this->form->get('dob-date'));
        $this->assertInstanceOf('Zend\Form\Element\Email', $this->form->get('email-address'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-address1'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-address2'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-address3'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-postcode'));
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $this->form->get('canSign'));
        $this->assertInstanceOf('Zend\Form\Element\Submit', $this->form->get('submit'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData([
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
            'canSign'          => false
        ]);

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData([
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
            'canSign'          => 123
        ]);

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
            'canSign' => [
                0 => 'expected-type:bool'
            ],
            'name-title' => [
                0 => 'cannot-be-blank'
            ],
        ], $this->form->getMessages());
    }
}
