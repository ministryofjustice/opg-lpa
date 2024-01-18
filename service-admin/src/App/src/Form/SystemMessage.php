<?php

namespace App\Form;

use App\Filter\StandardInputFilterChain;
use Laminas\Form\Element\Textarea;
use Laminas\InputFilter\Input;
use Laminas\Validator\StringLength;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

class SystemMessage extends AbstractForm
{
    /**
     * @var int
     *
     * This is used in the constructor
     * @psalm-suppress UnusedProperty
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

        // Message HTML field
        $field = new Textarea('message');
        $this->add($field);

        // Filter inputs
        $inputFilter = $this->getInputFilter();

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

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $inputFilter->add($input);

        // Csrf field
        $this->addCsrfElement();
    }
}
