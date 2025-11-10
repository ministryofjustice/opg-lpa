<?php

namespace Application\Form;

use Application\Form\Element\CsrfBuilder;
use Laminas\Form\Element\Csrf;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

abstract class AbstractCsrfForm extends AbstractForm
{
    /**
     * @var Csrf
     */
    private $csrf = null;


    /**
     * Set the CSRF element
     */
    public function setCsrf(CsrfBuilder $builder)
    {
        //  Add the csrf element
        $csrf = $builder(get_class($this));

        //  Add the CSRF specification to the input filter
        $this->addToInputFilter($csrf->getInputSpecification());

        $this->csrf = $csrf;

        $this->add($csrf);
    }

    /**
     * @return Csrf
     */
    public function getCsrf()
    {
        return $this->csrf;
    }
}
