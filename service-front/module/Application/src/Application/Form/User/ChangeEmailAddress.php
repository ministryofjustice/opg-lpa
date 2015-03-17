<?php
namespace Application\Form\User;

use Zend\Validator;

use Zend\Authentication\Result;
use Zend\Authentication\Adapter\Exception\InvalidArgumentException;
use Application\Model\Service\Authentication\Adapter\AdapterInterface;

class ChangeEmailAddress extends AbstractForm {

    private $authAdapter;
    private $authenticationService;

    //---

    public function __construct( $formName = 'change-email-address' ){

        parent::__construct($formName);

        //---

        $this->add(array(
            'name' => 'password',
            'type' => 'Password',
        ));

        $this->add(array(
            'name' => 'email',
            'type' => 'Email',
        ));

        $this->add(array(
            'name' => 'email_confirm',
            'type' => 'Email',
        ));

        //--------------------------------

        $inputFilter = $this->getInputFilter();

        $inputFilter->add(array(
            'name'     => 'email',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name'    => 'EmailAddress',
                ),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'email_confirm',
            'required' => true,
            'filters'  => array(
                array('name' => 'StripTags'),
                array('name' => 'StringTrim'),
            ),
            'validators' => array(
                array(
                    'name'    => 'Identical',
                    'options' => array(
                        'token' => 'email',
                        'messages' => [
                            Validator\Identical::NOT_SAME => 'did not match',
                        ],
                    ),
                ),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'password',
            'required' => true,
            'validators' => array(
                array(
                    'name'    => 'Callback',
                    'options' => array(
                        'callback' => [ $this, 'validatePassword' ],
                        'messages' => [
                            Validator\Callback::INVALID_VALUE => 'is incorrect',
                        ],
                    ),
                ),
            ),
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function


    /**
     * Set the Authentication Adapter used to validate the user's password.
     *
     * @param AdapterInterface $authAdapter
     */
    public function setAuthAdapter( AdapterInterface $authAdapter, $authenticationService ){
        $this->authAdapter = $authAdapter;
        $this->authenticationService = $authenticationService;
    }


    /**
     * Validates if a given password is correct.
     *
     * The email address MUST already have been set.
     *
     * @param $value string The value from the password text field.
     * @return bool
     */
    public function validatePassword( $value ){

        if( !( $this->authAdapter instanceof AdapterInterface ) ){
            throw new InvalidArgumentException('AuthAdapter not set');
        }

        $this->authAdapter->setPassword( $value );

        $result = $this->authenticationService->authenticate( $this->authAdapter );

        return $result->isValid();

        //$result = $this->authAdapter->authenticate();

        //return ($result->getCode() === Result::SUCCESS);

    }

} // class
