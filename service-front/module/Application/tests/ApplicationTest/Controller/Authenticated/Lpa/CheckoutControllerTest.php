<?php

namespace ApplicationTest\Controller\Authenticated\Lpa;

use Application\Controller\Authenticated\Lpa\CheckoutController;
use Application\Form\Lpa\PaymentForm;
use ApplicationTest\Controller\AbstractControllerTest;
use Mockery;
use Mockery\MockInterface;

class CheckoutControllerTest extends AbstractControllerTest
{
    /**
     * @var CheckoutController
     */
    private $controller;
    /**
     * @var MockInterface|PaymentForm
     */
    private $form;

    public function setUp()
    {
        $this->controller = new CheckoutController();
        parent::controllerSetUp($this->controller);

        $this->form = Mockery::mock(PaymentForm::class);
        $this->formElementManager->shouldReceive('get')->with('Application\Form\Lpa\PaymentForm')->andReturn($this->form);
    }
}