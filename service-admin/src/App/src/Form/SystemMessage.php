<?php

namespace App\Form;

use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\StringLength;

/**
 * Class SystemMessage
 * @package App\Form
 */
class SystemMessage extends AbstractForm
{
    /**
     * @var int
     */
    private $maxMessageLength = 8000;

    /**
     * SystemMessage constructor
     *
     * @param array<string, mixed> $options
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
        $this->addCsrfElement($inputFilter);
    }
}
