<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInput as StandardInputFilter;
use Zend\Filter;
use Zend\Form\Element\Text;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\Validator\EmailAddress;

/**
 * Class UserSearch
 * @package App\Form
 */
class UserSearch extends AbstractForm
{
    /**
     * UserSearch constructor
     *
     * @param array $options
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

        //  Csrf field
        //  TODO - Add this in the constructor if the options contain 'csrf' value
        $this->addCsrfElement($inputFilter);
    }
}
