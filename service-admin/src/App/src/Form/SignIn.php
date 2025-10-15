<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInputFilterChain;
use Laminas\Filter;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Password;
use Laminas\InputFilter\Input;
use Laminas\Validator\Regex;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

class SignIn extends AbstractForm
{
    /**
     * SignIn constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = $this->getInputFilter();

        //  Email field
        $field = new Text('email');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create())
            ->attach(new Filter\StringToLower());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true)
            ->attach(new Validator\Regex([
                'pattern'  => '/^[a-zA-Z0-9.!#$%&\'*+\/=?^_`{|}~-]+@.*?(gov.uk)/',
                'messages' => [
                    Regex::NOT_MATCH => 'Please use a GOV.UK email address',
                ],
            ]));

        $input->setRequired(true);

        $this->add($field);
        $inputFilter->add($input);

        //  Password field
        $field = new Password('password');
        $input = new Input($field->getName());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty());

        $input->setRequired(true);

        $this->add($field);
        $inputFilter->add($input);

        // Csrf field
        $this->addCsrfElement();
    }

    /**
     * Set the authentication error reference
     *
     * @param string $errorReference
     * @return void
     */
    public function setAuthError(string $errorReference): void
    {
        $this->setMessages([
            'email' => [
                $errorReference => $errorReference,
            ],
        ]);
    }
}
