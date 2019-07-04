<?php

namespace Application\Form\User;

use Zend\Validator\Identical;
use Zend\Validator\NotEmpty;

class Registration extends SetPassword
{
    public function init()
    {
        $this->add([
            'name' => 'email',
            'type' => 'Email',
        ]);

        $this->add([
            'name' => 'email_confirm',
            'type' => 'Email',
        ]);

        //Put in here to align the error summary with the order of the form as part of LPA-3169
        parent::init();
        $this->setName('registration');

        $this->add([
            'name'    => 'terms',
            'type'    => 'Checkbox',
            'options' => [
                'use_hidden_element' => false,
            ]
        ]);

        //  Add data to the input filter
        $this->setUseInputFilterDefaults(false);

        $this->addToInputFilter([
            'name'     => 'email',
            'required' => true,
            'filters'  => [
                [
                    'name' => 'StringToLower'
                ],
            ],
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages'           => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name' => 'Application\Form\Validator\EmailAddress',
                ]
            ],
        ]);

        $this->addToInputFilter([
            'name'     => 'email_confirm',
            'required' => true,
            'validators' => [
                [
                    'name'                   => 'NotEmpty',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'messages' => [
                            NotEmpty::IS_EMPTY => 'cannot-be-empty',
                        ],
                    ],
                ],
                [
                    'name'                   => 'Identical',
                    'break_chain_on_failure' => true,
                    'options'                => [
                        'token'    => 'email',
                        'messages' => [
                            Identical::NOT_SAME => 'did-not-match',
                        ],
                    ],
                ],
            ],
        ]);

        $this->addToInputFilter([
            'name'          => 'terms',
            'required'      => true,
            'error_message' => 'must-be-checked',
            'validators'    => [
                [
                    'name'    => 'Identical',
                    'break_chain_on_failure' => true,
                    'options' => [
                        'token' => '1',
                        'literal' => true,
                        'messages' => [
                            Identical::NOT_SAME => 'must-be-checked',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
