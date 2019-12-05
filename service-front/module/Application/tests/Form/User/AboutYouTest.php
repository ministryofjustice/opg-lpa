<?php

namespace ApplicationTest\Form\User;

use Application\Form\User\AboutYou as AboutYouForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class AboutYouTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpActorForm(new AboutYouForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\User\AboutYou', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractActorForm', $this->form);
        $this->assertInstanceOf('Application\Form\Lpa\AbstractLpaForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractForm', $this->form);
        $this->assertEquals('about-you', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('name-title'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('name-first'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('name-last'));
        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $this->form->get('dob-date'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-address1'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-address2'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-address3'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $this->form->get('address-postcode'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'id' => '854c71a05f3eed0788c127783b435e8c',
            'createdAt' => '2018-08-20 14:59:10.379Z',
            'updatedAt' => '2018-08-20 14:59:10.379Z',
            'name-title'       => 'Mr',
            'name-first'       => 'first',
            'name-last'        => 'last',
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
            //  No id, createdAt or updatedAt
            'name-title'       => '',
            'name-first'       => '',
            'name-last'        => '',
            'address-address1' => 'add1',
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
            'address-address2' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'address-postcode' => [
                0 => 'linked-1-cannot-be-null'
            ],
            'name-title' => [
                0 => 'cannot-be-blank'
            ],
            'id' => [
                0 => 'cannot-be-blank'
            ],
            'createdAt' => [
                0 => 'cannot-be-blank'
            ],
            'updatedAt' => [
                0 => 'cannot-be-blank'
            ],
        ], $this->form->getMessages());
    }
}
