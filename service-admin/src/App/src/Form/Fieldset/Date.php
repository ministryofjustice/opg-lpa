<?php

namespace App\Form\Fieldset;

use App\Filter\StandardInputFilterChain;
use App\Validator;
use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;
use Laminas\Validator\Callback;
use Laminas\Validator\ValidatorInterface;
use DateTime;

class Date extends Fieldset
{
    /**
     * @var InputFilter
     */
    private $inputFilter;

    /**
     * @param string|null $name
     */
    public function __construct($name = null)
    {
        parent::__construct($name);

        $inputFilter = $this->inputFilter = new InputFilter();

        //------------------------
        // Day

        $field = new Text('day');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(Validator\NotEmpty::INTEGER + Validator\NotEmpty::ZERO), true)
            ->attach(new Validator\Digits(), true)
            ->attach($this->getValidDateValidator(), true)
            ->attach($this->getFutureDateValidator(), true);

        $this->add($field);
        $inputFilter->add($input);

        //------------------------
        // Month

        $field = new Text('month');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(Validator\NotEmpty::INTEGER + Validator\NotEmpty::ZERO), true)
            ->attach(new Validator\Digits(), true)
            ->attach($this->getValidDateValidator(), true)
            ->attach($this->getFutureDateValidator(), true);

        $this->add($field);
        $inputFilter->add($input);

        //------------------------
        // Year

        $field = new Text('year');
        $input = new Input($field->getName());

        $input->getFilterChain()
            ->attach(StandardInputFilterChain::create());

        $input->getValidatorChain()
            ->attach(new Validator\NotEmpty(Validator\NotEmpty::INTEGER + Validator\NotEmpty::ZERO), true)
            ->attach(new Validator\Digits(), true)
            ->attach($this->getValidDateValidator(), true)
            ->attach($this->getFutureDateValidator(), true);

        $this->add($field);
        $inputFilter->add($input);
    }

    /**
     * @return InputFilter
     */
    public function getInputFilter(): InputFilter
    {
        return $this->inputFilter;
    }

    /**
     * Combines the errors from each field into one.
     *
     * @param null|string $elementName
     * @return array<string, mixed>
     */
    public function getMessages($elementName = null): array
    {
        $messages = parent::getMessages($elementName);

        $combined = [];

        foreach ($messages as $errors) {
            $combined = array_merge($combined, $errors);
        }

        return $combined;
    }

    /**
     * @return ValidatorInterface
     */
    private function getValidDateValidator(): ValidatorInterface
    {
        $validator = new Callback(function ($_, $context) {
            if (count(array_filter($context)) != 3) {
                return true;
            }

            return checkdate($context['month'], $context['day'], $context['year']) && ($context['year'] < 9999);
        });

        $validator->setMessage('invalid-date', Callback::INVALID_VALUE);

        return $validator;
    }

    /**
     * @return ValidatorInterface
     */
    private function getFutureDateValidator(): ValidatorInterface
    {
        $validator = new Callback(function ($_, $context) {
            $context = array_filter($context);
            if (count($context) != 3) {
                // Don't validate unless all fields present.
                return true;
            }

            if (!checkdate($context['month'], $context['day'], $context['year'])) {
                // Don't validate if date is invalid
                return true;
            }

            $born = DateTime::createFromFormat('Y-m-d', "{$context['year']}-{$context['month']}-{$context['day']}");

            return ($born < new DateTime());
        });

        $validator->setMessage('future-date', Callback::INVALID_VALUE);

        return $validator;
    }

    /**
     * @return DateTime|null
     */
    public function getDateValue()
    {
        $day = $this->get('day')->getValue();
        $month = $this->get('month')->getValue();
        $year = $this->get('year')->getValue();

        if (is_numeric($day) && is_numeric($month) && is_numeric($year)) {
            return new DateTime(sprintf('%s-%s-%s', $year, $month, $day));
        }

        return null;
    }

    /**
     * Override getName() from the parent class, so that we always return
     * a string or null as the name. This is so that this fieldset can be
     * used with form input filters, as in \App\Form\Feedback.
     *
     * @return ?string
     */
    public function getName(): ?string
    {
        $name = parent::getName();
        return (is_string($name) ? $name : null);
    }
}
