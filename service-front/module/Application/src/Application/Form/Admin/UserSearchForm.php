<?php

namespace Application\Form\Admin;

use Application\Form\AbstractCsrfForm;
use Zend\Validator\StringLength;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class UserSearchForm extends AbstractCsrfForm
{
    public function init()
    {
        $this->setName('admin-user-search');

        $this->add([
            'name' => 'email',
            'type' => 'Email',
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

        parent::init();
    }
}
