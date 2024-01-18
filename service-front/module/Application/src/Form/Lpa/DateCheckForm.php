<?php

namespace Application\Form\Lpa;

use Application\Form\AbstractCsrfForm;
use MakeShared\DataModel\Lpa\Document\Document;
use MakeShared\DataModel\Lpa\Lpa;

/**
 * @template T
 * @template-extends AbstractCsrfForm<T>
 */

class DateCheckForm extends AbstractCsrfForm
{
    /**
     * LPA object if it was passed in via the constructor
     *
     * @var Lpa
     */
    protected $lpa;

    /**
     * @param  null|int|string  $name    Optional name for the element
     * @param  array            $options Optional options for the element
     */
    public function __construct($name = null, $options = [])
    {
        //  If an LPA has been passed in the options then extract it and set as a variable now
        if (array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }

        parent::__construct('form-date-checker', $options);
    }

    public function init()
    {
        //  If applicable add the life sustaining date element
        if ($this->lpa->document->type === Document::LPA_TYPE_HW) {
            $this->addDataCheckFieldset('sign-date-donor-life-sustaining');
        }

        $this->addDataCheckFieldset('sign-date-donor');
        $this->addDataCheckFieldset('sign-date-certificate-provider');

        //  Add a signing date for each attorney
        foreach ($this->lpa->document->primaryAttorneys as $idx => $attorney) {
            $this->addDataCheckFieldset('sign-date-attorney-' . $idx);
        }

        //  Add a signing date for each replacement attorney
        foreach ($this->lpa->document->replacementAttorneys as $idx => $attorney) {
            $this->addDataCheckFieldset('sign-date-replacement-attorney-' . $idx);
        }

        if ($this->lpa->completedAt !== null) {
            //  Add the applicant(s)
            if ($this->lpa->document->whoIsRegistering === 'donor') {
                //Applicant is donor
                $this->addDataCheckFieldset('sign-date-applicant-0');
            } elseif (is_array($this->lpa->document->whoIsRegistering)) {
                //Applicant is one or more primary attorneys
                for ($i = 0; $i < count($this->lpa->document->whoIsRegistering); $i++) {
                    $this->addDataCheckFieldset('sign-date-applicant-' . $i);
                }
            }
        }

        //  Add the submit button
        $this->add([
            'name'  => 'submit',
            'type'  => 'Submit',
        ]);

        parent::init();
    }

    /**
     * Add a standard data element to the form
     *
     * @param $elementName
     */
    private function addDataCheckFieldset($elementName)
    {
        //  Add the fieldset
        $this->add([
            'name'       => $elementName,
            'type'       => 'Application\Form\Fieldset\Dob',
            'attributes' => [
                'id' => $elementName,
            ],
        ]);

        //  Add data to the input filter
        $this->addToInputFilter([
            'name'          => $elementName,
            'required'      => true,
            'validators'    => [
                [
                    'name' => 'Application\Form\Validator\Date',
                ],
            ],
        ]);
    }
}
