<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInputFilterChain;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Hidden;
use Laminas\InputFilter\Input;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

class UserFind extends AbstractForm
{
    /**
     * UserFind constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = $this->getInputFilter();

        //  query field
        $field = new Text('query');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true);

        $this->add($field);
        $inputFilter->add($input);

        // offset field
        $offset = new Hidden('offset');
        $offsetInput = new Input($offset->getName());

        $offsetInput->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $offsetInput->getValidatorChain()
            ->attach(new Validator\Digits(), true);

        $this->add($offset);
        $inputFilter->add($offsetInput);

        // Csrf field
        $this->addCsrfElement();
    }
}
