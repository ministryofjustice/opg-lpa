<?php

declare(strict_types=1);

namespace ApplicationTest\Form;

use Application\Form\AbstractCsrfForm;
use Application\Form\Element\CsrfBuilder;
use Application\Model\Service\Session\SessionUtility;
use Laminas\Form\FormInterface;
use Laminas\ServiceManager\ServiceManager;
use Mockery;
use Psr\Log\LoggerInterface;

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
        $sm
            ->shouldReceive('get')
            ->with('config')
            ->andReturn(['csrf' => ['salt' => 'Rando_Calrissian']]);

        $sessionUtility = Mockery::mock(SessionUtility::class);
        $sessionUtility
            ->shouldReceive('getFromMvc')
            ->with('CsrfValidator', 'token')
            ->andReturn(12345);

        $logger = Mockery::mock(LoggerInterface::class);

        $sm
            ->shouldReceive('get')
            ->with(SessionUtility::class)
            ->andReturn($sessionUtility);
        $sm
            ->shouldReceive('get')
            ->with('Logger')
            ->andReturn($logger);

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
