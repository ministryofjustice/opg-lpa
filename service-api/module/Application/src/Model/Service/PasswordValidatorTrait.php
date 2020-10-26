<?php

namespace Application\Model\Service;

trait PasswordValidatorTrait
{
    /**
     * Determins if the given password passes validation
     *
     * @return boolean
     */
    protected function isPasswordValid($password)
    {

        $passwordValidator = new \Laminas\Validator\ValidatorChain();

        $passwordValidator->attach(new \Laminas\Validator\StringLength(['min' => 8]))
            ->attach(new \Laminas\Validator\Regex(['pattern' => '/.*[0-9].*/']))   //  Must include 1 number
            ->attach(new \Laminas\Validator\Regex(['pattern' => '/.*[a-z].*/']))   //  Must include one lower-case letter
            ->attach(new \Laminas\Validator\Regex(['pattern' => '/.*[A-Z].*/']));  // Must include one uppdate-case letter

        return $passwordValidator->isValid($password);
    }
}
