<?php

namespace ApplicationTest\Form\View\Helper;

use Application\Form\View\Helper\FormRadio;
use PHPUnit\Framework\TestCase;
use Zend\Form\Element\Radio;

class FormRadioTest extends TestCase
{
    public function testRender()
    {
        $options = [
            1 => 'Option value 1',
            2 => 'Option value 2',
            3 => 'Option value 3',
            4 => 'Option value 4',
            5 => 'Option value 5',
        ];

        $radio = new Radio();
        $radio->setName('test-name');
        $radio->setValueOptions($options);

        $helper = new FormRadio();

        $html = $helper($radio);

        $expected = '<div>' .
            '<input type="radio" name="test-name" value="1" id="test-name-1">' .
            '<label for="test-name-1">Option value 1</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="test-name" value="2" id="test-name-2">' .
            '<label for="test-name-2">Option value 2</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="test-name" value="3" id="test-name-3">' .
            '<label for="test-name-3">Option value 3</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="test-name" value="4" id="test-name-4">' .
            '<label for="test-name-4">Option value 4</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="test-name" value="5" id="test-name-5">' .
            '<label for="test-name-5">Option value 5</label>' .
            '</div>';

        $this->assertEquals($expected, $html);
    }

    public function testRenderWithAttributes()
    {
        $options = [
            1 => 'Option value 1',
            2 => 'Option value 2',
            3 => 'Option value 3',
            4 => 'Option value 4',
            5 => 'Option value 5',
        ];

        $radio = new Radio();
        $radio->setName('test-name');
        $radio->setValueOptions($options);
        $radio->setAttributes(['div-attributes' => ['class' => 'test_class']]);

        $helper = new FormRadio();

        $html = $helper($radio);

        $expected = '<div class="test_class">' .
            '<input type="radio" name="test-name" value="1" id="test-name-1">' .
            '<label for="test-name-1">Option value 1</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="test-name" value="2" id="test-name-2">' .
            '<label for="test-name-2">Option value 2</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="test-name" value="3" id="test-name-3">' .
            '<label for="test-name-3">Option value 3</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="test-name" value="4" id="test-name-4">' .
            '<label for="test-name-4">Option value 4</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="test-name" value="5" id="test-name-5">' .
            '<label for="test-name-5">Option value 5</label>' .
            '</div>';

        $this->assertEquals($expected, $html);
    }

    public function testRenderGroupAndIndividualDivAttributes()
    {
        $options = [
            'first'  => [
                'value' => 'one',
                'label' => 'One',
                'div-attributes' => [
                    'class' => 'another-class',
                    'data-target' => 'target-one',
                ],
            ],
            'second'  => [
                'value' => 'two',
                'label' => 'Two',
            ],
            'third'  => [
                'value' => 'three',
                'label' => 'Three',
                'div-attributes' => [
                    'data-target' => 'target-three',
                ]
            ],
        ];

        $radio = new Radio();
        $radio->setName('test-name');
        $radio->setAttributes(['div-attributes' => ['class' => 'all-class']]);
        $radio->setValueOptions($options);

        $helper = new FormRadio();

        $html = $helper($radio);

        $expected = '<div class="another-class" data-target="target-one">' .
            '<input type="radio" name="test-name" value="one" id="test-name-one">' .
            '<label for="test-name-one">One</label>' .
            '</div>' .
            '<div class="all-class">' .
            '<input type="radio" name="test-name" value="two" id="test-name-two">' .
            '<label for="test-name-two">Two</label>' .
            '</div>' .
            '<div class="all-class" data-target="target-three">' .
            '<input type="radio" name="test-name" value="three" id="test-name-three">' .
            '<label for="test-name-three">Three</label>' .
            '</div>';

        $this->assertEquals($expected, $html);
    }

    public function testOutputOption()
    {
        $options = [
            1 => 'Option value 1',
            2 => 'Option value 2',
            3 => 'Option value 3',
            4 => 'Option value 4',
            5 => 'Option value 5',
        ];

        $radio = new Radio();
        $radio->setName('test-radio');
        $radio->setValueOptions($options);

        $helper = new FormRadio();

        $html = $helper->outputOption($radio, 1);

        $expected = '<div>' .
            '<input type="radio" name="test-radio" id="test-radio-1" value="1">' .
            '<label for="test-radio-1">Option value 1</label>' .
            '</div>';

        $this->assertEquals($expected, $html);
    }
}
