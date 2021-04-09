<?php

namespace Application\Form;

use Application\Form\Validator\Csrf as CsrfValidator;
use Laminas\Form\Element\Csrf;
use Application\Logging\LoggerTrait;

abstract class AbstractCsrfForm extends AbstractForm
{
    use LoggerTrait;
    /**
     * @var array
     */
    private $config;

    /**
     * @var Csrf
     */
    private $csrf = null;

    public function init()
    {

        $this->getLogger()->err(sprintf(
            "{AbstractCsrfForm:init} about to call parent init"
        ));

        parent::init();

        $this->getLogger()->err(sprintf(
            "{AbstractCsrfForm:init} done parent init"
        ));
    }

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
