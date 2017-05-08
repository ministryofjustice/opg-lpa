<?php

namespace Application\Form\Element;

use Zend\Form\Element\Radio;

class Type extends Radio
{
    /**
     * @var array
     */
    protected $valueOptions =  [
        'property-and-financial' => [
            'label' => 'Property and financial affairs',
            'value' => 'property-and-financial',
            'label_attributes' => [
                'for' => 'property-and-financial',
            ],
            'attributes' => [
                'id' => 'property-and-financial',
            ],
        ],
        'health-and-welfare' => [
            'label' => 'Health and welfare',
            'value' => 'health-and-welfare',
            'label_attributes' => [
                'for' => 'health-and-welfare',
            ],
            'attributes' => [
                'id' => 'health-and-welfare',
            ],
        ],
    ];
}
