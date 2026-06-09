<?php

declare(strict_types=1);

namespace App\Form;

use App\Filter\StripTagsPreservingAngleBrackets;
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
        if (!in_array($inputData['name'], ['password', 'password_confirm', 'password_current'])) {
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
