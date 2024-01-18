<?php

namespace App\Form;

use DateTime;
use Exception;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

class Feedback extends AbstractForm
{
    /**
     * Feedback constructor
     *
     * @param array<string, mixed> $options
     */
    public function __construct($options = [])
    {
        parent::__construct(self::class, $options);

        $inputFilter = $this->getInputFilter();

        //  Start date
        $startDate = new Fieldset\Date('start-date');

        $this->add($startDate);
        $inputFilter->add($startDate->getInputFilter(), $startDate->getName());

        //  End date
        $endDate = new Fieldset\Date('end-date');

        $this->add($endDate);
        $inputFilter->add($endDate->getInputFilter(), $endDate->getName());

        // Csrf field
        $this->addCsrfElement();
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if (parent::isValid()) {
            if ($this->getDateValue('start-date') <= $this->getDateValue('end-date')) {
                return true;
            }

            //  Add error to the end date
            $this->setMessages([
                'end-date' => [
                    [
                        'end-before-start',
                    ],
                ],
            ]);
        }

        return false;
    }

    /**
     * Get the DateTime representation of the fieldset values
     *
     * @param string $fieldname
     * @return DateTime|null
     * @throws Exception
     */
    public function getDateValue(string $fieldname)
    {
        if (!in_array($fieldname, ['start-date', 'end-date'])) {
            throw new Exception('Date value can only be retrieved for fields start-date and end-date');
        }

        $dateInput = $this->get($fieldname);

        if (!is_a($dateInput, \App\Form\Fieldset\Date::class)) {
            throw new Exception('Field ' . $fieldname . ' is not a Fieldset\Date instance');
        }

        return $dateInput->getDateValue();
    }
}
