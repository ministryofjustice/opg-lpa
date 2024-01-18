<?php

namespace Application\Form;

use Application\Form\Validator\Csrf as CsrfValidator;
use Laminas\Form\Element\Csrf;

/**
 * @template T
 * @template-extends AbstractForm<T>
 */

abstract class AbstractCsrfForm extends AbstractForm
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Csrf
     */
    private $csrf = null;

    public function setConfig(array $config)
    {
        $this->config = $config;
    }

    /**
     * Set the CSRF element
     */
    public function setCsrf()
    {
        //  Add the csrf element
        $csrfName = 'secret_' . md5(get_class($this));
        $csrf = new Csrf($csrfName);

        $csrfSalt = $this->config['csrf']['salt'];

        $csrfValidator = new CsrfValidator([
            'name' => $csrf->getName(),
            'salt' => $csrfSalt,
        ]);

        $csrf->setCsrfValidator($csrfValidator);

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
