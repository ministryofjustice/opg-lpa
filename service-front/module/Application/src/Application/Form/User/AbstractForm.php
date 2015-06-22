<?php
namespace Application\Form\User;

use Zend\Form\Form;
use Zend\Form\Element\Csrf;
use Application\Form\Validator\Csrf as CsrfValidator;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

use Application\Model\Service\ServiceDataInputInterface;

abstract class AbstractForm extends Form implements ServiceDataInputInterface, ServiceLocatorAwareInterface {

    use ServiceLocatorAwareTrait;

    /**
     * @var string The Csrf name user for this form.
     */
    private $csrfName = null;

    public function __construct( $formName ){

        parent::__construct( $formName );



    } // function

    public function init()
    {
        parent::init();

        $this->setAttribute( 'method', 'post' );

        //---

        $this->csrfName = 'secret_'.md5(get_class($this));

        $this->add( (new Csrf($this->csrfName))->setCsrfValidator(
            new CsrfValidator([
                'name' => $this->csrfName,
                'salt' => $this->getServiceLocator()->getServiceLocator()->get('Config')['csrf']['salt'],
            ])
        ));

    }

    /**
     * @return string The CSRF name user for this form.
     */
    public function csrfName(){
        return $this->csrfName;
    }

    /**
     * By default we simply return the data unchanged.
     *
     * @return array|object
     */
    public function getDataForModel(){
        return $this->getData();
    }

} // abstract class
