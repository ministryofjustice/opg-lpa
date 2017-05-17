<?php

namespace Application\Form;

use Application\Form\Validator\Csrf as CsrfValidator;
use Zend\Form\Element\Csrf;
use Zend\Form\Form;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;

abstract class AbstractForm extends Form implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * @var Csrf
     */
    private $csrf = null;

    public function init()
    {
        $this->setAttribute('method', 'post');
        $this->setAttribute('novalidate', 'novalidate');

        $this->setCsrf();

        parent::init();

        $this->prepare();
    }

    /**
     * Set the CSRF element
     */
    public function setCsrf()
    {
        //  Add the csrf element
        $csrfName = 'secret_' . md5(get_class($this));
        $csrf = new Csrf($csrfName);

        $csrfSalt = $this->getServiceLocator()
                         ->getServiceLocator()
                         ->get('Config')['csrf']['salt'];

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

    /**
     * Add input data to input filter
     *
     * @param array $inputData
     */
    protected function addToInputFilter(array $inputData)
    {
        //  Merge the required input filters into the input data
        $inputData = array_merge_recursive([
            'filters'  => [
                [
                    'name' => 'StripTags'
                ],
                [
                    'name' => 'StringTrim'
                ],
            ]
        ], $inputData);

        $filter = $this->getInputFilter();

        $filter->add($inputData);
    }
}
