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
            'name' => 'postcode-service',
            'type' => 'Radio',
            'options'   => [
                'value_options' => [
                    'postcode-anywhere' => ['value' => 'postcode-anywhere'],
                    'moj-dsd' => ['value' => 'moj-dsd'],
                ],
                'disable_inarray_validator' => true,
            ],
        ));

        //--------------------------------

    } // function

} // class
