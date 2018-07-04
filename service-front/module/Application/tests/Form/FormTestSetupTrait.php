<?php

namespace ApplicationTest\Form;

use Application\Form\AbstractCsrfForm;
use Mockery as m;

trait FormTestSetupTrait
{
    /**
     * Form to test
     *
     * @var AbstractCsrfForm
     */
    protected $form;

    /**
     * Set up the form to test
     */
    protected function setUpForm(AbstractCsrfForm $form)
    {
        //  Mock the input filter - the filter validation should pass to allow the validate by model to execute
        $inputFilter = m::mock('Zend\InputFilter\InputFilter')->makePartial();
        $inputFilter->shouldReceive('isValid')
            ->andReturn(true);

        $form->setInputFilter($inputFilter);

        //  Mock the form element manager and config
        $config = [
            'csrf' => [
                'salt' => 'Rando_Calrissian'
            ]
        ];

        $form->setConfig($config);
        $form->init();

        $this->form = $form;
    }
}
