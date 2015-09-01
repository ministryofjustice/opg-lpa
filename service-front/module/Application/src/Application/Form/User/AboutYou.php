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
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 5,
                        'messages' => [ Validator\StringLength::TOO_LONG => "must be %max% characters or fewer" ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'name-first',
            'required' => true,
            'error_message' => 'must not be blank',
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 50,
                        'messages' => [ Validator\StringLength::TOO_LONG => "must be %max% characters or fewer" ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'name-last',
            'required' => true,
            'error_message' => 'must not be blank',
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 50,
                        'messages' => [ Validator\StringLength::TOO_LONG => "must be %max% characters or fewer" ],
                    ],
                ],
            ],
        ]);

        //---

        $inputFilter->add([
            'name'     => 'dob-date-day',
            'required' => false,
            'allowEmpty' => true,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 1, 'max' => 31,
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
            'allowEmpty' => true,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => 1, 'max' => 12,
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
            'allowEmpty' => true,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'Between',
                    'options' => [
                        'min' => (int)date('Y') - 150, 'max' => (int)date('Y'),
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
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 50,
                        'messages' => [ Validator\StringLength::TOO_LONG => "must be %max% characters or fewer" ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'address-address2',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 50,
                        'messages' => [ Validator\StringLength::TOO_LONG => "must be %max% characters or fewer" ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'address-address3',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'max' => 50,
                        'messages' => [ Validator\StringLength::TOO_LONG => "must be %max% characters or fewer" ],
                    ],
                ],
            ],
        ]);

        $inputFilter->add([
            'name'     => 'address-postcode',
            'required' => false,
            'filters'  => [ ['name' => 'StripTags'], ['name' => 'StringTrim'] ],
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 1,
                        'max' => 8,
                        'messages' => [
                            Validator\StringLength::TOO_SHORT => "must be at least %min% characters",
                            Validator\StringLength::TOO_LONG => "must be %max% characters or fewer",
                        ],
                    ],
                ],
            ],
        ]);

    } // function

    public function setData( $data ){

        if( isset($data['dob-date']) ){

            $dob = new \DateTime( $data['dob-date'] );

            $data['dob-date-day'] = $dob->format('j');
            $data['dob-date-month'] = $dob->format('n');
            $data['dob-date-year'] = $dob->format('Y');

        }

        parent::setData( $data );

    }

    /**
     * We need to convert the DOB for the Model.
     *
     * @return array|object
     */
    public function getDataForModel(){

        $data = parent::getDataForModel();

        if( $data['dob-date-day'] > 0 && $data['dob-date-month'] > 0 && $data['dob-date-year'] > 0 ){

            $data['dob-date'] = "{$data['dob-date-year']}-{$data['dob-date-month']}-{$data['dob-date-day']}";

        }

        // Strip these working fields out...
        unset($data['dob-date-day'], $data['dob-date-month'], $data['dob-date-year']);

        $data = array_filter( $data, function($v){
            return !empty( $v );
        });

        return $data;

    } // function
    
    
    public function isValid()
    {
        if(!empty($this->data['dob-date-day'])||!empty($this->data['dob-date-month'])||!empty($this->data['dob-date-year'])) {
            if(!checkdate($this->data['dob-date-month'], $this->data['dob-date-day'], $this->data['dob-date-year'])) {
                $this->setMessages(['dob-date-day' => ['invalid date']]);
                return parent::isValid() & false;
            }
        }
        return parent::isValid();
    }

} // class
