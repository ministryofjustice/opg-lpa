<?php
namespace Application\Form\General;

use Application\Form\Validator\Csrf as CsrfValidator;
use Zend\Form\Element\Csrf;
use Zend\Form\Form;

use Application\Model\Service\ServiceDataInputInterface;

abstract class AbstractForm extends Form implements ServiceDataInputInterface
{
    /**
     * @var string The Csrf name user for this form.
     */
    private $csrfName = null;

    /**
     * @return string The CSRF name user for this form.
     */
    public function csrfName()
    {
        return $this->csrfName;
    }

    public function __construct($formName = null)
    {
        parent::__construct($formName);

        $this->setAttribute('method', 'post');
        $this->setAttribute('novalidate', 'novalidate');

        $this->csrfName = 'secret_' . md5(get_class($this));

        $this->add((new Csrf($this->csrfName))->setCsrfValidator(
            new CsrfValidator([
                'name' => $this->csrfName,
                'salt' => md5('Feedback Form Salt'),
            ])
        ));
    }

    /**
     * By default we simply return the data unchanged.
     *
     * @return array|object
     */
    public function getDataForModel()
    {
        return $this->getData();
    }
}
