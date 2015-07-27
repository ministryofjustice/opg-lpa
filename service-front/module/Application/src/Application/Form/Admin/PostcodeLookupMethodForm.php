<?php
namespace Application\Form\Admin;

use Application\Form\General\AbstractForm;

/**
 * For an admin to set the system message
 *
 * Class SystemMessageForm
 * @package Application\Form\Admin
 */
class PostcodeLookupMethodForm extends AbstractForm {

    public function __construct( $formName = 'admin-postcode-lookup-method' ) {

        parent::__construct( $formName );

        //--- Form elements

        $this->add(array(
            'name' => 'use-new-postcode-lookup-method',
            'type' => 'Checkbox',
        ));

        //--------------------------------

    } // function

} // class
