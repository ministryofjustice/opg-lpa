<?php
namespace Application\Form\User;

use Zend\Validator;

class AboutYou extends AbstractForm {

    public function __construct( $formName = 'about-you' ){

        parent::__construct($formName);

        $this->add(array(
            'name' => 'name-title',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'name-first',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'name-last',
            'type' => 'Text',
        ));

        //---

        $this->add(array(
            'name' => 'dob-date-day',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'dob-date-month',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'dob-date-year',
            'type' => 'Text',
        ));

        //---

        $this->add(array(
            'name' => 'address-address1',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'address-address2',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'address-address3',
            'type' => 'Text',
        ));

        $this->add(array(
            'name' => 'address-postcode',
            'type' => 'Text',
        ));

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add([
            'name'     => 'name-title',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

        $inputFilter->add([
            'name'     => 'name-first',
            'required' => true,
            'error_message' => 'must not be blank',
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

        //var_dump($inputFilter); exit();

        $inputFilter->add([
            'name'     => 'name-last',
            'required' => true,
            'error_message' => 'must not be blank',
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

        //---

        $inputFilter->add([
            'name'     => 'dob-date-day',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'], ['name' => 'Int'] ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 1,
                        'max' => 31,
                        'messages' => [
                            Validator\Between::NOT_BETWEEN => "must be between %min% and %max%",
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'dob-date-month',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'], ['name' => 'Int'] ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 1,
                        'max' => 12,
                        'messages' => [
                            Validator\Between::NOT_BETWEEN => "must be between %min% and %max%",
                        ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'dob-date-year',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'], ['name' => 'Int'] ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => (int)date('Y') - 150,
                        'max' => (int)date('Y'),
                        'messages' => [
                            Validator\Between::NOT_BETWEEN => "must be between %min% and %max%",
                        ],
                    ],
                ],
            ],
        ]);

        //---

        $inputFilter->add([
            'name'     => 'address-address1',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

        $inputFilter->add([
            'name'     => 'address-address2',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

        $inputFilter->add([
            'name'     => 'address-address3',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

        $inputFilter->add([
            'name'     => 'address-postcode',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
        ]);

    } // function

} // class
