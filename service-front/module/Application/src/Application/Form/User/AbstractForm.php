<?php
namespace Application\Form\User;

use Zend\Form\Form;
use Zend\Form\Element\Csrf;

abstract class AbstractForm extends Form {

    public function __construct( $formName ){

        parent::__construct( $formName );

        $this->setAttribute( 'method', 'post' );

        $this->add( (new Csrf('secret'))->setCsrfValidatorOptions([
            'timeout' => null,
            'salt' => sha1('Application\Form\User-Salt'),
        ]));

    } // function

} // abstract class
