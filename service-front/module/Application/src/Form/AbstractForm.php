<?php

namespace Application\Form;

use Laminas\Form\Element\Textarea;
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
        $fieldName = $inputData['name'];

        // Password fields: no StripTags or StringTrim, as these filters
        // strip leading/trailing spaces and remove anything that looks
        // like HTML, such as <>
        $isPasswordField = in_array($fieldName, ['password', 'password_confirm', 'password_current']);

        // Textarea fields: no StripTags, as the filter treats characters
        // like < and > as HTML tags and strips everything from < to the
        // next > (or end of string), destroying legitimate user input.
        // Output escaping in templates (Twig auto-escape) handles XSS.
        $isTextareaField = $this->has($fieldName) && $this->get($fieldName) instanceof Textarea;

        if (!$isPasswordField) {
            $filters = [];

            if (!$isTextareaField) {
                $filters[] = ['name' => 'Laminas\Filter\StripTags'];
            }

            $filters[] = ['name' => 'Laminas\Filter\StringTrim'];

            $inputData = array_merge_recursive([
                'filters' => $filters,
            ], $inputData);
        }

        $filter = $this->getInputFilter();
        $filter->add($inputData);
    }
}
