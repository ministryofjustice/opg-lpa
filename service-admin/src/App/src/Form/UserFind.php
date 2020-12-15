<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInput as StandardInputFilter;
use Laminas\Filter;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Hidden;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;

/**
 * Class UserFind
 * @package App\Form
 */
class UserFind extends AbstractForm
{
    /**
     * UserFind constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        //  query field
        $field = new Text('query');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(new StandardInputFilter());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true);

        $this->add($field);
        $inputFilter->add($input);

        // offset field
        $offset = new Hidden('offset');
        $offsetInput = new Input($offset->getName());

        $offsetInput->getFilterChain()
            ->attach(new StandardInputFilter());

        $offsetInput->getValidatorChain()
            ->attach(new Validator\Digits(), true);

        $this->add($offset);
        $inputFilter->add($offsetInput);
    }
}
