<?php

namespace Application\Model\Service;

trait PasswordValidatorTrait {

    /**
     * Determins if the given password passes validation 
     *
     * @return boolean
     */
    protected function isPasswordValid($password){

        $passwordValidator = new \Zend\Validator\ValidatorChain();

        $passwordValidator->attach(
            new \Zend\Validator\StringLength( ['min' => 8] )
        )->attach(
            // Must include 1 number
            new \Zend\Validator\Regex( [ 'pattern' => '/.*[0-9].*/' ] )
        )->attach(
            // Must include one lower-case letter
            new \Zend\Validator\Regex( [ 'pattern' => '/.*[a-z].*/' ] )
        )->attach(
            // Must include one uppdate-case letter
            new \Zend\Validator\Regex( [ 'pattern' => '/.*[A-Z].*/' ] )
        );

        return $passwordValidator->isValid($password);

    }

} // class
