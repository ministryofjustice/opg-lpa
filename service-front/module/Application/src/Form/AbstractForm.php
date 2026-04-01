<?php

namespace Application\Form;

use Application\Filter\StripTagsPreservingAngleBrackets;
use Laminas\Form\Form;

/**
 * @template T
 * @template-extends Form<T>
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
        // Only add the tag stripping and trimming filters for
        // non-password fields; if used for password fields, these
        // filters strip leading/trailing spaces and remove anything that
        // looks like HTML, such as <>
        if (!in_array($inputData['name'], ['password', 'password_confirm', 'password_current'])) {
            // Use StripTagsPreservingAngleBrackets (backed by HTML Purifier) instead of Laminas StripTags.
            $inputData = array_merge_recursive([
                'filters'  => [
                    [
                        'name' => StripTagsPreservingAngleBrackets::class,
                    ],
                    [
                        'name' => 'Laminas\Filter\StringTrim',
                    ],
                ]
            ], $inputData);
        }

        $filter = $this->getInputFilter();
        $filter->add($inputData);
    }
}
