<?php

namespace ApplicationTest\Form\Element;

use Application\Form\Element\Type;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class TypeTest extends MockeryTestCase
{
    /**
     * @var Type
     */
    private $element;

    public function setUp()
    {
        $this->element = new Type();
    }

    public function testValueOptions()
    {
        $this->assertEquals([
            'property-and-financial' => [
                'label' => 'Property and financial affairs',
                'value' => 'property-and-financial',
            ],
            'health-and-welfare' => [
                'label' => 'Health and welfare',
                'value' => 'health-and-welfare',
            ],
        ], $this->element->getValueOptions());
    }

    public function testSelectedValue()
    {
        $this->assertNull($this->element->getValue());

        $this->element->setValue('property-and-financial');

        $this->assertEquals('property-and-financial', $this->element->getValue());
    }
}
