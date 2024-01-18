<?php

namespace Application\Form;

use Laminas\Form\Form;

/**
 * @template-extends AbstractForm
 */

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
     */
    protected function addToInputFilter(array $inputData)
    {
        // Only add the StripTags and StringTrim filters for
        // non-password fields; if used for password fields, these
        // filters strip leading/trailing spaces and remove anything that
        // looks like HTML, such as <>
        if (!in_array($inputData['name'], ['password', 'password_confirm', 'password_current'])) {
            // Merge additional input filters for non-password fields
            $inputData = array_merge_recursive([
                'filters'  => [
                    [
                        'name' => 'Laminas\Filter\StripTags'
                    ],
                    [
                        'name' => 'Laminas\Filter\StringTrim'
                    ],
                ]
            ], $inputData);
        }

        $filter = $this->getInputFilter();
        $filter->add($inputData);
    }
}
