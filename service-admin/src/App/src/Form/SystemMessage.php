<?php

namespace App\Form;

use Zend\Form\Element\Textarea;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\Validator\StringLength;

/**
 * Class SystemMessage
 * @package App\Form
 */
class SystemMessage extends AbstractForm
{
    private $maxMessageLength = 8000;

    /**
     * SystemMessage constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        //  Message field
        $field = new Textarea('message');
        $input = new Input($field->getName());
        $input->setRequired(false);

        $validator = new StringLength([
            'max' => $this->maxMessageLength,
            'messages' => [
                StringLength::TOO_LONG => 'Limit the message to ' . $this->maxMessageLength . ' characters',
            ],
        ]);

        $input->getValidatorChain()
            ->attach($validator);

        $this->add($field);
        $inputFilter->add($input);

        //  Csrf field
        //  TODO - Add this in the constructor if the options contain 'csrf' value
        $this->addCsrfElement($inputFilter);
    }
}
