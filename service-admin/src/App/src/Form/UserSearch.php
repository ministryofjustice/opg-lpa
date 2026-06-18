<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInputFilterChain;
use Laminas\Form\Element\Select;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\Input;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

class UserSearch extends AbstractForm
{
    public const SEARCH_TYPE_OPTIONS = [
        'email'      => 'Email',
        'userId'     => 'User ID',
        'aReference' => 'A Reference',
    ];

    /**
     * UserSearch constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        // Search type select
        $select = new Select('searchType');
        $select->setValueOptions(self::SEARCH_TYPE_OPTIONS);

        $selectInput = new Input($select->getName());
        $selectInput->setRequired(true);

        $this->add($select);
        $this->getInputFilter()->add($selectInput);

        //  Search value field
        $field = new Text('email');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true);

        $input->setRequired(true);

        $this->add($field);
        $this->getInputFilter()->add($input);

        // Csrf field
        $this->addCsrfElement();
    }
}
