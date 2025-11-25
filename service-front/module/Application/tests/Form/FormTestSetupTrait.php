<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Form\AbstractCsrfForm;
use Application\Form\Element\CsrfBuilder;
use Laminas\Validator\Csrf as LaminasCsrfValidator;
use Laminas\Form\FormInterface;
use Laminas\ServiceManager\ServiceManager;
use Mockery;

trait FormTestSetupTrait
{
    /**
     * Form to test
     *
     * @var FormInterface
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

        $sm = Mockery::mock(ServiceManager::class);
        $sm->shouldReceive('get')
            ->with('config')
            ->andReturn(['csrf' => ['salt' => 'Rando_Calrissian']]);
        $sm->shouldReceive('build')->andReturn(new LaminasCsrfValidator());

        $csrfBuilder = new CsrfBuilder($sm);

        if ($form instanceof AbstractCsrfForm) {
            $form->setCsrf($csrfBuilder);
        }

        $this->form = $form;
    }

    /**
     * Function to easily enrich the form data with Csrf data
     */
    private function getCsrfData(): array
    {
        if ($this->form instanceof AbstractCsrfForm) {
            return [
                $this->form->getCsrf()->getName() => $this->form->getCsrf()->getValue(),
            ];
        }

        return [];
    }
}
