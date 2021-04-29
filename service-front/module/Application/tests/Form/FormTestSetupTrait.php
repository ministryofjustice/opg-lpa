<?php

namespace ApplicationTest\Form;

use Application\Form\AbstractCsrfForm;
use Application\Form\AbstractForm;
use Laminas\Form\FormInterface;

trait FormTestSetupTrait
{
    /**
     * Form to test
     *
     * @var AbstractForm
     */
    protected $form;

    /**
     * @param AbstractForm $form
     *
     * Set up the form to test
     */
    protected function setUpForm(AbstractForm $form)
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
     * @param AbstractCsrfForm $form
     *
     * Set up the form to test
     */
    protected function setUpCsrfForm(AbstractCsrfForm $form)
    {
        //  Mock the form element manager and config
        $config = [
            'csrf' => [
                'salt' => 'Rando_Calrissian'
            ]
        ];

        $form->setConfig($config);

        //  Pass on the set up - do this last
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

    /**
     * @param AbstractLpaForm $form
     *
     * Set up the form to test
     */
    protected function setUpLpaForm(AbstractLpaForm $form)
    {
        //  TODO...

        //  Pass on the set up - do this last
        $this->setUpCsrfForm($form);
    }

    /**
     * @param AbstractActorForm $form
     *
     * Set up the form to test
     */
    protected function setUpActorForm(AbstractActorForm $form)
    {
        //  TODO...

        //  Pass on the set up - do this last
        $this->setUpLpaForm($form);
    }

    /**
     * @param AbstractMainFlowForm $form
     *
     * Set up the form to test
     */
    protected function setUpMainFlowForm(AbstractMainFlowForm $form)
    {
        //  TODO...

        //  Pass on the set up - do this last
        $this->setUpLpaForm($form);
    }
}
