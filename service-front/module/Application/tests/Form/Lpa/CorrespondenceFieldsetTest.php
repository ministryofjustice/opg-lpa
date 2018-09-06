<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Lpa\CorrespondenceFieldset;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CorrespondenceFieldsetTest extends MockeryTestCase
{
    public function testNameAndInstance()
    {
        $fieldSet = new CorrespondenceFieldset();

        $this->assertInstanceOf('Application\Form\Lpa\CorrespondenceFieldset', $fieldSet);
        $this->assertInstanceOf('Zend\Form\Fieldset', $fieldSet);
    }

    public function testElements()
    {
        $fieldSet = new CorrespondenceFieldset();

        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $fieldSet->get('contactByEmail'));
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $fieldSet->get('contactByPhone'));
        $this->assertInstanceOf('Zend\Form\Element\Checkbox', $fieldSet->get('contactByPost'));
        $this->assertInstanceOf('Zend\Form\Element\Email', $fieldSet->get('email-address'));
        $this->assertInstanceOf('Zend\Form\Element\Text', $fieldSet->get('phone-number'));
    }

    public function testSetAndGetMessages()
    {
        $testMessages = [
            'some-field' => 'A big error message',
        ];

        $fieldSet = new CorrespondenceFieldset();

        $fieldSet->setMessages($testMessages);

        $this->assertEquals($fieldSet->getMessages(), $testMessages);
    }
}
