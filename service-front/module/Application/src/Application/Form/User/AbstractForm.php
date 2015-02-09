<?php
namespace Application\Form\User;

use Zend\Form\Form;
use Zend\Form\Element\Csrf;

abstract class AbstractForm extends Form {

    public function __construct( $formName ){

        parent::__construct( $formName );

        $this->setAttribute( 'method', 'post' );

        $this->add( new Csrf('secret') );

    } // function

} // abstract class
