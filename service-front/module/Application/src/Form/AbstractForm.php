<?php

namespace Application\Form;

use Laminas\Form\Form;

abstract class AbstractForm extends Form
{
    public function init()
    {
        $this->setAttribute('method', 'post');
        $this->setAttribute('novalidate', 'novalidate');

        parent::init();

        $this->prepare();
    }

    /**
     * Add input data to input filter
     *
     * @param array $inputData
     *
     * @return void
     */
    protected function addToInputFilter(array $inputData): void
    {
        //  Merge the required input filters into the input data
        $inputData = array_merge_recursive([
            'filters'  => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ],
            ]
        ], $inputData);

        $filter = $this->getInputFilter();

        $filter->add($inputData);
    }
}
