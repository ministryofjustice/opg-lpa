<?php

namespace Auth\Model\Service;

trait PasswordValidatorTrait
{

    /**
     * Determins if the given password passes validation
     *
     * @return boolean
     */
    protected function isPasswordValid($password)
    {

        $passwordValidator = new \Zend\Validator\ValidatorChain();

        $passwordValidator->attach(new \Zend\Validator\StringLength(['min' => 8]))
            ->attach(new \Zend\Validator\Regex(['pattern' => '/.*[0-9].*/']))   //  Must include 1 number
            ->attach(new \Zend\Validator\Regex(['pattern' => '/.*[a-z].*/']))   //  Must include one lower-case letter
            ->attach(new \Zend\Validator\Regex(['pattern' => '/.*[A-Z].*/']));  // Must include one uppdate-case letter

        return $passwordValidator->isValid($password);
    }
}
