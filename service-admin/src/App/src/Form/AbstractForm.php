<?php

namespace App\Form;

use App\Validator;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Form;
use Laminas\Form\FormInterface;
use Laminas\InputFilter\Input;

/**
 * @template T
 * @template-extends Form<T>
 */

abstract class AbstractForm extends Form
{
    /**
     * @return void
     */
    protected function addCsrfElement(): void
    {
        $options = $this->getOptions();

        $csrfValidator = new Validator\Csrf([
            'name' => $this->getName(),
            'secret' => $options['csrf']
        ]);

        $field = new Csrf('secret');
        $field->setCsrfValidator($csrfValidator);

        $input = new Input($field->getName());

        $input->getValidatorChain()
              ->attach(new Validator\NotEmpty())
              ->attach($csrfValidator);

        $this->add($field);

        $this->getInputFilter()->add($input);
    }

    /**
     * Function strips out the 'secret' value if set
     *
     * @param int $flag
     * @return array<string, mixed>|object
     */
    public function getData($flag = FormInterface::VALUES_NORMALIZED)
    {
        $data = parent::getData($flag);

        if (is_array($data)) {
            unset($data['secret']);
        }

        return $data;
    }

    /**
     * Ensures that the data is set through the form's InputFilter so the filters
     * are actually applied.
     */
    public function setData(iterable $data)
    {
        $filteredData = $this->getInputFilter()->setData($data)->getValues();
        return parent::setData($filteredData);
    }
}
