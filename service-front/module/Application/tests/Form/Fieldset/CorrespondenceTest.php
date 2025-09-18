<?php

namespace ApplicationTest\Form\FieldSet;

use Application\Form\Fieldset\Correspondence;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class CorrespondenceTest extends MockeryTestCase
{
    public function testNameAndInstance()
    {
        $fieldSet = new Correspondence();

        $this->assertInstanceOf('Application\Form\Fieldset\Correspondence', $fieldSet);
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldSet);
    }

    public function testElements()
    {
        $fieldSet = new Correspondence();

        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldSet->get('contactByEmail'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldSet->get('contactByPhone'));
        $this->assertInstanceOf('Laminas\Form\Element\Checkbox', $fieldSet->get('contactByPost'));
        $this->assertInstanceOf('Laminas\Form\Element\Email', $fieldSet->get('email-address'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldSet->get('phone-number'));
    }

    public function testSetAndGetMessages()
    {
        $testMessages = [
            'some-field' => 'A big error message',
        ];

        $fieldSet = new Correspondence();

        $fieldSet->setMessages($testMessages);

        $this->assertEquals($fieldSet->getMessages(), $testMessages);
    }
}
