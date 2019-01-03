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
        ],
        'health-and-welfare' => [
            'label' => 'Health and welfare',
            'value' => 'health-and-welfare',
        ],
    ];
}
