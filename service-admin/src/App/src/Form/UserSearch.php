<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInput as StandardInputFilter;
use Laminas\Filter;
use Laminas\Form\Element\Text;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;

/**
 * Class UserSearch
 * @package App\Form
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

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        //  Email field
        $field = new Text('email');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(new StandardInputFilter())
            ->attach(new Filter\StringToLower());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true)
            ->attach(new Validator\Email());

        $input->setRequired(true);

        $this->add($field);
        $inputFilter->add($input);
    }
}
