<?php
namespace Application\Form\User;

use Zend\Validator;

use Application\Model\Service\Authentication\AuthenticationService;
use Zend\Authentication\Exception\InvalidArgumentException;

class ChangePassword extends SetPassword {

    /**
     * @var AuthenticationService
     */
    private $authenticationService;

    //---

    public function __construct( $formName = 'change-password' ){

        parent::__construct($formName);

        //---

        $this->add(array(
            'name' => 'password_current',
            'type' => 'Password',
        ));

        //--------------------------------
        $this->setUseInputFilterDefaults(false);
        
        $inputFilter = $this->getInputFilter();

        //---

        $inputFilter->add(array(
            'name'     => 'password_current',
            'required' => true,
            'validators' => array(
                array(
                    'name'    => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options' => array(
                        'messages' => [
                            Validator\NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ),
                ),
                array(
                    'name'    => 'Callback',
                    'break_chain_on_failure' => true,
                    'options' => array(
                        'callback' => [ $this, 'validatePassword' ],
                        'messages' => [
                            Validator\Callback::INVALID_VALUE => 'is-incorrect',
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
