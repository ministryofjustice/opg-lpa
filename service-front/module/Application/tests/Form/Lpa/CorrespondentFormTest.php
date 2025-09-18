<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\CorrespondentForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class CorrespondentFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp() : void
    {
        $this->setUpForm(new CorrespondentForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Lpa\CorrespondentForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractActorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('form-correspondent', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Laminas\Form\Element\Hidden', $this->form->get('who'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-title'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-first'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('name-last'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('company'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address1'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address2'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-address3'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('address-postcode'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $this->form->get('email-address'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $this->form->get('phone-number'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'who'              => 'donor',
            'name-title'       => 'Mr',
            'name-first'       => 'first',
            'name-last'        => 'last',
            'company'          => 'some inc.',
            'address-address1' => 'add1',
            'address-postcode' => 'postcode',
            'email-address'    => '',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'who'              => '',
            'name-title'       => '',
            'name-first'       => '',
            'name-last'        => '',
            'company'          => '',
            'address-address1' => 'add1',
            'email-address'    => 'inv@lid@mail.address',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'who' => [
                0 => 'cannot-be-blank',
                1 => 'allowed-values:donor,attorney,certificateProvider,other',
            ],
            'name-title' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'name-first' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'name-last' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'company' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'address-address2' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'address-postcode' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'email-address' => [
                0 => 'invalid-email-address'
            ],
        ], $this->form->getMessages());
    }
}
