<?php

namespace App\Form;

use App\Validator;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Form as LaminasForm;
use Laminas\Form\FormInterface;
use Laminas\InputFilter\Input;
use Laminas\InputFilter\InputFilter;

/**
 * Class AbstractForm
 * @package App\Form
 */
abstract class AbstractForm extends LaminasForm
{
    /**
     * @param InputFilter $inputFilter
     * @return void
     */
    protected function addCsrfElement(InputFilter $inputFilter): void
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
