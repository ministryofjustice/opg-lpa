<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInputFilterChain;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\Input;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

class UserSearch extends AbstractForm
{
    /**
     * UserSearch constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        //  Email field
        $field = new Text('email');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true)
            ->attach(new Validator\Email());

        $input->setRequired(true);

        $this->add($field);
        $this->getInputFilter()->add($input);

        // Csrf field
        $this->addCsrfElement();
    }
}
