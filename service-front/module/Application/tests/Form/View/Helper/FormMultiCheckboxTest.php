<?php

namespace ApplicationTest\Form\View\Helper;

use PHPUnit\Framework\TestCase;
use Zend\Form\Element\MultiCheckbox;

class FormMultiCheckboxTest extends TestCase
{
    public function testRenderOptions()
    {
        $options = [
            1 => 'Option value 1',
            2 => 'Option value 2',
            3 => 'Option value 3',
            4 => 'Option value 4',
            5 => 'Option value 5',
        ];

        $checkbox = new MultiCheckbox();

        $helper = new TestableFormMultiCheckbox();

        $html = $helper->callRenderOptions($checkbox, $options, [0], []);

        $expected = '<div><input value="1"><label>Option value 1</label></div>' .
            '<div><input value="2"><label>Option value 2</label></div>' .
            '<div><input value="3"><label>Option value 3</label></div>' .
            '<div><input value="4"><label>Option value 4</label></div>' .
            '<div><input value="5"><label>Option value 5</label></div>';

        $this->assertEquals($expected, $html);
    }

    public function testRenderWithAttributes()
    {
        $options = [
            'value_options' => [
                'value' => '1',
                'label' => 'Option value 1',
                'attributes' => [
                    'id' => 'option-1',
                    'div-attributes' => ['class' => 'test_class']
                ],
                'label_attributes' => [
                    'for' => 'option-1',
                ],
            ],
            [
                'value' => '2',
                'label' => 'Option value 2',
                'selected' => true,
            ],
            [
                'value' => '3',
                'label' => 'Option value 3',
                'selected' => true,
            ],
        ];

        $checkbox = new MultiCheckbox();
        $helper = new TestableFormMultiCheckbox();

        $html = $helper->callRenderOptions($checkbox, $options, [0], []);

        $expected = '<div class="test_class">' .
            '<input id="option-1" value="1">' .
            '<label for="option-1">Option value 1</label>' .
            '</div>' .
            '<div>' .
            '<input value="2" checked="checked">' .
            '<label>Option value 2</label>' .
            '</div>' .
            '<div>' .
            '<input value="3" checked="checked">' .
            '<label>Option value 3</label>' .
            '</div>';

        $this->assertEquals($expected, $html);
    }
}
