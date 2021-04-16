<?php

namespace ApplicationTest\Form;

use Application\Form\AbstractCsrfForm;
use Application\Form\AbstractForm;
use Application\Form\Lpa\AbstractActorForm;
use Application\Form\Lpa\AbstractLpaForm;
use Application\Form\Lpa\AbstractMainFlowForm;
use Laminas\Form\FormInterface;
use Mockery as m;
use Laminas\InputFilter\InputFilter;

trait FormTestSetupTrait
{
    /**
     * Form to test
     *
     * @var AbstractForm
     */
    protected $form;

    /**
     * @param FormInterface $form
     *
     * Set up the form to test
     */
    protected function setUpForm(FormInterface $form)
    {
        $form->init();

        if ($form instanceof AbstractCsrfForm) {
            $config = [
                'csrf' => [
                    'salt' => 'Rando_Calrissian'
                ]
            ];

            $form->setConfig($config);
            $form->setCsrf();
        }

        $this->form = $form;
    }

    /**
     * @param FormInterface $form
     *
     * Set up the form to test
     */
    protected function setUpCsrfForm(FormInterface $form)
    {
        $this->setUpForm($form);
    }

    /**
     * @param FormInterface $form
     *
     * Set up the form to test
     */
    protected function setUpLpaForm(FormInterface $form)
    {
        $this->setUpForm($form);
    }

    /**
     * Function to easily enrich the form data with Csrf data
     *
     * @return array
     */
    private function getCsrfData()
    {
        if ($this->form instanceof AbstractCsrfForm) {
            return [
                $this->form->getCsrf()->getName() => $this->form->getCsrf()->getValue(),
            ];
        }

        return [];
    }
}
