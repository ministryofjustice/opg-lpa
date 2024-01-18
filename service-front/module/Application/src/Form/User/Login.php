<?php

namespace Application\Form\User;

use Application\Form\AbstractForm;

/**
 * Form for logging into the site
 *
 * @template T
 * @template-extends AbstractForm<T>
 */

class Login extends AbstractForm
{
    public function init()
    {
        $this->setName('login');

        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'password',
            'type' => 'Password',
        ]);

        //  Add data to the input filter
        $this->addToInputFilter([
            'name'                   => 'email',
            'break_chain_on_failure' => true,
            'required'               => true,
            'error_message'          => 'cannot-be-empty',
            'filters'                => [
                [
                    'name' => 'StringToLower'
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'                   => 'password',
            'break_chain_on_failure' => true,
            'required'               => true,
            'error_message'          => 'cannot-be-empty',
        ]);

        parent::init();
    }
}
