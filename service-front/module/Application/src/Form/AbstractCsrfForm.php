<?php

namespace Application\Form;

use Application\Form\Validator\Csrf as CsrfValidator;
use Laminas\Form\Element\Csrf;

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

    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * Set the CSRF element
     *
     * @return void
     */
    public function setCsrf(): void
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
