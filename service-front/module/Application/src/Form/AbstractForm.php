<?php

namespace Application\Form;

use Laminas\Form\Form;
use Application\Logging\LoggerTrait;

abstract class AbstractForm extends Form
{
    use LoggerTrait;

    public function init()
    {
        $this->getLogger()->err(sprintf(
            "{AbstractForm:init} settingAttributes"
        ));
        $this->setAttribute('method', 'post');
        $this->setAttribute('novalidate', 'novalidate');

        $this->getLogger()->err(sprintf(
            "{AbstractForm:init} calling parent init"
        ));
        parent::init();
        $this->getLogger()->err(sprintf(
            "{AbstractForm:init} done parent init"
        ));

        $this->getLogger()->err(sprintf(
            "{AbstractForm:init} calling prepare"
        ));
        $this->prepare();
        $this->getLogger()->err(sprintf(
            "{AbstractForm:init} done prepare"
        ));
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
