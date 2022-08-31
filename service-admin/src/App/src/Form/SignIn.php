<?php

namespace App\Form;

use App\Validator;
use App\Filter\StandardInputFilterChain;
use Laminas\Filter;
use Laminas\Form\Element\Text;
use Laminas\Form\Element\Password;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;

/**
 * Class SignIn
 * @package App\Form
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

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        //  Email field
        $field = new Text('email');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create())
            ->attach(new Filter\StringToLower());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(), true)
            ->attach(new Validator\GovUkEmail());

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

        //  Csrf field
        $this->addCsrfElement($inputFilter);
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
