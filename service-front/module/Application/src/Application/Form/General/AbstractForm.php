<?php
namespace Application\Form\General;

use Zend\Form\Form;
use Zend\Form\Element\Csrf;

use Application\Model\Service\ServiceDataInputInterface;

abstract class AbstractForm extends Form implements ServiceDataInputInterface {

    public function __construct( $formName ){

        parent::__construct( $formName );

        $this->setAttribute( 'method', 'post' );

        $this->add( (new Csrf('secret'))->setCsrfValidatorOptions([
            'timeout' => null,
            'salt' => sha1('Application\Form\General-Salt'),
        ]));

    } // function

    /**
     * By default we simply return the data unchanged.
     *
     * @return array|object
     */
    public function getDataForModel(){
        return $this->getData();
    }

} // abstract class
