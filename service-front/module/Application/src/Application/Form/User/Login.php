<?php
namespace Application\Form\User;


/**
 * Form for logging into the site
 *
 * Class Login
 * @package Application\Form\User
 */
class Login extends AbstractForm {

    public function __construct( $formName = 'login' ){

        parent::__construct($formName);

        //---

        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
        ));

        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
        ));

        //----------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'email',
            'required' => true,
            'error_message' => 'You need to enter your email address',
            'filters'  => array(
                array('name' => 'StringTrim'),
                array('name' => 'StringToLower'),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'password',
            'required' => true,
            'error_message' => 'You need to enter your password',
        ));

    } // function

} // class
