<?php

namespace App\Form;

use Zend\InputFilter\InputFilter;

/**
 * Class Feedback
 * @package App\Form
 */
class Feedback extends AbstractForm
{
    /**
     * Feedback constructor
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = new InputFilter();
        $this->setInputFilter($inputFilter);

        //  Start date
        $startDate = new Fieldset\Date('start-date');

        $this->add($startDate);
        $inputFilter->add($startDate->getInputFilter(), $startDate->getName());

        //  End date
        $endDate = new Fieldset\Date('end-date');

        $this->add($endDate);
        $inputFilter->add($endDate->getInputFilter(), $endDate->getName());

        //  Csrf field
        $this->addCsrfElement($inputFilter);
    }
}
