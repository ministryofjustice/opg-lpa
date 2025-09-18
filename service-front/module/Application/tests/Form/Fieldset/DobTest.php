<?php

namespace ApplicationTest\Form\Lpa;

use Application\Form\Fieldset\Dob as DobFieldset;
use Mockery\Adapter\Phpunit\MockeryTestCase;

final class DobTest extends MockeryTestCase
{
    public function testNameAndInstance()
    {
        $fieldSet = new DobFieldset();

        $this->assertInstanceOf('Application\Form\Fieldset\Dob', $fieldSet);
        $this->assertInstanceOf('Laminas\Form\Fieldset', $fieldSet);
    }

    public function testElements()
    {
        $fieldSet = new DobFieldset();

        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldSet->get('day'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldSet->get('month'));
        $this->assertInstanceOf('Laminas\Form\Element\Text', $fieldSet->get('year'));
    }

    public function testSetAndGetMessages()
    {
        $testMessages = [
            'some-field' => 'A big error message',
        ];

        $fieldSet = new DobFieldset();

        $fieldSet->setMessages($testMessages);

        $this->assertEquals($fieldSet->getMessages(), $testMessages);
    }
}
