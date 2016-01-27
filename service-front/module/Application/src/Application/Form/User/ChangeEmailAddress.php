<?php
namespace Application\Form\User;

use Zend\Validator;

use Application\Model\Service\Authentication\AuthenticationService;
use Zend\Authentication\Exception\InvalidArgumentException;

class ChangeEmailAddress extends AbstractForm {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    //---

    public function __construct( $formName = 'change-email-address' ){

        parent::__construct($formName);

        //---

        $this->add(array(
            'name' => 'password_current',
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

        $this->setUseInputFilterDefaults(false);
        
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
            array(
                'name'    => 'NotEmpty',
                'options' => array(
                    'messages' => [
                        Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                    ],
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
                            Validator\Identical::NOT_SAME => 'did-not-match',
                        ],
                    ),
                ),
                array(
                    'name'    => 'NotEmpty',
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
            ),
        ));

        $inputFilter->add(array(
            'name'     => 'password_current',
            'required' => true,
            'validators' => array(
                array(
                    'name'    => 'NotEmpty',
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
            ),
        ));

        //---

        $this->setInputFilter( $inputFilter );

    } // function


    /**
     * Set the Authentication Service used to validate the user's password.
     *
     * @param AuthenticationService $authenticationService
     */
    public function setAuthenticationService( AuthenticationService $authenticationService ){
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

        if( !( $this->authenticationService instanceof AuthenticationService ) ){
            throw new InvalidArgumentException('AuthenticationService not set');
        }

        // Set the password in teh adapter.
        $this->authenticationService->getAdapter()->setPassword( $value );

        return $this->authenticationService->verify()->isValid();

    }

} // class
