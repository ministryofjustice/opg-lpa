<?php

namespace ApplicationTest\Form\Admin;

use Application\Form\Admin\UserSearchForm;
use ApplicationTest\Form\FormTestSetupTrait;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class UserSearchFormTest extends MockeryTestCase
{
    use FormTestSetupTrait;

    /**
     * Set up the form to test
     */
    public function setUp()
    {
        $this->setUpCsrfForm(new UserSearchForm());
    }

    public function testNameAndInstances()
    {
        $this->assertInstanceOf('Application\Form\Admin\UserSearchForm', $this->form);
        $this->assertInstanceOf('Application\Form\AbstractCsrfForm', $this->form);
        $this->assertEquals('admin-user-search', $this->form->getName());
    }

    public function testElements()
    {
        $this->assertInstanceOf('Zend\Form\Element\Email', $this->form->get('email'));
    }

    public function testValidateByModelOK()
    {
        $this->form->setData(array_merge([
            'email' => 'a@b.com',
        ], $this->getCsrfData()));

        $this->assertTrue($this->form->isValid());
        $this->assertEquals([], $this->form->getMessages());
    }

    public function testValidateByModelInvalid()
    {
        $this->form->setData(array_merge([
            'email' => '',
        ], $this->getCsrfData()));

        $this->assertFalse($this->form->isValid());

        $this->assertEquals([
            'email' => [
                0 => 'cannot-be-empty'
            ],
        ], $this->form->getMessages());
    }
}
