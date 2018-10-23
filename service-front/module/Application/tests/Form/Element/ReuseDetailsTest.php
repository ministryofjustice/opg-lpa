<?php

namespace ApplicationTest\Form\Element;

use Application\Form\Element\ReuseDetails;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ReuseDetailsTest extends MockeryTestCase
{
    /**
     * @var ReuseDetails
     */
    private $element;

    public function setUp()
    {
        $this->element = new ReuseDetails();

        $this->element->setValueOptions([
            'actorReuseDetails' => [
                [
                    'label' => 'Actor Label 1',
                ],
                [
                    'label' => 'Actor Label 2',
                ],
                [
                    'label' => 'Actor Label 3',
                ],
            ],
        ]);
    }

    public function testValueOptions()
    {
        $this->assertEquals([
            [
                'label' => 'Actor Label 1',
                'value' => 0,
                'label_attributes' => [
                    'class' => 'text block-label flush--left'
                ],
            ],
            [
                'label' => 'Actor Label 2',
                'value' => 1,
                'label_attributes' => [
                    'class' => 'text block-label flush--left'
                ],
            ],
            [
                'label' => 'Actor Label 3',
                'value' => 2,
                'label_attributes' => [
                    'class' => 'text block-label flush--left'
                ],
            ],
            [
                'label' => 'None of the above - I want to add a new person',
                'value' => -1,
                'label_attributes' => [
                    'class' => 'text block-label flush--left'
                ],
            ],
        ], $this->element->getValueOptions());
    }
}
