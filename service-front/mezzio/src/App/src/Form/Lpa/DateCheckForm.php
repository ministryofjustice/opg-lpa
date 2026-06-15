<?php

declare(strict_types=1);

namespace App\Form\Lpa;

use App\Form\Validator\Date as DateValidator;
use MakeShared\DataModel\Lpa\Document\Document;

/**
 * @template T
 * @template-extends AbstractLpaForm<T>
 */
class DateCheckForm extends AbstractLpaForm
{
    public function __construct($name = null, $options = [])
    {
        // Handle case where Laminas InvokableFactory passes options as the first argument
        if (is_array($name) && empty($options)) {
            $options = $name;
            $name    = null;
        }

        if (array_key_exists('lpa', $options)) {
            $this->lpa = $options['lpa'];
            unset($options['lpa']);
        }

        // Call grandparent constructor directly since AbstractLpaForm would double-process options
        \App\Form\AbstractForm::__construct('form-date-checker', $options);
    }

    public function init()
    {
        if ($this->lpa->document->type === Document::LPA_TYPE_HW) {
            $this->addDataCheckFieldset('sign-date-donor-life-sustaining');
        }

        $this->addDataCheckFieldset('sign-date-donor');
        $this->addDataCheckFieldset('sign-date-certificate-provider');

        foreach ($this->lpa->document->primaryAttorneys as $idx => $attorney) {
            $this->addDataCheckFieldset('sign-date-attorney-' . $idx);
        }

        foreach ($this->lpa->document->replacementAttorneys as $idx => $attorney) {
            $this->addDataCheckFieldset('sign-date-replacement-attorney-' . $idx);
        }

        if ($this->lpa->completedAt !== null) {
            if ($this->lpa->document->whoIsRegistering === 'donor') {
                $this->addDataCheckFieldset('sign-date-applicant-0');
            } elseif (is_array($this->lpa->document->whoIsRegistering)) {
                for ($i = 0; $i < count($this->lpa->document->whoIsRegistering); $i++) {
                    $this->addDataCheckFieldset('sign-date-applicant-' . $i);
                }
            }
        }

        $this->add([
            'name' => 'submit',
            'type' => 'Submit',
        ]);

        parent::init();
    }

    private function addDataCheckFieldset(string $elementName): void
    {
        $this->add([
            'name'       => $elementName,
            'type'       => 'App\Form\Fieldset\Dob',
            'attributes' => ['id' => $elementName],
        ]);

        $this->addToInputFilter([
            'name'       => $elementName,
            'required'   => true,
            'validators' => [
                ['name' => DateValidator::class],
            ],
        ]);
    }

    protected function validateByModel()
    {
        return [
            'isValid'  => true,
            'messages' => [],
        ];
    }
}
