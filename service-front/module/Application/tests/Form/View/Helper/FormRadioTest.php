<?php

namespace ApplicationTest\Form\View\Helper;

use Application\Form\View\Helper\FormRadio;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Zend\Form\Element\Radio;

class FormRadioTest extends MockeryTestCase
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
        $radio->setValueOptions($options);

        $helper = new FormRadio();

        $html = $helper($radio);

        $expected = '<div>' .
            '<input type="radio" name="" value="1">' .
            '<label>Option value 1</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="" value="2">' .
            '<label>Option value 2</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="" value="3">' .
            '<label>Option value 3</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="" value="4">' .
            '<label>Option value 4</label>' .
            '</div>' .
            '<div>' .
            '<input type="radio" name="" value="5">' .
            '<label>Option value 5</label>' .
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
        $radio->setValueOptions($options);
        $radio->setAttributes(['div-attributes' => ['class' => 'test_class']]);

        $helper = new FormRadio();

        $html = $helper($radio);

        $expected = '<div class="test_class">' .
            '<input type="radio" name="" value="1">' .
            '<label>Option value 1</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="" value="2">' .
            '<label>Option value 2</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="" value="3">' .
            '<label>Option value 3</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="" value="4">' .
            '<label>Option value 4</label>' .
            '</div>' .
            '<div class="test_class">' .
            '<input type="radio" name="" value="5">' .
            '<label>Option value 5</label>' .
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
        $radio->setValueOptions($options);

        $helper = new FormRadio();

        $html = $helper->outputOption($radio, 1);

        $expected = '<div>' .
            '<input type="radio" name="" value="1">' .
            '<label>Option value 1</label>' .
            '</div>';

        $this->assertEquals($expected, $html);
    }
}
