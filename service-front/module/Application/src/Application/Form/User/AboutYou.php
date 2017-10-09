<?php

namespace Application\Form\User;

use Application\Form\AbstractCsrfForm;
use Opg\Lpa\DataModel\Common\Address;
use Opg\Lpa\DataModel\Common\Dob;
use Opg\Lpa\DataModel\Common\LongName;
use Zend\Form\FormInterface;
use Zend\Validator\Between;
use Zend\Validator\NotEmpty;
use Zend\Validator\StringLength;

class AboutYou extends AbstractCsrfForm
{
    public function init()
    {
        $this->setName('about-you');

        $this->add([
            'name' => 'name-title',
            'type' => 'Text',
            'attributes' => [
                'data-select-options' => json_encode([
                    '',
                    'Mr',
                    'Mrs',
                    'Miss',
                    'Ms',
                    'Dr',
                    'Other',
                ]),
            ],
        ]);

        $this->add([
            'name' => 'name-first',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'name-last',
            'type' => 'Text',
        ]);

        //---

        $this->add([
            'name' => 'dob-date-day',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'dob-date-month',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'dob-date-year',
            'type' => 'Text',
        ]);

        //---

        $this->add([
            'name' => 'address-address1',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'address-address2',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'address-address3',
            'type' => 'Text',
        ]);

        $this->add([
            'name' => 'address-postcode',
            'type' => 'Text',
        ]);

        //--------------------------------

        //  Add data to the input filter
        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'     => 'name-title',
            'required' => true,
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => LongName::TITLE_MAX_LENGTH,
                        'messages' => [ StringLength::TOO_LONG => "max-%max%-characters" ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'name-first',
            'required' => true,
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => LongName::FIRST_NAME_MAX_LENGTH,
                        'messages' => [ StringLength::TOO_LONG => "max-%max%-characters" ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'name-last',
            'required' => true,
            'validators' => [
                [
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => LongName::LAST_NAME_MAX_LENGTH,
                        'messages' => [ StringLength::TOO_LONG => "max-%max%-characters" ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'dob-date-day',
            'required' => false,
            'allowEmpty' => true,
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 1, 'max' => 31,
                        'messages' => [
                            Between::NOT_BETWEEN => "must-be-between-%min%-and-%max%-characters",
                        ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'dob-date-month',
            'required' => false,
            'allowEmpty' => true,
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 1, 'max' => 12,
                        'messages' => [
                            Between::NOT_BETWEEN => "must-be-between-%min%-and-%max%-characters",
                        ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'dob-date-year',
            'required' => false,
            'allowEmpty' => true,
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => (int)date('Y') - 150, 'max' => (int)date('Y'),
                        'messages' => [
                            Between::NOT_BETWEEN => "must-be-between-%min%-and-%max%-characters",
                        ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'address-address1',
            'required' => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => Address::ADDRESS_LINE_MAX_LENGTH,
                        'messages' => [ StringLength::TOO_LONG => "max-%max%-characters" ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'address-address2',
            'required' => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => Address::ADDRESS_LINE_MAX_LENGTH,
                        'messages' => [ StringLength::TOO_LONG => "max-%max%-characters" ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'address-address3',
            'required' => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => Address::ADDRESS_LINE_MAX_LENGTH,
                        'messages' => [ StringLength::TOO_LONG => "max-%max%-characters" ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'address-postcode',
            'required' => false,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => Address::POSTCODE_MIN_LENGTH,
                        'max' => Address::POSTCODE_MAX_LENGTH,
                        'messages' => [
                            StringLength::TOO_SHORT => "min-%min%-characters",
                            StringLength::TOO_LONG => "max-%max%-characters",
                        ],
                    ],
                ],
            ],
        ]);

        parent::init();
    }

    public function setData($data)
    {
        if (isset($data['dob-date'])) {
            $dob = new \DateTime($data['dob-date']);

            $data['dob-date-day'] = $dob->format('j');
            $data['dob-date-month'] = $dob->format('n');
            $data['dob-date-year'] = $dob->format('Y');
        }

        parent::setData($data);
    }

    /**
     * Retrieve the validated data
     * We need to convert the DOB for the model
     *
     * By default, retrieves normalized values; pass one of the
     * FormInterface::VALUES_* constants to shape the behavior.
     *
     * @param  int $flag
     * @return array|object
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $data = parent::getData($flag);

        if (is_array($data)) {
            if ($data['dob-date-day'] > 0 && $data['dob-date-month'] > 0 && $data['dob-date-year'] > 0) {
                $data['dob-date'] = "{$data['dob-date-year']}-{$data['dob-date-month']}-{$data['dob-date-day']}";
            }

            // Strip these working fields out...
            unset($data['dob-date-day'], $data['dob-date-month'], $data['dob-date-year']);

            $data = array_filter($data, function ($v) {
                return !empty($v);
            });

            // If no address is set, ensure NULL is passed.

            if (empty($data['address-address1'])
                && empty($data['address-address2'])
                && empty($data['address-address3'])
                && empty($data['address-postcode'])) {

                $data['address'] = null;
            }
        }

        return $data;
    }

    public function isValid()
    {
        if (!empty($this->data['dob-date-day']) || !empty($this->data['dob-date-month']) || !empty($this->data['dob-date-year'])) {
            if (!checkdate($this->data['dob-date-month'], $this->data['dob-date-day'], $this->data['dob-date-year'])) {
                $this->setMessages(['dob-date-day' => ['invalid-date']]);
                return parent::isValid() & false;
            }

            // Ensure the date is in the past...
            $date = Dob::parseDob("{$this->data['dob-date-year']}-{$this->data['dob-date-month']}-{$this->data['dob-date-day']}");

            if (!($date instanceof \DateTime) || $date >= new \DateTime('today')) {
                $this->setMessages(['dob-date-day' => ['invalid-date']]);
                return parent::isValid() & false;
            }
        }

        return parent::isValid();
    }
}
