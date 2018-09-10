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

        $expected = '<label><input type="radio" name="" value="1">Option value 1</label><label><input type="radio" name="" value="2">Option value 2</label><label><input type="radio" name="" value="3">Option value 3</label><label><input type="radio" name="" value="4">Option value 4</label><label><input type="radio" name="" value="5">Option value 5</label>';

        $this->assertEquals($expected, $html);
    }
}
