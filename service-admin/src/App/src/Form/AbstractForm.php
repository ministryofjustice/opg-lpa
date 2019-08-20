<?php

namespace App\Form;

use App\Validator;
use Zend\Form\Element\Csrf;
use Zend\Form\Form as ZendForm;
use Zend\Form\FormInterface;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;

/**
 * Class AbstractForm
 * @package App\Form
 */
abstract class AbstractForm extends ZendForm
{
    /**
     * @param InputFilter $inputFilter
     */
    protected function addCsrfElement(InputFilter $inputFilter)
    {
        $options = $this->getOptions();

        $field = new Csrf('secret');

        $field->setCsrfValidator(new Validator\Csrf([
            'name' => $this->getName(),
            'secret' => $options['csrf']
        ]));

        $input = new Input($field->getName());

        $input->getValidatorChain()
              ->attach(new Validator\NotEmpty());

        $this->add($field);

        $inputFilter->add($input);
    }

    /**
     * Function strips out the 'secret' value if set
     *
     * @param int $flag
     * @return array|object
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $data = parent::getData($flag);

        if (is_array($data)) {
            unset($data['secret']);
        }

        return $data;
    }
}
